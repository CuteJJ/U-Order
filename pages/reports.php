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
$viewMode = $_GET['view_mode'] ?? 'custom';

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

$search = $_GET['search'] ?? '';
$filterStall = $_GET['stall_id'] ?? '';
$filterCat = $_GET['category_id'] ?? '';

// Base Conditions for Product Summary
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
// PRODUCT SALES SUMMARY QUERY
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
        ORDER BY TotalSales DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Grand Total
$grandTotalQty = array_sum(array_column($reportData, 'TotalQty'));
$grandTotalSales = array_sum(array_column($reportData, 'TotalSales'));

// EXCEL EXPORT
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"product_sales_summary.xls\"");
    
    echo "<table border='1'><thead><tr><th>Stall</th><th>Product</th><th>Category</th><th>Unit Price</th><th>Qty Sold</th><th>Total Sales</th></tr></thead><tbody>";
    foreach ($reportData as $row) { 
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['StallName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['ProductName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['CategoryName'] ?? 'N/A') . "</td>";
        echo "<td>" . number_format($row['UnitPrice'], 2) . "</td>";
        echo "<td>" . $row['TotalQty'] . "</td>";
        echo "<td>" . number_format($row['TotalSales'], 2) . "</td>";
        echo "</tr>"; 
    }
    echo "<tr style='font-weight:bold;'><td colspan='4'>GRAND TOTAL</td><td>{$grandTotalQty}</td><td>" . number_format($grandTotalSales, 2) . "</td></tr>";
    echo "</tbody></table>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Sales Report</title>
    <link rel="stylesheet" href="../assets/css/aurora_theme.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #88C0D0 0%, #5E81AC 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
        }
        .preset-btn { 
            padding: 6px 12px;
            border: 1px solid #D8DEE9;
            background: #fff;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            text-decoration: none;
            color: #2E3440;
            display: inline-block;
        }
        .preset-btn.active { 
            background: #88C0D0;
            color: white;
            border-color: #88C0D0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="admin_dashboard.php">&larr; Dashboard</a>
        </div>
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0;">Product Sales Summary</h2>
            <div>
                 <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn" style="background:#A3BE8C; color:white;">Export Excel</a>
                 <a href="admin_printed_report.php?<?php echo http_build_query($_GET); ?>" target="_blank" class="btn btn-secondary">🖨️ Export PDF</a>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Products Sold</h3>
                <p class="value"><?php echo number_format($grandTotalQty); ?></p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #A3BE8C 0%, #8FBCBB 100%);">
                <h3>Total Revenue</h3>
                <p class="value">RM <?php echo number_format($grandTotalSales, 2); ?></p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #B48EAD 0%, #D08770 100%);">
                <h3>Unique Products</h3>
                <p class="value"><?php echo count($reportData); ?></p>
            </div>
        </div>
        
        <!-- Filters -->
        <form method="GET" action="" class="filter-bar">
            <input type="hidden" name="page" value="1">
            
            <div style="margin-bottom: 15px; display:flex; gap:10px; align-items: center;">
                <strong>Quick Filters:</strong>
                <a href="?view_mode=daily" class="preset-btn <?php echo $viewMode=='daily'?'active':''; ?>">Today</a>
                <a href="?view_mode=monthly" class="preset-btn <?php echo $viewMode=='monthly'?'active':''; ?>">This Month</a>
                <a href="?view_mode=custom" class="preset-btn <?php echo $viewMode=='custom'?'active':''; ?>">Custom</a>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items:end;">
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
                <div><label>Search</label><input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Product or Stall..."></div>
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th width="20%">Stall</th>
                        <th width="25%">Product</th>
                        <th width="15%">Category</th>
                        <th width="12%" class="text-right">Unit Price</th>
                        <th width="10%" class="text-center">Qty Sold</th>
                        <th width="18%" class="text-right">Total Sales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="6" style="text-align:center; padding: 30px;">No sales data for this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportData as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['StallName']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['ProductName']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['CategoryName'] ?? 'N/A'); ?></td>
                            <td class="text-right">RM <?php echo number_format($row['UnitPrice'], 2); ?></td>
                            <td class="text-center"><?php echo $row['TotalQty']; ?></td>
                            <td class="text-right"><strong>RM <?php echo number_format($row['TotalSales'], 2); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background:#B48EAD; color:white;">
                            <td colspan="4" style="text-align:right; font-weight:bold;">GRAND TOTAL</td>
                            <td class="text-center" style="font-weight:bold;"><?php echo number_format($grandTotalQty); ?></td>
                            <td class="text-right" style="font-weight:bold;">RM <?php echo number_format($grandTotalSales, 2); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>