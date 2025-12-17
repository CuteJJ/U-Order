<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'vendor') {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// 1. Get Vendor's Stall ID
$stmt = $db->prepare("SELECT StallId, StallName FROM stalls WHERE StaffId = :uid");
$stmt->execute([':uid' => $userId]);
$stall = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$stall) { echo "No stall found."; exit; }
$stallId = $stall['StallId'];
$stallName = $stall['StallName'];

// 2. Fetch Dropdowns
$products = $db->prepare("SELECT ProductId, ProductName FROM products WHERE StallId = :sid");
$products->execute([':sid' => $stallId]);
$productList = $products->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Filters
$search = $_GET['search'] ?? '';
$filterFood = $_GET['food_id'] ?? '';
$viewMode = $_GET['view_mode'] ?? 'custom'; 

// Set Dates
if ($viewMode === 'daily') {
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d');
} elseif ($viewMode === 'monthly') {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
} else {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
}

// --- LOGIC: DETERMINE LAYOUT MODE (For Web View sorting) ---
$layoutMode = 'default'; 
if (!empty($filterFood)) {
    $layoutMode = 'food'; 
} elseif (!empty($search)) {
    $layoutMode = 'search';
}

// Base Conditions
$baseWhere = "WHERE o.StallId = :sid AND DATE(o.CreatedAt) BETWEEN :start AND :end";
$params = [':sid' => $stallId, ':start' => $startDate, ':end' => $endDate];

// --- APPLY FILTERS (ADDITIVE) ---

// 1. Search Filter
if (!empty($search)) { 
    $baseWhere .= " AND (u.Name LIKE :search OR p.ProductName LIKE :search)"; 
    $params[':search'] = "%$search%"; 
}

// 2. Food Filter
if (!empty($filterFood)) { 
    $baseWhere .= " AND ol.ProductId = :pid"; 
    $params[':pid'] = $filterFood;
}

// =================================================================
//  PDF PRINT VIEW (PRODUCT SALES SUMMARY)
//  This replaces the old detailed list with a summary table.
// =================================================================
if (isset($_GET['print_view'])) {
    // 1. Aggregated Query: Group by Product
    // We sum up quantity and subtotal for each product in the selected range
    // NOTE: This ignores the 'search' filter usually, as summaries are typically for all products or specific food filter.
    // If you want search to apply, keep $baseWhere.
    
    $sql = "SELECT p.ProductName, p.UnitPrice, 
                   SUM(ol.Quantity) as TotalQty, 
                   SUM(ol.Subtotal) as TotalSales
            FROM orders o
            JOIN orderitems ol ON o.OrderId = ol.OrderId
            JOIN products p ON ol.ProductId = p.ProductId
            JOIN users u ON o.UserId = u.UserId
            $baseWhere
            GROUP BY p.ProductId
            ORDER BY TotalQty DESC"; // Sort by highest quantity sold
            
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $summaryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Prepare Data for Histogram (Product Name vs Quantity)
    $chartLabels = [];
    $chartValues = [];
    $grandTotalSales = 0;
    
    foreach($summaryData as $row) {
        $chartLabels[] = $row['ProductName'];
        $chartValues[] = $row['TotalQty'];
        $grandTotalSales += $row['TotalSales'];
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Sales Summary - <?php echo $stallName; ?></title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <style>
            body { font-family: "Segoe UI", sans-serif; color: #333; max-width: 800px; margin: 0 auto; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
            .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; }
            .header p { margin: 5px 0; font-size: 14px; color: #666; }
            
            .chart-box { width: 100%; height: 300px; margin-bottom: 40px; page-break-inside: avoid; }
            
            table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
            th { background: #f4f4f4; border-bottom: 2px solid #333; padding: 10px; text-align: left; text-transform: uppercase; font-weight: bold; }
            td { border-bottom: 1px solid #ddd; padding: 10px; vertical-align: middle; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            
            .total-row { font-weight: bold; background: #eef; font-size: 15px; }
            .total-row td { border-top: 2px solid #333; }
            
            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body onload="setTimeout(function(){window.print()}, 1000)">
        
        <div class="no-print" style="margin-bottom:20px; text-align:center; background:#eee; padding:10px;">
            <p>Wait for chart to load...</p>
        </div>

        <div class="header">
            <h1>Product Sales Summary</h1>
            <p><strong><?php echo htmlspecialchars($stallName); ?></strong></p>
            <p>Period: <?php echo date('d M Y', strtotime($startDate)); ?> - <?php echo date('d M Y', strtotime($endDate)); ?></p>
        </div>

        <!-- HISTOGRAM: Quantity Sold per Product -->
        <div class="chart-box">
            <canvas id="pdfChart"></canvas>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="40%">Product</th>
                    <th width="20%" class="text-center">Qty Sold</th>
                    <th width="20%" class="text-right">Unit Price</th>
                    <th width="20%" class="text-right">Sub Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($summaryData)): ?>
                    <tr><td colspan="4" class="text-center">No sales in this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($summaryData as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['ProductName']); ?></td>
                        <td class="text-center"><?php echo $row['TotalQty']; ?></td>
                        <td class="text-right">RM <?php echo number_format($row['UnitPrice'], 2); ?></td>
                        <td class="text-right">RM <?php echo number_format($row['TotalSales'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3" class="text-right">GRAND TOTAL</td>
                        <td class="text-right">RM <?php echo number_format($grandTotalSales, 2); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <script>
            const ctx = document.getElementById('pdfChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($chartLabels); ?>,
                    datasets: [{
                        label: 'Quantity Sold',
                        data: <?php echo json_encode($chartValues); ?>,
                        backgroundColor: 'rgba(163, 190, 140, 0.7)', // Greenish
                        borderColor: '#8FBCBB',
                        borderWidth: 1,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    scales: { 
                        y: { 
                            beginAtZero: true,
                            ticks: { precision: 0 }, // Ensure whole numbers for Qty
                            title: { display: true, text: 'Units Sold' }
                        } 
                    },
                    plugins: { 
                        legend: { display: false },
                        title: { display: true, text: 'Top Selling Products (Qty)' }
                    }
                }
            });
        </script>
    </body>
    </html>
    <?php
    exit; // Stop here so web report code doesn't run
}

// =================================================================
//  WEB VIEW LOGIC (Detailed List - No Changes to your Sort Logic)
// =================================================================

// --- SORTING LOGIC ---
$sortClauses = [];

// Priority 1: Filtered Food First
if (!empty($filterFood)) {
    $sortClauses[] = "(CASE WHEN ol.ProductId = :pid THEN 0 ELSE 1 END) ASC";
}

// Priority 2: Search Match First
if (!empty($search)) {
    $sortClauses[] = "(CASE WHEN (u.Name LIKE :search OR p.ProductName LIKE :search) THEN 0 ELSE 1 END) ASC";
}

// Priority 3: Standard Chronological
$sortClauses[] = "o.CreatedAt DESC";
$sortClauses[] = "o.OrderId DESC";

$orderBy = "ORDER BY " . implode(", ", $sortClauses);

// 1. Calculate Pagination
$countSql = "SELECT COUNT(*) as total_records, SUM(ol.Subtotal) as grand_total
             FROM orders o
             JOIN orderitems ol ON o.OrderId = ol.OrderId
             JOIN products p ON ol.ProductId = p.ProductId
             JOIN users u ON o.UserId = u.UserId
             $baseWhere";

$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$totals = $stmtCount->fetch(PDO::FETCH_ASSOC);

$totalRecords = $totals['total_records'] ?? 0;
$grandTotalRevenue = $totals['grand_total'] ?? 0;

$limit = 10; // 10 Records per page
$totalPages = ceil($totalRecords / $limit);

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
}

$offset = ($page - 1) * $limit;
if ($offset < 0) $offset = 0;

// 2. Fetch Data with LIMIT
$sql = "SELECT o.OrderId, o.CreatedAt, p.ProductName, ol.Quantity, ol.Subtotal, u.Name as CustomerName, ol.ProductId
        FROM orders o
        JOIN orderitems ol ON o.OrderId = ol.OrderId
        JOIN products p ON ol.ProductId = p.ProductId
        JOIN users u ON o.UserId = u.UserId
        $baseWhere
        $orderBy
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// EXCEL EXPORT (Detailed List)
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    // Re-run query without limit for export
    $sqlExport = "SELECT o.OrderId, o.CreatedAt, p.ProductName, ol.Quantity, ol.Subtotal, u.Name as CustomerName
            FROM orders o
            JOIN orderitems ol ON o.OrderId = ol.OrderId
            JOIN products p ON ol.ProductId = p.ProductId
            JOIN users u ON o.UserId = u.UserId
            $baseWhere
            $orderBy";
    $stmtExp = $db->prepare($sqlExport);
    $stmtExp->execute($params);
    $exportData = $stmtExp->fetchAll(PDO::FETCH_ASSOC);

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"vendor_report.xls\"");
    
    // Header changes based on mode
    if ($layoutMode === 'food') {
        echo "<table border='1'><thead><tr><th>Product</th><th>Date</th><th>Order ID</th><th>Customer</th><th>Qty</th><th>Amount</th></tr></thead><tbody>";
    } elseif ($layoutMode === 'search') {
        echo "<table border='1'><thead><tr><th>Match</th><th>Date</th><th>Order ID</th><th>Info</th><th>Qty</th><th>Amount</th></tr></thead><tbody>";
    } else {
        echo "<table border='1'><thead><tr><th>Date</th><th>Order ID</th><th>Customer</th><th>Product</th><th>Qty</th><th>Amount</th></tr></thead><tbody>";
    }
    
    foreach ($exportData as $row) { 
        $d = $row['CreatedAt']; $oid = $row['OrderId']; $cust = $row['CustomerName']; $prod = $row['ProductName']; $q = $row['Quantity']; $s = $row['Subtotal'];
        
        if ($layoutMode === 'food') {
            echo "<tr><td>$prod</td><td>$d</td><td>$oid</td><td>$cust</td><td>$q</td><td>$s</td></tr>"; 
        } elseif ($layoutMode === 'search') {
            echo "<tr><td>$cust / $prod</td><td>$d</td><td>$oid</td><td>-</td><td>$q</td><td>$s</td></tr>"; 
        } else {
            echo "<tr><td>$d</td><td>$oid</td><td>$cust</td><td>$prod</td><td>$q</td><td>$s</td></tr>"; 
        }
    }
    echo "</tbody></table>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Reports</title>
    <link rel="stylesheet" href="../assets/css/aurora_theme.css">
    <style>
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .page-link { padding: 8px 12px; border: 1px solid #D8DEE9; background: white; border-radius: 4px; color: #2E3440; }
        .page-link.active { background: #B48EAD; color: white; border-color: #B48EAD; }
        
        .preset-btn { 
            padding: 6px 12px; border: 1px solid #D8DEE9; background: #fff; 
            border-radius: 4px; cursor: pointer; font-size: 0.9em; text-decoration:none; color:#2E3440;
        }
        .preset-btn.active { background: #88C0D0; color: white; border-color: #88C0D0; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header & Nav -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <a href="vendor_dashboard.php">&larr; Back</a>
            <div>
                 <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn" style="background:#A3BE8C; color:white;">Export Excel</a>
                 <a href="?<?php echo http_build_query(array_merge($_GET, ['print_view' => '1'])); ?>" target="_blank" class="btn btn-secondary">🖨️ Export PDF</a>
            </div>
        </div>
        
        <!-- Filter Form -->
        <form method="GET" action="" class="filter-bar">
            <input type="hidden" name="page" value="1">
            
            <div style="margin-bottom: 15px; display:flex; gap:10px;">
                <strong>Quick Filters:</strong>
                <a href="?view_mode=daily" class="preset-btn <?php echo $viewMode=='daily'?'active':''; ?>">Today</a>
                <a href="?view_mode=monthly" class="preset-btn <?php echo $viewMode=='monthly'?'active':''; ?>">This Month</a>
                <a href="?view_mode=custom" class="preset-btn <?php echo $viewMode=='custom'?'active':''; ?>">Custom</a>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items:end;">
                <div><label>From</label><input type="date" name="start_date" value="<?php echo $startDate; ?>"></div>
                <div><label>To</label><input type="date" name="end_date" value="<?php echo $endDate; ?>"></div>
                <div><label>Food Item</label>
                    <select name="food_id">
                        <option value="">All Items</option>
                        <?php foreach($productList as $id => $name): echo "<option value='$id' " . ($filterFood == $id ? 'selected' : '') . ">$name</option>"; endforeach; ?>
                    </select>
                </div>
                <div><label>Search</label><input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Customer or Food..."></div>
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <?php if ($layoutMode === 'food'): ?>
                            <th width="30%">Product</th>
                            <th width="20%">Date</th>
                            <th width="10%">Order ID</th>
                            <th width="20%">Customer</th>
                        <?php elseif ($layoutMode === 'search'): ?>
                            <th width="25%">Match (Cust/Prod)</th>
                            <th width="20%">Date</th>
                            <th width="10%">Order ID</th>
                            <th width="25%">Other Info</th>
                        <?php else: /* Default */ ?>
                            <th width="20%">Date</th>
                            <th width="10%">Order ID</th>
                            <th width="20%">Customer</th>
                            <th width="30%">Product</th>
                        <?php endif; ?>
                        <th width="5%">Qty</th>
                        <th width="15%" style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="6" style="text-align:center;">No records.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportData as $row): 
                            $currOrder = $row['OrderId'];
                            $currCust = $row['CustomerName'];
                            
                            $dateStr = date('Y-m-d H:i', strtotime($row['CreatedAt']));
                            $custStr = htmlspecialchars($row['CustomerName']);
                            $prodStr = htmlspecialchars($row['ProductName']);
                            $idStr   = '#'.$currOrder;

                            // Display Logic (WEB VIEW - NO GROUPING / FULL DISPLAY)
                            // We determine column order, but display full content.
                            
                            if ($layoutMode === 'food') {
                                $d1 = $prodStr; 
                                $d2 = $dateStr;
                                $d3 = $idStr;
                                $d4 = $custStr;
                            } elseif ($layoutMode === 'search') {
                                // Search Layout: If Product matches, show Product first
                                $matchedProd = (stripos($prodStr, $search) !== false);
                                if ($matchedProd) {
                                     $d1 = $prodStr;
                                     $d4 = $custStr;
                                } else {
                                     $d1 = $custStr;
                                     $d4 = $prodStr;
                                }
                                $d2 = $dateStr;
                                $d3 = $idStr;
                            } else { 
                                // Default Layout
                                $d1 = $dateStr;
                                $d2 = $idStr;
                                $d3 = $custStr;
                                $d4 = $prodStr;
                            }
                        ?>
                        <tr>
                            <!-- No dim-text class, display full data -->
                            <td><?php echo $d1; ?></td>
                            <td><?php echo $d2; ?></td>
                            <td><?php echo $d3; ?></td>
                            <td><?php echo $d4; ?></td>
                            
                            <td><?php echo $row['Quantity']; ?></td>
                            <td style="text-align:right;">RM <?php echo number_format($row['Subtotal'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background:#B48EAD; color:white;">
                            <td colspan="5" style="text-align:right; font-weight:bold;">TOTAL</td>
                            <td style="font-weight:bold; text-align:right;">RM <?php echo number_format($grandTotalRevenue, 2); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php 
                $prevParams = array_merge($_GET, ['page' => $page - 1]);
                $nextParams = array_merge($_GET, ['page' => $page + 1]);
            ?>
            <a href="?<?php echo http_build_query($prevParams); ?>" class="page-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>">&larr; Prev</a>
            <span style="padding: 8px 12px;">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            <a href="?<?php echo http_build_query($nextParams); ?>" class="page-link <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">Next &rarr;</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>