<?php
include '../configs/db.php';
include '../includes/functions.php';

// Access Control
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    flash('error', 'Access Denied. Admin only.');
    header("Location: login.php");
    exit;
}

// Fetch Statistics
// 1. Total Sales
$stmt = $db->query("SELECT SUM(TotalAmount) FROM payments WHERE Status = 'paid'");
$totalSales = $stmt->fetchColumn() ?: 0;

// 2. Total Users
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE Role = 'customer'");
$totalUsers = $stmt->fetchColumn();

// 3. Total Stalls
$stmt = $db->query("SELECT COUNT(*) FROM stalls");
$totalStalls = $stmt->fetchColumn();

// 4. Recent Registrations (Users)
$stmt = $db->query("SELECT * FROM users ORDER BY CreatedAt DESC LIMIT 5");
$recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <style>
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #4a90e2; }
        .stat-label { color: #666; }
        
        .action-bar { display: flex; gap: 15px; margin-bottom: 30px; background: #fff; padding: 15px; border-radius: 8px; }
        .btn-admin { padding: 10px 20px; text-decoration: none; border-radius: 5px; background: #eee; color: #333; font-weight: bold; }
        .btn-admin.primary { background: #4a90e2; color: white; }
        
        .table-container { background: white; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>Admin Dashboard</h2>
            <div>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span> | 
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <?php flash(); ?>

        <!-- Quick Actions -->
        <div class="action-bar">
            <!-- Updated Links -->
            <a href="stall_register.php" class="btn-admin primary">+ Register New Stall</a>
            <a href="reports.php" class="btn-admin">View Reports</a>
            <a href="menu.php" class="btn-admin">View Live Menu</a>
        </div>

        <!-- Stats -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-number">RM <?php echo number_format($totalSales, 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Registered Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalStalls; ?></div>
                <div class="stat-label">Active Stalls</div>
            </div>
        </div>

        <!-- Recent Users Table -->
        <div class="table-container">
            <h3>Recently Registered Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($u['UserId']); ?></td>
                        <td><?php echo htmlspecialchars($u['Name']); ?></td>
                        <td><?php echo htmlspecialchars($u['Email']); ?></td>
                        <td><?php echo ucfirst($u['Role']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($u['CreatedAt'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>