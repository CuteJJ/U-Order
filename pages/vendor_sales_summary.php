<?php
require_once '../configs/db.php';
include 'vendor_sidebar.php';

// Vendor login check
if (!isset($_SESSION['user_id'])) {
    echo "Please login.";
    exit;
}

$vendorId = $_SESSION['user_id'];

$sql = "SELECT StallId FROM stalls WHERE StaffId = ? LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute([$vendorId]);
$stall = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stall) {
    echo "No stall assigned.";
    exit;
}

$stallId = $stall['StallId'];
$startDate = date("Y-m-d", strtotime("-30 days"));
$endDate   = date("Y-m-d");

// Monthly Revenue
$sql = "
SELECT SUM(oi.Subtotal)
FROM orderitems oi
JOIN orders o ON o.OrderId = oi.OrderId
WHERE o.StallId = ?
  AND o.Status <> 'cancelled'
  AND oi.Status <> 'cancelled'
  AND DATE(o.CreatedAt) BETWEEN ? AND ? ";
$stmt = $db->prepare($sql);
$stmt->execute([$stallId, $startDate, $endDate]);
$monthlyRevenue = $stmt->fetchColumn() ?: 0;

// Monthly Orders
$sql = "
SELECT COUNT(*)
FROM orders
WHERE StallId = ?
  AND Status <> 'cancelled'
  AND DATE(CreatedAt) BETWEEN ? AND ? ";
$stmt = $db->prepare($sql);
$stmt->execute([$stallId, $startDate, $endDate]);
$monthlyOrders = $stmt->fetchColumn() ?: 0;

// Top Selling Products
$sql = "
SELECT p.ProductName, SUM(oi.Quantity) AS SoldQty
FROM orderitems oi
JOIN orders o ON o.OrderId = oi.OrderId
JOIN products p ON p.ProductId = oi.ProductId
WHERE o.StallId = ?
  AND o.Status <> 'cancelled'
  AND oi.Status <> 'cancelled'
  AND DATE(o.CreatedAt) BETWEEN ? AND ?
GROUP BY oi.ProductId
ORDER BY SoldQty DESC
LIMIT 5 ";
$stmt = $db->prepare($sql);
$stmt->execute([$stallId, $startDate, $endDate]);
$topItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$bestName = $topItems[0]['ProductName'] ?? "N/A";

// Chart data
$sql = "
SELECT DATE(o.CreatedAt) AS Day, 
       SUM(oi.Subtotal) AS Revenue
FROM orderitems oi
JOIN orders o ON o.OrderId = oi.OrderId
WHERE o.StallId = ?
  AND o.Status <> 'cancelled'
  AND oi.Status <> 'cancelled'
  AND DATE(o.CreatedAt) BETWEEN ? AND ?
GROUP BY DATE(o.CreatedAt)
ORDER BY Day ASC";
$stmt = $db->prepare($sql);
$stmt->execute([$stallId, $startDate, $endDate]);
$chartRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format chart datasets
$days = [];
$revenues = [];
foreach ($chartRows as $row) {
    $days[]     = date("d", strtotime($row['Day']));
    $revenues[] = (float)$row['Revenue'];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Vendor Sales Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #f5f7fa;
    font-family: 'Segoe UI', sans-serif;
}

/* Final layout: Sidebar left (240px) + dashboard full width */
.dashboard-wrapper {
    margin-left: 240px;
    padding: 25px;
    width: calc(100% - 240px);
}

/* KPI Cards */
.dashboard-card {
    background: #fffcfcff;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.06);
    border-left: 5px solid;
}

.card-title {
    font-size: 14px;
    color: #666;
}

.card-value {
    font-size: 26px;
    font-weight: 700;
}

/* Sales chart sizing */
#salesChart {
    max-width: 800px !important;
    max-height: 500px !important;
    margin: 0 auto;
    display: block;
}
.chart-card {
    padding: 15px 20px !important;
}

/* Top selling product list */
.top-item {
    background: white;
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    font-size: 15px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.05);
}

.rank-number {
    font-weight: bold;
    margin-right: 10px;
}
</style>

</head>
<body>

<div class="dashboard-wrapper">

<h2 class="fw-bold mb-4">Vendor Sales Dashboard</h2>

<!-- KPI Row -->
<div class="row g-3 mb-4">

    <div class="col-md-4">
        <div class="dashboard-card" style="border-color:#2980f3;">
            <div class="card-title">Total Revenue (Last 30 Days)</div>
            <div class="card-value">RM <?= number_format($monthlyRevenue,2) ?></div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="dashboard-card" style="border-color:#e74c3c;">
            <div class="card-title">Total Orders (Last 30 Days)</div>
            <div class="card-value"><?= $monthlyOrders ?></div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="dashboard-card" style="border-color:#27ae60;">
            <div class="card-title">Best Selling Product</div>
            <div class="card-value"><?= htmlspecialchars($bestName) ?></div>
        </div>
    </div>

</div>

<!-- Sales Chart -->
<div class="dashboard-card mb-4 chart-card" style="border-color:#8e44ad;">
    <h5 class="fw-bold mb-2">Sales (Last 30 Days)</h5>
    <canvas id="salesChart"></canvas>
</div>

<!-- Top Selling -->
<h4 class="fw-bold mb-3">Top Selling Products</h4>

<?php
$rank = 1;
foreach ($topItems as $item): ?>
    <div class="top-item">
        <div>
            <span class="rank-number">#<?= $rank ?></span>
            <strong><?= htmlspecialchars($item['ProductName']) ?></strong>
        </div>
        <span><?= $item['SoldQty'] ?> sold</span>
    </div>
<?php
$rank++;
endforeach; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById("salesChart"), {
    type: "bar",
    data: {
        labels: <?= json_encode($days) ?>,
        datasets: [{
            label: "Revenue (RM)",
            data: <?= json_encode($revenues) ?>,
            backgroundColor: "#2980f3"
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

</body>
</html>
