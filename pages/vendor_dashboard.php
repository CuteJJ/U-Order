<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'vendor') {
    flash('error', 'Access Denied. Vendor only.');
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

/* 1. Vendor Stall */
$stmt = $db->prepare("SELECT StallId, StallName FROM stalls WHERE StaffId = :uid");
$stmt->execute([':uid' => $userId]);
// 用对象
$stall = $stmt->fetch(PDO::FETCH_OBJ);

if (!$stall) {
    die("Error: No stall assigned.");
}
$stallId = $stall->StallId;

/* 2. Total Revenue */
$stmt = $db->prepare("
    SELECT SUM(ol.Subtotal)
    FROM orderitems ol
    JOIN orders o ON ol.OrderId = o.OrderId
    JOIN payments p ON o.PaymentId = p.PaymentId
    WHERE o.StallId = :sid AND p.Status = 'paid'
");
$stmt->execute([':sid' => $stallId]);
$totalRevenue = $stmt->fetchColumn() ?: 0;

/* 3. Peak Hour */
$stmt = $db->prepare("
    SELECT HOUR(CreatedAt) AS OrderHour, COUNT(*) AS Count
    FROM orders
    WHERE StallId = :sid
    GROUP BY OrderHour
    ORDER BY Count DESC
    LIMIT 1
");
$stmt->execute([':sid' => $stallId]);
$peakData = $stmt->fetch(PDO::FETCH_OBJ);
$peakHour = $peakData ? date("g A", strtotime($peakData->OrderHour . ":00")) : "N/A";

/* 4. Top Category */
$stmt = $db->prepare("
    SELECT c.CategoryName, COUNT(ol.OrderListId) AS SoldCount
    FROM orderitems ol
    JOIN products p ON ol.ProductId = p.ProductId
    JOIN categories c ON p.CategoryId = c.CategoryId
    JOIN orders o ON ol.OrderId = o.OrderId
    WHERE o.StallId = :sid
    GROUP BY c.CategoryId
    ORDER BY SoldCount DESC
    LIMIT 1
");
$stmt->execute([':sid' => $stallId]);
$topCatRow = $stmt->fetch(PDO::FETCH_OBJ);
$topCat = $topCatRow ? $topCatRow->CategoryName : "N/A";

/* 5. Chart Data */
$chartSql = "
    SELECT p.ProductName, SUM(ol.Subtotal) AS ProductTotal
    FROM orderitems ol
    JOIN products p ON ol.ProductId = p.ProductId
    JOIN orders o ON ol.OrderId = o.OrderId
    JOIN payments pay ON o.PaymentId = pay.PaymentId
    WHERE o.StallId = :sid AND pay.Status = 'paid'
    GROUP BY p.ProductId
";
$chartStmt = $db->prepare($chartSql);
$chartStmt->execute([':sid' => $stallId]);
// 用对象
$chartData = $chartStmt->fetchAll(PDO::FETCH_OBJ);

$productLabels = [];
$productValues = [];
foreach ($chartData as $row) {
    $productLabels[] = $row->ProductName;
    $productValues[] = (float)$row->ProductTotal;
}

/* 6. Recent Orders */
$recentSql = "
    SELECT 
        o.OrderId,
        u.Name AS CustomerName,
        o.CreatedAt,
        o.Status,
        (
            SELECT SUM(Subtotal) 
            FROM orderitems 
            WHERE OrderId = o.OrderId
        ) AS OrderTotal
    FROM orders o
    JOIN users u ON o.UserId = u.UserId
    WHERE o.StallId = :sid
    ORDER BY o.CreatedAt DESC
    LIMIT 5
";
$stmtRecent = $db->prepare($recentSql);
$stmtRecent->execute([':sid' => $stallId]);
// 用对象
$recentOrders = $stmtRecent->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Dashboard</title>

    <link rel="stylesheet" href="../assets/css/aurora_theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .main-area {
            margin-left: 240px; /* 对齐 vendor_sidebar 宽度 */
            padding: 30px 40px;
            min-height: 100vh;
            background: #f8fafc;
        }

        .page-content {
            margin-top: 10px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #ffffff;
            padding: 24px;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
        }

        .stat-number {
            font-size: 1.9rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
        }

        .table-container {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
            margin-bottom: 20px;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-container th,
        .table-container td {
            padding: 12px 18px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.95rem;
        }
    </style>
</head>

<body>

<?php include 'vendor_sidebar.php'; ?>

<div class="main-area">

    <div class="page-content">

        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px;">
            <div>
                <h2 style="margin-bottom: 5px;">Vendor Dashboard</h2>
                <span style="color:#6b7280;">
                    Manage <strong><?= htmlspecialchars($stall->StallName); ?></strong>
                </span>
            </div>
            <div>
                <span style="margin-right: 15px; font-weight: 600;">
                    Hi, <?= htmlspecialchars($_SESSION['name']); ?>
                </span>
                <a href="logout.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.9rem;">
                    Logout
                </a>
            </div>
        </div>

        <?php flash(); ?>

        <!-- Stats -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-number">RM <?= number_format($totalRevenue, 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $peakHour; ?></div>
                <div class="stat-label">Peak Hour</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= htmlspecialchars($topCat); ?></div>
                <div class="stat-label">Top Category</div>
            </div>
        </div>

        <!-- Chart + Quick Actions -->
        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:30px; margin-bottom:30px;">

            <div class="card" style="min-height: 340px;">
                <h4 style="margin-bottom:16px;">Sales by Food Item</h4>
                <canvas id="foodChart"></canvas>
            </div>

            <div class="card" style="display:flex; flex-direction:column; gap:12px;">
                <h4 style="margin-bottom:8px;">Quick Actions</h4>
                <a href="vendor_reports.php" class="btn btn-admin primary">View Reports</a>
                <a href="vendor_menu.php" class="btn btn-admin">View My Menu</a>
            </div>

        </div>

        <!-- Recent Orders -->
        <div class="table-container">
            <div style="padding: 16px 18px; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin:0;">Recent Orders</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:20px;">No recent orders.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td>#<?= $o->OrderId; ?></td>
                                <td><?= htmlspecialchars($o->CustomerName); ?></td>
                                <td><?= date('d M H:i', strtotime($o->CreatedAt)); ?></td>
                                <td><?= ucfirst($o->Status); ?></td>
                                <td>RM <?= number_format($o->OrderTotal, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div><!-- /.page-content -->

</div><!-- /.main-area -->

<script>
const ctx = document.getElementById('foodChart').getContext('2d');

new Chart(ctx, {
    type: 'pie',
    data: {
        labels: <?= json_encode($productLabels); ?>,
        datasets: [{
            data: <?= json_encode($productValues); ?>,
            backgroundColor: ['#BF616A','#D08770','#EBCB8B','#A3BE8C','#B48EAD','#88C0D0']
        }]
    },
    options: {
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

</body>
</html>
