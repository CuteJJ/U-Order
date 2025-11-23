<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    flash('error', 'Access Denied. Admin only.');
    header("Location: login.php");
    exit;
}

// Stats Queries
$stmt = $db->query("SELECT SUM(TotalAmount) FROM payments WHERE Status = 'paid'");
$totalSales = $stmt->fetchColumn() ?: 0;
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE Role = 'customer'");
$totalUsers = $stmt->fetchColumn();
$stmt = $db->query("SELECT COUNT(*) FROM stalls");
$totalStalls = $stmt->fetchColumn();
$stmt = $db->query("SELECT * FROM users ORDER BY CreatedAt DESC LIMIT 5");
$recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- NEW QUERY FOR PIE CHART (Sales by Stall) ---
$chartSql = "SELECT s.StallName, SUM(ol.Subtotal) as StallTotal
             FROM orderlists ol
             JOIN orders o ON ol.OrderId = o.OrderId
             JOIN stalls s ON o.StallId = s.StallId
             JOIN payments p ON o.PaymentId = p.PaymentId
             WHERE p.Status = 'paid'
             GROUP BY s.StallId";
$chartStmt = $db->query($chartSql);
$chartData = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for JS
$stallLabels = [];
$stallValues = [];
foreach ($chartData as $row) {
    $stallLabels[] = $row['StallName'];
    $stallValues[] = $row['StallTotal'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/aurora_theme.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px;">
            <div>
                <h2 style="margin-bottom: 5px;">Admin Dashboard</h2>
                <span style="color: var(--text-muted);">Overview of your canteen system</span>
            </div>
            <div>
                <span style="margin-right: 15px; font-weight: 600;">Hi, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="logout.php" class="btn btn-secondary" style="font-size: 0.9rem; padding: 8px 16px;">Logout</a>
            </div>
        </div>

        <?php flash(); ?>

        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-number">RM <?php echo number_format($totalSales, 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalStalls; ?></div>
                <div class="stat-label">Stalls</div>
            </div>
        </div>
        
        <!-- NEW: Dashboard Layout (Chart + Actions) -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 30px;">
            
            <!-- Pie Chart Container -->
            <div class="card" style="padding: 25px; min-height: 350px;">
                <h4 style="margin-bottom: 20px; color: var(--text-main);">Sales Distribution by Stall</h4>
                <div style="height: 300px; display:flex; justify-content:center;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Quick Actions (Moved here) -->
            <div class="card" style="padding: 25px; display:flex; flex-direction:column; gap:15px;">
                <h4 style="margin-bottom: 10px; color: var(--text-main);">Quick Actions</h4>
                <a href="stall_register.php" class="btn btn-admin primary" style="text-align:center;">+ Register New Stall</a>
                <a href="reports.php" class="btn btn-admin" style="text-align:center;">View Reports</a>
                <a href="menu.php" class="btn btn-admin" style="text-align:center;">View Live Menu</a>
            </div>
        </div>

        <div class="table-container">
            <div style="padding: 20px; background: white; border-bottom: 1px solid var(--border-color);">
                <h3 style="margin: 0;">Recent Registrations</h3>
            </div>
            <table>
                <thead>
                    <tr><th>User ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined Date</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td style="font-family: monospace; color: var(--aurora-purple);"><?php echo htmlspecialchars($u['UserId']); ?></td>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($u['Name']); ?></td>
                        <td><?php echo htmlspecialchars($u['Email']); ?></td>
                        <td>
                            <span style="padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; background: #E5E9F0; font-weight: 700; text-transform: uppercase;">
                                <?php echo ucfirst($u['Role']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($u['CreatedAt'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chart Configuration -->
    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($stallLabels); ?>,
                datasets: [{
                    label: 'Revenue (RM)',
                    data: <?php echo json_encode($stallValues); ?>,
                    backgroundColor: [
                        '#BF616A', // Red
                        '#D08770', // Orange
                        '#EBCB8B', // Yellow
                        '#A3BE8C', // Green
                        '#B48EAD', // Purple
                        '#88C0D0', // Blue
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>