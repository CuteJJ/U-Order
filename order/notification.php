<?php
session_start();
require_once __DIR__ . '/../configs/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

$userId = $_SESSION['user_id'];

/* ============================================================
    FETCH ORDERS (EXCLUDE COMPLETED)
============================================================ */
$sql = "
    SELECT 
        o.OrderId,
        o.Status,
        o.CreatedAt,
        s.StallName,
        p.TotalAmount
    FROM orders o
    JOIN stalls s ON o.StallId = s.StallId
    JOIN payments p ON o.PaymentId = p.PaymentId
    WHERE o.UserId = :uid
      AND o.Status != 'complete'
    ORDER BY o.CreatedAt DESC
";

$stmt = $db->prepare($sql);
$stmt->execute(['uid' => $userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
    HELPER FUNCTIONS
============================================================ */
function getStatusMessage($s)
{
    return [
        'pending'   => 'Your order is pending confirmation',
        'preparing' => 'Your order is being prepared',
        'ready'     => 'Your order is ready for pickup!',
        'cancelled' => 'Order was cancelled'
    ][$s] ?? 'Processing your order';
}

function timeAgo($dt)
{
    $ts = strtotime($dt);
    $diff = time() - $ts;

    if ($diff < 60) return "Just now";
    if ($diff < 3600) return floor($diff / 60) . "m ago";
    if ($diff < 86400) return floor($diff / 3600) . "h ago";
    return floor($diff / 86400) . "d ago";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Canteen Pre-Order</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Notification CSS -->
    <link rel="stylesheet" href="../assets/css/notification.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

    <!-- Header -->
  <header class="notif-header glass-nav">
    <a href="../index.php" class="nav-back-btn">
        <i class="fas fa-chevron-left"></i>
    </a>

    <h1 class="nav-title">Notifications</h1>

    <div class="nav-placeholder"></div>
</header>

    <!-- Main Container -->
    <main class="notif-container">
        <div id="notificationsContainer" class="notifications-list">
            <?php if (empty($orders)): ?>
                <div class="empty-center" id="emptyState">
                    <div class="empty-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <h2>No notifications yet</h2>
                    <p>Your order notifications will appear here</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <div class="notif-card" data-order-id="<?= $o['OrderId'] ?>" data-status="<?= $o['Status'] ?>">
                        <div class="notif-icon <?= $o['Status'] ?>">
                            <?php
                            $icons = [
                                'pending' => 'fa-clock',
                                'preparing' => 'fa-fire',
                                'ready' => 'fa-box-open',
                                'cancelled' => 'fa-times-circle'
                            ];
                            $icon = $icons[$o['Status']] ?? 'fa-circle-notch';
                            ?>
                            <i class="fas <?= $icon ?>"></i>
                        </div>

                        <div class="notif-content">
                            <div class="notif-header-row">
                                <div class="notif-title">
                                    <h3>Order #<?= $o['OrderId'] ?></h3>
                                    <p class="stall-name"><?= htmlspecialchars($o['StallName']) ?></p>
                                </div>

                                <?php if ($o['Status'] !== 'ready'): ?>
                                    <span class="notif-status-pill <?= $o['Status'] ?>">
                                        <?= ucfirst($o['Status']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p class="status-message">
                                <?= getStatusMessage($o['Status']) ?>
                            </p>

                            <div class="notif-footer">
                                <span class="notif-time">
                                    <i class="far fa-clock"></i>
                                    <?= timeAgo($o['CreatedAt']) ?>
                                </span>

                                <a href="order_detail.php?id=<?= $o['OrderId'] ?>" class="view-order-btn">
                                    <i class="fas fa-eye"></i> View Order
                                </a>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="loading-state d-none">
            <div class="spinner"></div>
            <p>Checking for updates...</p>
        </div>
    </main>

    <!-- Toast Notification -->
    <div id="notificationToast" class="notification-toast">
        <i class="fas fa-bell"></i>
        <span>New order update!</span>
    </div>

    <script>
        const USER_ID = <?= $userId ?>;
        const INITIAL_NOTIFICATIONS = <?= json_encode($orders) ?>;
    </script>

    <script src="../assets/js/notification.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>

</html>