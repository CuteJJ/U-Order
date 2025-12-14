<?php
session_start();
require_once __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';
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
include '../includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/notification.css">

<div class="notification-wrapper">
    <div class="page-nav-row">
        <a href="/U-Order/index.php" class="back-pill">
            <i class="fas fa-arrow-left"></i> Back to Menu
        </a>
    </div>

    <div class="page-header">
        <h1>Notifications</h1>
        <p>Real-time updates on your active orders</p>
    </div>

    <div id="notificationsContainer" class="notifications-list">
        <?php if (empty($orders)): ?>
            <div class="empty-center">
                <div class="empty-icon">
                    <i class="fas fa-bell-slash"></i>
                </div>
                <h2>No notifications yet</h2>
                <p>Your order notifications will appear here</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $o): ?>
                <div class="notif-card" data-order-id="<?= $o['OrderId'] ?>" data-status="<?= $o['Status'] ?>">

                    <div class="status-strip <?= $o['Status'] ?>"></div>

                    <div class="notif-body">
                        <div class="notif-top-row">
                            <div class="stall-info">
                                <span class="order-id">#<?= $o['OrderId'] ?></span>
                                <h3 class="stall-name"><?= htmlspecialchars($o['StallName']) ?></h3>
                            </div>
                            <span class="time-tag">
                                <i class="far fa-clock"></i> <?= timeAgo($o['CreatedAt']) ?>
                            </span>
                        </div>

                        <div class="notif-message-row">
                            <div class="icon-bubble <?= $o['Status'] ?>">
                                <?php
                                $icons = [
                                    'pending' => 'fa-hourglass-half',
                                    'preparing' => 'fa-fire-alt',
                                    'ready' => 'fa-utensils',
                                    'cancelled' => 'fa-times'
                                ];
                                $icon = $icons[$o['Status']] ?? 'fa-circle';
                                ?>
                                <i class="fas <?= $icon ?>"></i>
                            </div>
                            <div class="message-text">
                                <span class="status-title"><?= ucfirst($o['Status']) ?></span>
                                <p>
                                    <?php
                                    if ($o['Status'] == 'pending') echo "Waiting for stall to confirm.";
                                    elseif ($o['Status'] == 'preparing') echo "The chef is preparing your meal.";
                                    elseif ($o['Status'] == 'ready') echo "It's ready! Please pick it up now.";
                                    else echo "Order status has been updated.";
                                    ?>
                                </p>
                            </div>
                        </div>

                        <div class="notif-actions">
                            <a href="order_detail.php?id=<?= $o['OrderId'] ?>" class="btn-view-details">
                                View Details <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="notificationToast">
    <div class="toast-icon"><i class="fas fa-bell"></i></div>
    <div class="toast-content">
        <span class="toast-title">Order Update!</span>
        <span class="toast-desc">Status changed.</span>
    </div>
</div>

<script>
    const USER_ID = <?= $userId ?>;
    const INITIAL_NOTIFICATIONS = <?= json_encode($orders) ?>;
</script>
<script src="../assets/js/notification.js"></script>


<?php include __DIR__ . '/../includes/footer.php'; ?>