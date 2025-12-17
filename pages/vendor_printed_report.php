<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'vendor') {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Get Vendor's Stall Info
$stmt = $db->prepare("SELECT StallId, StallName FROM stalls WHERE StaffId = :uid");
$stmt->execute([':uid' => $userId]);
$stall = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$stall) { echo "No stall found."; exit; }
$stallId = $stall['StallId'];
$stallName = $stall['StallName'];

// Get Filters from URL
$search = $_GET['search'] ?? '';
$filterFood = $_GET['food_id'] ?? '';
$viewMode = $_GET['view_mode'] ?? 'custom';

// Set Date Range
if ($viewMode === 'daily') {
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d');
    $periodLabel = date('d M Y') . ' (Today)';
} elseif ($viewMode === 'monthly') {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
    $periodLabel = date('01 M Y') . ' - ' . date('t M Y') . ' (Current Month)';
} else {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $periodLabel = date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate));
}

// Base Query Conditions
$baseWhere = "WHERE o.StallId = :sid AND DATE(o.CreatedAt) BETWEEN :start AND :end";
$params = [':sid' => $stallId, ':start' => $startDate, ':end' => $endDate];

// Apply Filters
if (!empty($search)) {
    $baseWhere .= " AND (u.Name LIKE :search OR p.ProductName LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($filterFood)) {
    $baseWhere .= " AND ol.ProductId = :pid";
    $params[':pid'] = $filterFood;
}

// ========================================
// DETERMINE COLUMN ORDER & SORTING
// ========================================
$columns = [];
$orderBy = [];

if ($viewMode === 'daily') {
    // TODAY: No Date column, sort by OrderId
    $columns = ['OrderId', 'CustomerName', 'ProductName', 'Quantity', 'Subtotal'];
    $orderBy = ['o.OrderId ASC'];
    $reportTitle = 'Daily Sales Report';
    
} elseif (!empty($filterFood)) {
    // FOOD FILTER: ProductName, Date, OrderId, CustomerName
    $columns = ['ProductName', 'OrderDate', 'OrderId', 'CustomerName', 'Quantity', 'Subtotal'];
    $orderBy = ['p.ProductName ASC', 'DATE(o.CreatedAt) ASC', 'o.OrderId ASC'];
    $reportTitle = 'Sales Report (Filtered by Product)';
    
} elseif (!empty($search)) {
    // SEARCH: Determine if searching for customer or food
    // Check which matches more in the dataset
    $searchCheck = $db->prepare("
        SELECT 
            SUM(CASE WHEN u.Name LIKE :search THEN 1 ELSE 0 END) as cust_matches,
            SUM(CASE WHEN p.ProductName LIKE :search THEN 1 ELSE 0 END) as prod_matches
        FROM orders o
        JOIN orderitems ol ON o.OrderId = ol.OrderId
        JOIN products p ON ol.ProductId = p.ProductId
        JOIN users u ON o.UserId = u.UserId
        $baseWhere
    ");
    $searchCheck->execute($params);
    $matches = $searchCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($matches['cust_matches'] > $matches['prod_matches']) {
        // Customer Search: CustomerName, Date, OrderId, ProductName
        $columns = ['CustomerName', 'OrderDate', 'OrderId', 'ProductName', 'Quantity', 'Subtotal'];
        $orderBy = ['u.Name ASC', 'DATE(o.CreatedAt) ASC', 'o.OrderId ASC'];
        $reportTitle = 'Sales Report (Filtered by Customer)';
    } else {
        // Food Search: ProductName, Date, OrderId, CustomerName
        $columns = ['ProductName', 'OrderDate', 'OrderId', 'CustomerName', 'Quantity', 'Subtotal'];
        $orderBy = ['p.ProductName ASC', 'DATE(o.CreatedAt) ASC', 'o.OrderId ASC'];
        $reportTitle = 'Sales Report (Filtered by Product)';
    }
    
} else {
    // DEFAULT (Monthly/Custom): Date, OrderId, CustomerName, ProductName
    $columns = ['OrderDate', 'OrderId', 'CustomerName', 'ProductName', 'Quantity', 'Subtotal'];
    $orderBy = ['DATE(o.CreatedAt) ASC', 'o.OrderId ASC'];
    $reportTitle = $viewMode === 'monthly' ? 'Monthly Sales Report' : 'Sales Report';
}

$orderByClause = 'ORDER BY ' . implode(', ', $orderBy);

// ========================================
// FETCH DETAILED SALES DATA
// ========================================
$sql = "SELECT 
            o.OrderId,
            DATE(o.CreatedAt) as OrderDate,
            u.Name as CustomerName,
            p.ProductName,
            ol.Quantity,
            ol.Subtotal
        FROM orders o
        JOIN orderitems ol ON o.OrderId = ol.OrderId
        JOIN products p ON ol.ProductId = p.ProductId
        JOIN users u ON o.UserId = u.UserId
        $baseWhere
        $orderByClause";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Grand Total
$grandTotal = array_sum(array_column($reportData, 'Subtotal'));

// ========================================
// FETCH PRODUCT SUMMARY FOR HISTOGRAM
// ========================================
$summarySql = "SELECT 
                p.ProductName,
                p.UnitPrice,
                SUM(ol.Quantity) as TotalQty,
                SUM(ol.Subtotal) as TotalSales
            FROM orders o
            JOIN orderitems ol ON o.OrderId = ol.OrderId
            JOIN products p ON ol.ProductId = p.ProductId
            JOIN users u ON o.UserId = u.UserId
            $baseWhere
            GROUP BY p.ProductId
            ORDER BY TotalQty DESC";

$stmtSummary = $db->prepare($summarySql);
$stmtSummary->execute($params);
$summaryData = $stmtSummary->fetchAll(PDO::FETCH_ASSOC);

// Prepare Chart Data
$chartLabels = array_column($summaryData, 'ProductName');
$chartValues = array_column($summaryData, 'TotalQty');

// Column Headers Map
$columnHeaders = [
    'OrderDate' => 'Date',
    'OrderId' => 'Order ID',
    'CustomerName' => 'Customer',
    'ProductName' => 'Product',
    'Quantity' => 'Qty',
    'Subtotal' => 'Subtotal'
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $reportTitle; ?> - <?php echo htmlspecialchars($stallName); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            color: #2E3440; 
            background: white;
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2E3440;
        }
        
        .header h1 {
            font-size: 28px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
            color: #2E3440;
        }
        
        .header h2 {
            font-size: 22px;
            color: #5E81AC;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .header .period {
            font-size: 14px;
            color: #4C566A;
            font-weight: 500;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin: 30px 0 15px 0;
            color: #2E3440;
            border-bottom: 2px solid #D8DEE9;
            padding-bottom: 8px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 13px;
        }
        
        thead th {
            background: #ECEFF4;
            color: #2E3440;
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #2E3440;
        }
        
        tbody td {
            padding: 10px;
            border-bottom: 1px solid #E5E9F0;
            vertical-align: top;
            min-height: 40px; /* Ensures empty cells maintain height */
        }
        
        tbody tr:hover {
            background: #F9FAFB;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .total-row {
            background: #B48EAD !important;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        
        .total-row td {
            border-top: 3px solid #2E3440;
            border-bottom: 3px solid #2E3440;
            padding: 14px 10px;
        }
        
        .chart-container {
            width: 100%;
            height: 350px;
            margin: 30px 0;
            page-break-inside: avoid;
        }
        
        .summary-table {
            margin-top: 40px;
        }
        
        .summary-table th {
            background: #5E81AC;
            color: white;
        }
        
        .grand-total-row {
            background: #8FBCBB !important;
            color: white;
            font-size: 15px;
            font-weight: bold;
        }
        
        .grand-total-row td {
            border-top: 3px solid #2E3440;
            padding: 15px 10px;
        }
        
        @media print {
            body { padding: 20px; }
            .no-print { display: none; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }
    </style>
</head>
<body onload="setTimeout(function(){ window.print(); }, 1500);">

    <div class="no-print" style="text-align:center; background:#ECEFF4; padding:15px; border-radius:8px; margin-bottom:20px;">
        <p style="color:#5E81AC; font-weight:600;">⏳ Loading report and charts... Print dialog will open automatically.</p>
    </div>

    <!-- HEADER -->
    <div class="header">
        <h1><?php echo $reportTitle; ?></h1>
        <h2><?php echo htmlspecialchars($stallName); ?></h2>
        <p class="period">Period: <?php echo $periodLabel; ?></p>
    </div>

    <!-- DETAILED SALES REPORT -->
    <h3 class="section-title">Detailed Sales Breakdown</h3>
    <table>
        <thead>
            <tr>
                <?php foreach ($columns as $col): ?>
                    <th class="<?php echo in_array($col, ['Quantity', 'Subtotal']) ? 'text-right' : ''; ?>">
                        <?php echo $columnHeaders[$col]; ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reportData)): ?>
                <tr><td colspan="<?php echo count($columns); ?>" class="text-center">No sales data for this period.</td></tr>
            <?php else: ?>
                <?php 
                // Track previous values for smart break logic
                $prevProduct = null;
                $prevDate = null;
                $prevOrderId = null;
                $prevCustomer = null;
                
                // Determine the primary sort column (first column that's not Qty/Subtotal)
                $primaryColumn = $columns[0];
                
                foreach ($reportData as $row): 
                    $currentProduct = htmlspecialchars($row['ProductName']);
                    $currentDate = date('d/m/Y', strtotime($row['OrderDate']));
                    $currentOrderId = '#' . $row['OrderId'];
                    $currentCustomer = htmlspecialchars($row['CustomerName']);
                    
                    // Determine what changed (for resetting subsequent breaks)
                    $productChanged = ($prevProduct !== $currentProduct);
                    $dateChanged = ($prevDate !== $currentDate);
                    $orderChanged = ($prevOrderId !== $currentOrderId);
                    $customerChanged = ($prevCustomer !== $currentCustomer);
                ?>
                <tr>
                    <?php foreach ($columns as $col): ?>
                        <td class="<?php echo in_array($col, ['Quantity', 'Subtotal']) ? 'text-right' : ''; ?>">
                            <?php 
                            // Format the value
                            if ($col === 'OrderId') {
                                $displayValue = $currentOrderId;
                            } elseif ($col === 'OrderDate') {
                                $displayValue = $currentDate;
                            } elseif ($col === 'Subtotal') {
                                $displayValue = 'RM ' . number_format($row[$col], 2);
                            } elseif ($col === 'CustomerName') {
                                $displayValue = $currentCustomer;
                            } elseif ($col === 'ProductName') {
                                $displayValue = $currentProduct;
                            } else {
                                $displayValue = htmlspecialchars($row[$col]);
                            }
                            
                            // Context-Aware BREAK Logic based on column order
                            if ($col === 'ProductName') {
                                // If Product is first column, apply break
                                if ($primaryColumn === 'ProductName') {
                                    echo ($productChanged) ? $displayValue : '';
                                } else {
                                    // Product not primary, always show
                                    echo $displayValue;
                                }
                            }
                            elseif ($col === 'OrderDate') {
                                // Break date based on primary column
                                if ($primaryColumn === 'ProductName') {
                                    // Show date when product OR date changes
                                    echo ($productChanged || $dateChanged) ? $displayValue : '';
                                } elseif ($primaryColumn === 'OrderDate') {
                                    // Date is primary, break on date
                                    echo ($dateChanged) ? $displayValue : '';
                                } elseif ($primaryColumn === 'CustomerName') {
                                    // Show date when customer OR date changes
                                    echo ($customerChanged || $dateChanged) ? $displayValue : '';
                                } else {
                                    echo $displayValue;
                                }
                            }
                            elseif ($col === 'OrderId') {
                                // OrderId breaks on order change, but resets on higher hierarchy changes
                                if ($primaryColumn === 'ProductName') {
                                    echo ($productChanged || $dateChanged || $orderChanged) ? $displayValue : '';
                                } elseif ($primaryColumn === 'CustomerName') {
                                    echo ($customerChanged || $dateChanged || $orderChanged) ? $displayValue : '';
                                } else {
                                    echo ($orderChanged) ? $displayValue : '';
                                }
                            }
                            elseif ($col === 'CustomerName') {
                                // Customer shows when order changes (unless it's primary column)
                                if ($primaryColumn === 'CustomerName') {
                                    echo ($customerChanged) ? $displayValue : '';
                                } elseif ($primaryColumn === 'ProductName') {
                                    echo ($productChanged || $dateChanged || $orderChanged) ? $displayValue : '';
                                } else {
                                    echo ($orderChanged) ? $displayValue : '';
                                }
                            }
                            else {
                                // Always show: Quantity, Subtotal
                                echo $displayValue;
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php 
                    // Update tracking variables
                    $prevProduct = $currentProduct;
                    $prevDate = $currentDate;
                    $prevOrderId = $currentOrderId;
                    $prevCustomer = $currentCustomer;
                endforeach; 
                ?>
                
                <tr class="total-row">
                    <td colspan="<?php echo count($columns) - 1; ?>" class="text-right">TOTAL</td>
                    <td class="text-right">RM <?php echo number_format($grandTotal, 2); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($summaryData)): ?>
    <!-- HISTOGRAM -->
    <h3 class="section-title" style="margin-top:50px;">Top Selling Products</h3>
    <div class="chart-container">
        <canvas id="salesChart"></canvas>
    </div>

    <!-- PRODUCT SUMMARY TABLE -->
    <table class="summary-table">
        <thead>
            <tr>
                <th width="40%">Product Name</th>
                <th width="15%" class="text-center">Qty Sold</th>
                <th width="20%" class="text-right">Unit Price</th>
                <th width="25%" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $summaryGrandTotal = 0;
            foreach ($summaryData as $product): 
                $summaryGrandTotal += $product['TotalSales'];
            ?>
            <tr>
                <td><?php echo htmlspecialchars($product['ProductName']); ?></td>
                <td class="text-center"><?php echo $product['TotalQty']; ?></td>
                <td class="text-right">RM <?php echo number_format($product['UnitPrice'], 2); ?></td>
                <td class="text-right">RM <?php echo number_format($product['TotalSales'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="grand-total-row">
                <td colspan="3" class="text-right">GRAND TOTAL</td>
                <td class="text-right">RM <?php echo number_format($summaryGrandTotal, 2); ?></td>
            </tr>
        </tbody>
    </table>
    <?php endif; ?>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Units Sold',
                    data: <?php echo json_encode($chartValues); ?>,
                    backgroundColor: 'rgba(143, 188, 187, 0.7)',
                    borderColor: '#8FBCBB',
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 800 },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { 
                            precision: 0,
                            font: { size: 12 }
                        },
                        title: {
                            display: true,
                            text: 'Quantity Sold',
                            font: { size: 13, weight: 'bold' }
                        },
                        grid: { color: '#E5E9F0' }
                    },
                    x: {
                        ticks: { font: { size: 11 } },
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Product Sales Distribution',
                        font: { size: 16, weight: 'bold' },
                        color: '#2E3440',
                        padding: 20
                    }
                }
            }
        });
    </script>
</body>
</html>