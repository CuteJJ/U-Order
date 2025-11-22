<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --- 1. Fetch Dropdown Data ---
$stalls = $db->query("SELECT StallId, StallName FROM stalls")->fetchAll(PDO::FETCH_KEY_PAIR);
$categories = $db->query("SELECT CategoryId, CategoryName FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);

// --- 2. Handling Filter Parameters ---
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? ''; // Search text
$filterStall = $_GET['stall_id'] ?? ''; // Selected Stall ID
$filterCat = $_GET['category_id'] ?? ''; // Selected Category ID

// --- 3. Build Dynamic Query ---
$sql = "SELECT 
            o.OrderId, 
            o.CreatedAt, 
            s.StallName, 
            c.CategoryName,
            p.ProductName,
            ol.Quantity,
            ol.Subtotal,
            u.Name as CustomerName
        FROM orders o
        JOIN orderlists ol ON o.OrderId = ol.OrderId
        JOIN products p ON ol.ProductId = p.ProductId
        JOIN stalls s ON o.StallId = s.StallId
        JOIN users u ON o.UserId = u.UserId
        LEFT JOIN categories c ON p.CategoryId = c.CategoryId
        WHERE DATE(o.CreatedAt) BETWEEN :start AND :end";

$params = [':start' => $startDate, ':end' => $endDate];

// Apply Search Filter (Product Name or Customer Name)
if (!empty($search)) {
    $sql .= " AND (p.ProductName LIKE :search OR u.Name LIKE :search)";
    $params[':search'] = "%$search%";
}

// Apply Stall Filter
if (!empty($filterStall)) {
    $sql .= " AND o.StallId = :sid";
    $params[':sid'] = $filterStall;
}

// Apply Category Filter
if (!empty($filterCat)) {
    $sql .= " AND p.CategoryId = :cid";
    $params[':cid'] = $filterCat;
}

// Default Sort (Newest First)
$sql .= " ORDER BY o.CreatedAt DESC";

// Execute Query
$stmt = $db->prepare($sql);
$stmt->execute($params);
$reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Total Revenue for current view
$totalRevenue = 0;
foreach($reportData as $row) $totalRevenue += $row['Subtotal'];

// --- 4. Export Logic (Excel) ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = "sales_report_" . date('Ymd') . ".xls";
    
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<thead>
            <tr style='background-color:#f2f2f2;'>
                <th>Date</th>
                <th>Order ID</th>
                <th>Stall</th>
                <th>Category</th>
                <th>Product</th>
                <th>Customer</th>
                <th>Qty</th>
                <th>Subtotal (RM)</th>
            </tr>
          </thead><tbody>";
    
    foreach ($reportData as $row) {
        echo "<tr>";
        echo "<td>" . $row['CreatedAt'] . "</td>";
        echo "<td>" . $row['OrderId'] . "</td>";
        echo "<td>" . htmlspecialchars($row['StallName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['CategoryName'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['ProductName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['CustomerName']) . "</td>";
        echo "<td>" . $row['Quantity'] . "</td>";
        echo "<td>" . number_format($row['Subtotal'], 2) . "</td>";
        echo "</tr>";
    }
    echo "<tr><td colspan='7' align='right'><strong>TOTAL</strong></td><td><strong>" . number_format($totalRevenue, 2) . "</strong></td></tr>";
    echo "</tbody></table>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <style>
        @media print { .no-print { display: none !important; } .container { width: 100%; max-width: 100%; } }
        .filter-bar { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e9ecef; }
        .filter-row { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-weight: bold; font-size: 0.9em; color: #555; }
        .form-control { padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-width: 150px; }
        .report-table { width: 100%; border-collapse: collapse; font-size: 0.9em; }
        .report-table th, .report-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .report-table th { background-color: #4a90e2; color: white; }
        .badge { padding: 3px 8px; border-radius: 4px; background: #eee; font-size: 0.8em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print">
            <a href="admin_dashboard.php">&larr; Back to Dashboard</a>
            <h2 style="margin-top:10px;">Sales Reports</h2>
            
            <form method="GET" action="" class="filter-bar">
                <div class="filter-row">
                    <!-- Date Range -->
                    <div class="filter-group">
                        <label>From Date</label>
                        <input type="date" name="start_date" value="<?php echo $startDate; ?>" class="form-control">
                    </div>
                    <div class="filter-group">
                        <label>To Date</label>
                        <input type="date" name="end_date" value="<?php echo $endDate; ?>" class="form-control">
                    </div>

                    <!-- Dropdown Filters -->
                    <div class="filter-group">
                        <label>Filter by Stall</label>
                        <select name="stall_id" class="form-control">
                            <option value="">All Stalls</option>
                            <?php foreach($stalls as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php if($filterStall == $id) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Filter by Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php if($filterCat == $id) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Text Search -->
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Product or User Name...">
                    </div>

                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </div>
            </form>

            <div style="margin-bottom: 20px; text-align: right;">
                <!-- Export button includes all current URL parameters -->
                <a href="?<?php echo http_build_query($_GET); ?>&export=excel" class="btn" style="background:#217346; color:white;">
                    📊 Export Current View to Excel
                </a>
                <button onclick="window.print()" class="btn" style="background:#6c757d; color:white; margin-left:10px;">
                    🖨️ Print PDF
                </button>
            </div>
        </div>

        <!-- Results Table -->
        <div id="report-content">
            <p>Showing records from <strong><?php echo $startDate; ?></strong> to <strong><?php echo $endDate; ?></strong></p>
            
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Order ID</th>
                        <th>Stall</th>
                        <th>Category</th>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Qty</th>
                        <th>Subtotal (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="8" style="text-align:center; padding: 20px;">No records found matching your filters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportData as $row): ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($row['CreatedAt'])); ?> <small style="color:#888"><?php echo date('H:i', strtotime($row['CreatedAt'])); ?></small></td>
                            <td>#<?php echo $row['OrderId']; ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($row['StallName']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['CategoryName'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['ProductName']); ?></td>
                            <td><?php echo htmlspecialchars($row['CustomerName']); ?></td>
                            <td><?php echo $row['Quantity']; ?></td>
                            <td><?php echo number_format($row['Subtotal'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background:#eef; font-weight:bold;">
                            <td colspan="7" style="text-align:right;">TOTAL REVENUE</td>
                            <td>RM <?php echo number_format($totalRevenue, 2); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>