<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'vendor') {
    flash('error', 'Access Denied. Vendor only.');
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

/* ====== 1. Vendor Stall ====== */
$stmt = $db->prepare("SELECT StallId, StallName FROM stalls WHERE StaffId = :uid");
$stmt->execute([':uid' => $userId]);
$stall = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stall) { die("Error: No stall assigned."); }

$stallId = $stall['StallId'];

/* ====== 2. Total Revenue ====== */
$stmt = $db->prepare("
    SELECT SUM(ol.Subtotal)
    FROM orderitems ol
    JOIN orders o ON ol.OrderId = o.OrderId
    JOIN payments p ON o.PaymentId = p.PaymentId
    WHERE o.StallId = :sid AND p.Status = 'paid'
");
$stmt->execute([':sid' => $stallId]);
$totalRevenue = $stmt->fetchColumn() ?: 0;

/* ====== 3. Peak Hour ====== */
$stmt = $db->prepare("
    SELECT HOUR(CreatedAt) as OrderHour, COUNT(*) as Count
    FROM orders
    WHERE StallId = :sid
    GROUP BY OrderHour
    ORDER BY Count DESC LIMIT 1
");
$stmt->execute([':sid' => $stallId]);
$peakData = $stmt->fetch(PDO::FETCH_ASSOC);
$peakHour = $peakData ? date("g A", strtotime($peakData['OrderHour'] . ":00")) : "N/A";

/* ====== 4. Top Category ====== */
$stmt = $db->prepare("
    SELECT c.CategoryName, COUNT(ol.OrderListId) as SoldCount
    FROM orderitems ol
    JOIN products p ON ol.ProductId = p.ProductId
    JOIN categories c ON p.CategoryId = c.CategoryId
    JOIN orders o ON ol.OrderId = o.OrderId
    WHERE o.StallId = :sid
    GROUP BY c.CategoryId
    ORDER BY SoldCount DESC LIMIT 1
");
$stmt->execute([':sid' => $stallId]);
$topCat = $stmt->fetchColumn() ?: "N/A";

/* ====== 5. Chart Data ====== */
$chartSql = "
    SELECT p.ProductName, SUM(ol.Subtotal) as ProductTotal
    FROM orderitems ol
    JOIN products p ON ol.ProductId = p.ProductId
    JOIN orders o ON ol.OrderId = o.OrderId
    JOIN payments pay ON o.PaymentId = pay.PaymentId
    WHERE o.StallId = :sid AND pay.Status = 'paid'
    GROUP BY p.ProductId
";
$chartStmt = $db->prepare($chartSql);
$chartStmt->execute([':sid' => $stallId]);
$chartData = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

$productLabels = array_column($chartData, 'ProductName');
$productValues = array_column($chartData, 'ProductTotal');

/* ====== 6. Recent Orders ====== */
$recentSql = "
    SELECT o.OrderId, u.Name as CustomerName, o.CreatedAt, o.Status,
           (SELECT SUM(Subtotal) FROM orderitems WHERE OrderId = o.OrderId) as OrderTotal
    FROM orders o
    JOIN users u ON o.UserId = u.UserId
    WHERE o.StallId = :sid
    ORDER BY o.CreatedAt DESC LIMIT 5
";
$stmtRecent = $db->prepare($recentSql);
$stmtRecent->execute([':sid' => $stallId]);
$recentOrders = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Dashboard</title>

    <link rel="stylesheet" href="../assets/css/aurora_theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Fix layout so sidebar + content align nicely */
        .main-area {
            margin-left: 240px; /* matches your sidebar width */
            padding: 25px 35px;
        }

        .page-content {
            margin-top: 10px;
        }
    </style>
</head>

<body>

<?php include 'vendor_sidebar.php'; ?>

<div class="main-area">

    <?php include 'vendor_topbar.php'; ?>

    <div class="page-content">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px;">
            <div>
                <h2 style="margin-bottom: 5px;">Vendor Dashboard</h2>
                <span style="color: var(--text-muted);">
                    Manage <strong><?= htmlspecialchars($stall['StallName']); ?></strong>
                </span>
            </div>

            <div>
                <span style="margin-right: 15px; font-weight: 600;">
                    Hi, <?= htmlspecialchars($_SESSION['name']); ?>
                </span>
                <a href="logout.php" class="btn btn-secondary" style="font-size: 0.9rem; padding: 8px 16px;">
                    Logout
                </a>
            </div>
        </div>

        <?php flash(); ?>

        <!-- Stats Grid -->
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

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 30px;">
            <div class="card" style="padding: 25px; min-height: 350px;">
                <h4 style="margin-bottom: 20px;">Sales by Food Item</h4>
                <canvas id="foodChart"></canvas>
            </div>

            <div class="card" style="padding: 25px; display:flex; flex-direction:column; gap:15px;">
                <h4 style="margin-bottom: 10px;">Quick Actions</h4>
                <a href="vendor_reports.php" class="btn btn-admin primary">View Reports</a>
                <a href="vendor_menu.php" class="btn btn-admin">View My Menu</a>
            </div>
        </div>

        <div class="table-container">
            <div style="padding: 20px; background: white; border-bottom: 1px solid #ccc;">
                <h3 style="margin: 0;">Recent Orders</h3>
            </div>
            <table>
                <thead>
                    <tr><th>Order ID</th><th>Customer</th><th>Date</th><th>Status</th><th>Amount</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td>#<?= $o['OrderId']; ?></td>
                        <td><?= htmlspecialchars($o['CustomerName']); ?></td>
                        <td><?= date('d M H:i', strtotime($o['CreatedAt'])); ?></td>
                        <td><?= ucfirst($o['Status']); ?></td>
                        <td>RM <?= number_format($o['OrderTotal'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div> <!-- /page-content -->

</div> <!-- /main-area -->

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
    options: { plugins: { legend: { position: 'bottom' } } }
});
</script>

</body>
</html>
