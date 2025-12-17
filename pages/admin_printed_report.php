<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch Dropdowns for filter display
$stalls = $db->query("SELECT StallId, StallName FROM stalls")->fetchAll(PDO::FETCH_KEY_PAIR);
$categories = $db->query("SELECT CategoryId, CategoryName FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get Filters from URL
$viewMode = $_GET['view_mode'] ?? 'custom';

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

$search = $_GET['search'] ?? '';
$filterStall = $_GET['stall_id'] ?? '';
$filterCat = $_GET['category_id'] ?? '';

// Base Query Conditions
$baseWhere = "WHERE DATE(o.CreatedAt) BETWEEN :start AND :end";
$params = [':start' => $startDate, ':end' => $endDate];

if (!empty($search)) {
    $baseWhere .= " AND (p.ProductName LIKE :search OR s.StallName LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($filterStall)) {
    $baseWhere .= " AND o.StallId = :sid";
    $params[':sid'] = $filterStall;
}

if (!empty($filterCat)) {
    $baseWhere .= " AND p.CategoryId = :cid";
    $params[':cid'] = $filterCat;
}

// ========================================
// FETCH PRODUCT SUMMARY
// ========================================
$sql = "SELECT 
            p.ProductName,
            s.StallName,
            c.CategoryName,
            p.UnitPrice,
            SUM(ol.Quantity) as TotalQty,
            SUM(ol.Subtotal) as TotalSales
        FROM orders o
        JOIN orderitems ol ON o.OrderId = ol.OrderId
        JOIN products p ON ol.ProductId = p.ProductId
        JOIN stalls s ON o.StallId = s.StallId
        LEFT JOIN categories c ON p.CategoryId = c.CategoryId
        $baseWhere
        GROUP BY p.ProductId, s.StallId
        ORDER BY s.StallName ASC, TotalSales DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$summaryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare Chart Data
$chartLabels = [];
$chartValues = [];
$grandTotalQty = 0;
$grandTotalSales = 0;

foreach($summaryData as $row) {
    $chartLabels[] = $row['ProductName'] . ' (' . $row['StallName'] . ')';
    $chartValues[] = $row['TotalQty'];
    $grandTotalQty += $row['TotalQty'];
    $grandTotalSales += $row['TotalSales'];
}

// Get filter display text
$filterText = [];
if (!empty($filterStall)) {
    $filterText[] = "Stall: " . $stalls[$filterStall];
}
if (!empty($filterCat)) {
    $filterText[] = "Category: " . $categories[$filterCat];
}
if (!empty($search)) {
    $filterText[] = "Search: " . htmlspecialchars($search);
}
$filterDisplay = !empty($filterText) ? implode(' | ', $filterText) : 'All Products';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Product Sales Summary Report</title>
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
        
        .header .period {
            font-size: 14px;
            color: #4C566A;
            font-weight: 500;
            margin-top: 8px;
        }
        
        .header .filters {
            font-size: 12px;
            color: #5E81AC;
            margin-top: 5px;
            font-style: italic;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
            page-break-inside: avoid;
        }
        
        .stat-box {
            background: #ECEFF4;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #5E81AC;
        }
        
        .stat-box h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: #4C566A;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        
        .stat-box .value {
            font-size: 24px;
            font-weight: bold;
            color: #2E3440;
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
            background: #5E81AC;
            color: white;
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
            vertical-align: middle;
        }
        
        tbody tr:nth-child(even) {
            background: #F9FAFB;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
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
        
        .chart-container {
            width: 100%;
            height: 400px;
            margin: 30px 0;
            page-break-inside: avoid;
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
        <h1>Product Sales Summary Report</h1>
        <p class="period">Period: <?php echo $periodLabel; ?></p>
        <?php if (!empty($filterDisplay)): ?>
            <p class="filters">Filters Applied: <?php echo $filterDisplay; ?></p>
        <?php endif; ?>
    </div>

    <!-- STATS SUMMARY -->
    <div class="stats-summary">
        <div class="stat-box">
            <h3>Total Products Sold</h3>
            <p class="value"><?php echo number_format($grandTotalQty); ?></p>
        </div>
        <div class="stat-box" style="border-left-color: #A3BE8C;">
            <h3>Total Revenue</h3>
            <p class="value">RM <?php echo number_format($grandTotalSales, 2); ?></p>
        </div>
        <div class="stat-box" style="border-left-color: #B48EAD;">
            <h3>Unique Products</h3>
            <p class="value"><?php echo count($summaryData); ?></p>
        </div>
    </div>

    <?php if (!empty($summaryData)): ?>
    <!-- HISTOGRAM -->
    <h3 class="section-title">Top Selling Products by Quantity</h3>
    <div class="chart-container">
        <canvas id="salesChart"></canvas>
    </div>

    <!-- PRODUCT SUMMARY TABLE -->
    <h3 class="section-title">Product Sales Breakdown</h3>
    <table>
        <thead>
            <tr>
                <th width="18%">Stall</th>
                <th width="25%">Product</th>
                <th width="15%">Category</th>
                <th width="12%" class="text-right">Unit Price</th>
                <th width="12%" class="text-center">Qty Sold</th>
                <th width="18%" class="text-right">Total Sales</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $prevStall = null;
            foreach ($summaryData as $product): 
                $currentStall = htmlspecialchars($product['StallName']);
                $stallChanged = ($prevStall !== $currentStall);
            ?>
            <tr>
                <td><?php echo $stallChanged ? $currentStall : ''; ?></td>
                <td><strong><?php echo htmlspecialchars($product['ProductName']); ?></strong></td>
                <td><?php echo htmlspecialchars($product['CategoryName'] ?? 'N/A'); ?></td>
                <td class="text-right">RM <?php echo number_format($product['UnitPrice'], 2); ?></td>
                <td class="text-center"><?php echo $product['TotalQty']; ?></td>
                <td class="text-right">RM <?php echo number_format($product['TotalSales'], 2); ?></td>
            </tr>
            <?php 
                $prevStall = $currentStall;
            endforeach; 
            ?>
            
            <tr class="grand-total-row">
                <td colspan="4" class="text-right">GRAND TOTAL</td>
                <td class="text-center"><?php echo number_format($grandTotalQty); ?></td>
                <td class="text-right">RM <?php echo number_format($grandTotalSales, 2); ?></td>
            </tr>
        </tbody>
    </table>
    <?php else: ?>
    <p style="text-align:center; padding:40px; color:#666;">No sales data available for this period.</p>
    <?php endif; ?>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_slice($chartLabels, 0, 15)); ?>, // Top 15 products
                datasets: [{
                    label: 'Units Sold',
                    data: <?php echo json_encode(array_slice($chartValues, 0, 15)); ?>,
                    backgroundColor: 'rgba(94, 129, 172, 0.7)',
                    borderColor: '#5E81AC',
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
                        ticks: { 
                            font: { size: 10 },
                            maxRotation: 45,
                            minRotation: 45
                        },
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Top 15 Products by Sales Volume',
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