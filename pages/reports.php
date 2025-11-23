<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch Dropdowns
$stalls = $db->query("SELECT StallId, StallName FROM stalls")->fetchAll(PDO::FETCH_KEY_PAIR);
$categories = $db->query("SELECT CategoryId, CategoryName FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);

// Filters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';
$filterStall = $_GET['stall_id'] ?? '';
$filterCat = $_GET['category_id'] ?? '';

// Base Conditions
$baseWhere = "WHERE DATE(o.CreatedAt) BETWEEN :start AND :end";
$params = [':start' => $startDate, ':end' => $endDate];

if (!empty($search)) { 
    $baseWhere .= " AND (p.ProductName LIKE :search OR u.Name LIKE :search)"; 
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

// --- SPECIAL PRINT VIEW LOGIC (No Pagination) ---
if (isset($_GET['print_view'])) {
    // Query ALL data without LIMIT
    $sql = "SELECT o.OrderId, o.CreatedAt, s.StallName, c.CategoryName, p.ProductName, ol.Quantity, ol.Subtotal, u.Name as CustomerName
            FROM orders o
            JOIN orderlists ol ON o.OrderId = ol.OrderId
            JOIN products p ON ol.ProductId = p.ProductId
            JOIN stalls s ON o.StallId = s.StallId
            JOIN users u ON o.UserId = u.UserId
            LEFT JOIN categories c ON p.CategoryId = c.CategoryId
            $baseWhere
            ORDER BY o.CreatedAt DESC";
            
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate Totals
    $totalRevenue = 0;
    foreach($reportData as $row) $totalRevenue += $row['Subtotal'];
    $totalCount = count($reportData);
    
    // Output Clean HTML for Print
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Sales Report (Full)</title>
        <style>
            body { font-family: sans-serif; font-size: 12px; color: #333; }
            .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
            th { background: #eee; font-weight: bold; }
            .totals { margin-top: 20px; text-align: right; font-size: 14px; }
            .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
        </style>
    </head>
    <body onload="window.print()">
        <div class="header">
            <h2 style="margin:0;">Sales Report</h2>
            <p>Period: <?php echo $startDate; ?> to <?php echo $endDate; ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Date</th><th>ID</th><th>Stall</th><th>Product</th><th>Qty</th><th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData as $row): ?>
                <tr>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['CreatedAt'])); ?></td>
                    <td>#<?php echo $row['OrderId']; ?></td>
                    <td><?php echo htmlspecialchars($row['StallName']); ?></td>
                    <td><?php echo htmlspecialchars($row['ProductName']); ?></td>
                    <td><?php echo $row['Quantity']; ?></td>
                    <td>RM <?php echo number_format($row['Subtotal'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <p><strong>Total Orders:</strong> <?php echo $totalCount; ?></p>
            <p><strong>Grand Total Revenue:</strong> RM <?php echo number_format($totalRevenue, 2); ?></p>
        </div>

        <div class="footer">
            Generated on <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </body>
    </html>
    <?php
    exit; // Stop script here for print view
}

// --- NORMAL PAGINATION LOGIC (For Web View) ---
$countSql = "SELECT COUNT(*) as total_records, SUM(ol.Subtotal) as grand_total
             FROM orders o
             JOIN orderlists ol ON o.OrderId = ol.OrderId
             JOIN products p ON ol.ProductId = p.ProductId
             JOIN stalls s ON o.StallId = s.StallId
             JOIN users u ON o.UserId = u.UserId
             LEFT JOIN categories c ON p.CategoryId = c.CategoryId
             $baseWhere";

$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$totals = $stmtCount->fetch(PDO::FETCH_ASSOC);

$totalRecords = $totals['total_records'] ?? 0;
$grandTotalRevenue = $totals['grand_total'] ?? 0;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$totalPages = ceil($totalRecords / $limit);
$offset = ($page - 1) * $limit;

$sql = "SELECT o.OrderId, o.CreatedAt, s.StallName, c.CategoryName, p.ProductName, ol.Quantity, ol.Subtotal, u.Name as CustomerName
        FROM orders o
        JOIN orderlists ol ON o.OrderId = ol.OrderId
        JOIN products p ON ol.ProductId = p.ProductId
        JOIN stalls s ON o.StallId = s.StallId
        JOIN users u ON o.UserId = u.UserId
        LEFT JOIN categories c ON p.CategoryId = c.CategoryId
        $baseWhere
        ORDER BY o.CreatedAt DESC";

if (!isset($_GET['export'])) {
    $sql .= " LIMIT $limit OFFSET $offset";
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"report.xls\"");
    echo "<table border='1'><thead><tr><th>Date</th><th>Order ID</th><th>Stall</th><th>Product</th><th>Amount</th></tr></thead><tbody>";
    foreach ($reportData as $row) { echo "<tr><td>{$row['CreatedAt']}</td><td>{$row['OrderId']}</td><td>{$row['StallName']}</td><td>{$row['ProductName']}</td><td>{$row['Subtotal']}</td></tr>"; }
    echo "</tbody></table>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <link rel="stylesheet" href="../assets/css/aurora_theme.css">
    <style>
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .page-link { padding: 8px 12px; border: 1px solid #D8DEE9; background: white; border-radius: 4px; color: #2E3440; }
        .page-link.active { background: #B48EAD; color: white; border-color: #B48EAD; }
    </style>
</head>
<body>
    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="admin_dashboard.php">&larr; Dashboard</a>
        </div>
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0;">Sales Reports</h2>
            <div>
                 <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn" style="background:#A3BE8C; color:white;">Export Excel</a>
                 
                 <!-- UPDATED BUTTON: Opens the Full Print View -->
                 <a href="?<?php echo http_build_query(array_merge($_GET, ['print_view' => '1'])); ?>" target="_blank" class="btn btn-secondary">🖨️ Print Full PDF</a>
            </div>
        </div>
        
        <form method="GET" action="" class="filter-bar form-group" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
            <input type="hidden" name="page" value="1">
            <div><label>From</label><input type="date" name="start_date" value="<?php echo $startDate; ?>"></div>
            <div><label>To</label><input type="date" name="end_date" value="<?php echo $endDate; ?>"></div>
            <div><label>Stall</label>
                <select name="stall_id">
                    <option value="">All Stalls</option>
                    <?php foreach($stalls as $id => $name): echo "<option value='$id' " . ($filterStall == $id ? 'selected' : '') . ">$name</option>"; endforeach; ?>
                </select>
            </div>
            <div><label>Category</label>
                <select name="category_id">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $id => $name): echo "<option value='$id' " . ($filterCat == $id ? 'selected' : '') . ">$name</option>"; endforeach; ?>
                </select>
            </div>
            <div><label>Search</label><input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Keywords..."></div>
            <div style="display:flex; align-items:flex-end;"><button type="submit" class="btn btn-primary" style="width:100%;">Filter Data</button></div>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th><th>Order ID</th><th>Stall</th><th>Product</th><th>Qty</th><th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="6" style="text-align:center; padding: 30px;">No records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportData as $row): ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($row['CreatedAt'])); ?> <small style="color:#666"><?php echo date('H:i', strtotime($row['CreatedAt'])); ?></small></td>
                            <td>#<?php echo $row['OrderId']; ?></td>
                            <td><?php echo htmlspecialchars($row['StallName']); ?></td>
                            <td><?php echo htmlspecialchars($row['ProductName']); ?></td>
                            <td><?php echo $row['Quantity']; ?></td>
                            <td>RM <?php echo number_format($row['Subtotal'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background:#B48EAD; color:white;">
                            <td colspan="5" style="text-align:right; font-weight:bold;">GRAND TOTAL REVENUE</td>
                            <td style="font-weight:bold;">RM <?php echo number_format($grandTotalRevenue, 2); ?></td>
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
            <?php for($i = 1; $i <= $totalPages; $i++): $pParams = array_merge($_GET, ['page' => $i]); ?>
                <a href="?<?php echo http_build_query($pParams); ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <a href="?<?php echo http_build_query($nextParams); ?>" class="page-link <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">Next &rarr;</a>
        </div>
        <div style="text-align:center; margin-top:10px; color:#666; font-size:0.9em;">
            Page <?php echo $page; ?> of <?php echo $totalPages; ?> (<?php echo $totalRecords; ?> records)
        </div>
        <?php endif; ?>
    </div>
</body>
</html>