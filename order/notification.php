<?php
session_start();
require_once '../config/connection.php';

// Check if user is logged in
if (!isset($_SESSION['UserId'])) {
    header('Location: ../login.php');
    exit();
}

$userId = $_SESSION['UserId'];
$userName = $_SESSION['Name'] ?? 'User';

// Fetch notifications
$query = "SELECT 
            o.OrderId,
            o.Status,
            o.CreatedAt,
            o.Notes,
            s.StallName,
            p.TotalAmount
          FROM orders o
          INNER JOIN payments p ON o.PaymentId = p.PaymentId
          INNER JOIN stalls s ON o.StallId = s.StallId
          WHERE o.UserId = ?
          ORDER BY o.CreatedAt DESC
          LIMIT 50";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Canteen Pre-Order</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="/U-Order/assets/css/app.css">
    <link rel="stylesheet" href="/U-Order/assets/css/notification.css">
    <!-- jQuery  -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Main Content Wrapper -->
    <div class="app-wrapper">
        
        <!-- Header -->
        <header class="notification-header">
            <div class="container">
                <div class="header-content">
                    <div class="header-left">
                        <a href="../index.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div class="header-title">
                            <h1>Notifications</h1>
                            <p>Stay updated with your orders</p>
                        </div>
                    </div>
                    
                    <div class="header-right">
                        <i class="fas fa-bell"></i>
                        <span id="notificationCount" class="notification-badge d-none">0</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Notifications Container -->
        <main class="notification-main">
            <div class="container">
                <div id="notificationsContainer" class="notifications-list">
                    <!-- Notifications will be loaded here by JS -->
                </div>

                <!-- Loading State -->
                <div id="loadingState" class="loading-state">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading notifications...</p>
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="empty-state d-none">
                    <div class="empty-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h2>No notifications yet</h2>
                    <p>We'll notify you when there are updates to your orders</p>
                </div>
            </div>
        </main>

    </div>

    <!-- Pass PHP data to JavaScript -->
    <script>
        const USER_ID = <?php echo $userId; ?>;
        const INITIAL_NOTIFICATIONS = <?php echo json_encode($notifications); ?>;
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Notification JS -->
    <script src="/U-Order/js/notification.js"></script>
</body>
</html>