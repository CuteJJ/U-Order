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

// Base Conditions
$baseWhere = "WHERE o.StallId = :sid AND DATE(o.CreatedAt) BETWEEN :start AND :end";
$params = [':sid' => $stallId, ':start' => $startDate, ':end' => $endDate];

// --- APPLY FILTERS (ADDITIVE) ---
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
$columnHeaders = [
    'OrderDate' => 'Date',
    'OrderId' => 'Order ID',
    'CustomerName' => 'Customer',
    'ProductName' => 'Product',
    'Quantity' => 'Qty',
    'Subtotal' => 'Subtotal'
];

if ($viewMode === 'daily') {
    // TODAY: No Date column, sort by OrderId
    $columns = ['OrderId', 'CustomerName', 'ProductName', 'Quantity', 'Subtotal'];
    $orderBy = ['o.OrderId ASC'];
    
} elseif (!empty($filterFood)) {
    // FOOD FILTER: ProductName, Date, OrderId, CustomerName
    $columns = ['ProductName', 'OrderDate', 'OrderId', 'CustomerName', 'Quantity', 'Subtotal'];
    $orderBy = ['p.ProductName ASC', 'DATE(o.CreatedAt) ASC', 'o.OrderId ASC'];
    
} elseif (!empty($search)) {
    // SEARCH: Determine if searching for customer or food
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
    } else {
        // Food Search: ProductName, Date, OrderId, CustomerName
        $columns = ['ProductName', 'OrderDate', 'OrderId', 'CustomerName', 'Quantity', 'Subtotal'];
        $orderBy = ['p.ProductName ASC', 'DATE(o.CreatedAt) ASC', 'o.OrderId ASC'];
    }
    
} else {
    // DEFAULT (Monthly/Custom): Date, OrderId, CustomerName, ProductName
    $columns = ['OrderDate', 'OrderId', 'CustomerName', 'ProductName', 'Quantity', 'Subtotal'];
    $orderBy = ['DATE(o.CreatedAt) ASC', 'o.OrderId ASC'];
}

$orderByClause = 'ORDER BY ' . implode(', ', $orderBy);

// 1. Calculate Pagination
$countSql = "SELECT 
                COUNT(*) as total_records, 
                SUM(ol.Subtotal) as grand_total,
                SUM(ol.Quantity) as grand_qty
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
$grandTotalQty = $totals['grand_qty'] ?? 0;

$limit = 10; // 10 Records per page
$totalPages = ceil($totalRecords / $limit);

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
}

$offset = ($page - 1) * $limit;
if ($offset < 0) $offset = 0;

// 2. Fetch Data with LIMIT
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
        $orderByClause
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// EXCEL EXPORT (Detailed List)
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    // Re-run query without limit for export
    $sqlExport = "SELECT 
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
    $stmtExp = $db->prepare($sqlExport);
    $stmtExp->execute($params);
    $exportData = $stmtExp->fetchAll(PDO::FETCH_ASSOC);

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"vendor_report.xls\"");
    
    // Dynamic header based on columns
    echo "<table border='1'><thead><tr>";
    foreach ($columns as $col) {
        echo "<th>" . $columnHeaders[$col] . "</th>";
    }
    echo "</tr></thead><tbody>";
    
    foreach ($exportData as $row) { 
        echo "<tr>";
        foreach ($columns as $col) {
            if ($col === 'OrderId') {
                echo "<td>#" . $row[$col] . "</td>";
            } elseif ($col === 'OrderDate') {
                echo "<td>" . date('d/m/Y', strtotime($row[$col])) . "</td>";
            } elseif ($col === 'Subtotal') {
                echo "<td>" . number_format($row[$col], 2) . "</td>";
            } else {
                echo "<td>" . htmlspecialchars($row[$col]) . "</td>";
            }
        }
        echo "</tr>";
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
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <a href="vendor_dashboard.php">&larr; Back</a>
            <div>
                 <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn" style="background:#A3BE8C; color:white;">Export Excel</a>
                 <a href="vendor_printed_report.php?<?php echo http_build_query($_GET); ?>" target="_blank" class="btn btn-secondary">🖨️ Export PDF</a>
            </div>
        </div>
        
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
                        <?php foreach ($columns as $col): ?>
                            <th class="<?php echo in_array($col, ['Quantity', 'Subtotal']) ? 'text-right' : ''; ?>">
                                <?php echo $columnHeaders[$col]; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="<?php echo count($columns); ?>" style="text-align:center;">No records.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportData as $row): ?>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <td class="<?php echo in_array($col, ['Quantity', 'Subtotal']) ? 'text-right' : ''; ?>">
                                    <?php 
                                    if ($col === 'OrderId') {
                                        echo '#' . $row[$col];
                                    } elseif ($col === 'OrderDate') {
                                        echo date('d/m/Y', strtotime($row[$col]));
                                    } elseif ($col === 'Subtotal') {
                                        echo 'RM ' . number_format($row[$col], 2);
                                    } else {
                                        echo htmlspecialchars($row[$col]);
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background:#B48EAD; color:white;">
                            <?php 
                                // Calculate colspan: Total cols minus Quantity(1) minus Subtotal(1) = count - 2
                                $colspan = max(1, count($columns) - 2);
                            ?>
                            <td colspan="<?php echo $colspan; ?>" style="text-align:right; font-weight:bold;">GRAND TOTAL</td>
                            <td class="text-right" style="font-weight:bold;"><?php echo number_format($grandTotalQty); ?></td>
                            <td class="text-right" style="font-weight:bold;">RM <?php echo number_format($grandTotalRevenue, 2); ?></td>
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