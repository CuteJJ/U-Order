<?php
// 1. Setup & Auth
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

// Check Login
if (!isLoggedIn()) {
    flash('warning', 'Please login to view your orders.');
    header("Location: /U-Order/pages/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// 2. Fetch Data (Using your provided query)
$sql = "
    SELECT o.*, s.StallName
    FROM orders o
    JOIN stalls s ON o.StallId = s.StallId
    WHERE o.UserId = :uid
    ORDER BY o.OrderId DESC
";

$stmt = $db->prepare($sql);
$stmt->execute(['uid' => $userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Include Header
include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/order_history.css">

<div class="order-history-wrapper">
    <div class="history-nav">
        <a href="/U-Order/index.php" class="back-pill">
            <i class="fas fa-arrow-left"></i> Back to Menu
        </a>
    </div>
    <?php flash(); ?>

    <div class="history-header">
        <h1>Order History</h1>
        <p>Track your recent meals</p>
    </div>

    <?php if (empty($orders)): ?>
        <div class="no-orders">
            <div class="icon-box">
                <i class="fas fa-receipt"></i>
            </div>
            <h3>No orders yet</h3>
            <p>Looks like you haven't ordered anything yet.</p>
            <a href="/U-Order/index.php" class="btn-browse">Browse Stalls</a>
        </div>
    <?php else: ?>
        <div class="history-grid">
            <?php foreach ($orders as $order):
                // 1. Determine Status Color
                $statusClass = 'status-pending';
                if ($order['Status'] == 'completed') $statusClass = 'status-completed';
                if ($order['Status'] == 'cancelled') $statusClass = 'status-cancelled';
                if ($order['Status'] == 'ready') $statusClass = 'status-ready';

                // 2. Parse Created Date
                $dateObj = new DateTime($order['CreatedAt']);
                $formattedDate = $dateObj->format('d M, h:i A');

                // 3. GET REAL PICKUP TIME FROM DB (The Fix)
                // We assume your 'orders' table has a 'PickupTime' column just like 'cartitems'
                // If it's NULL, we fallback to CreatedAt + 15 mins (Logic for ASAP)
                if (!empty($order['PickupTime'])) {
                    $pickupObj = new DateTime($order['PickupTime']);
                    $pickupDisplay = $pickupObj->format('h:i A'); // e.g. "06:30 PM"
                } else {
                    $pickupDisplay = "ASAP";
                }

                // 4. Parse Payment Method only (removed Pickup parsing)
                $payMethod = "Cash"; // Default
                $parts = explode('|', $order['Notes']);
                foreach ($parts as $part) {
                    if (strpos(trim($part), 'Method:') === 0) {
                        $payMethod = trim(str_replace('Method:', '', $part));
                    }
                }
            ?>
                <div class="history-card">
                    <div class="card-top">
                        <div class="stall-info">
                            <h3><?php echo htmlspecialchars($order['StallName']); ?></h3>
                            <span class="order-date"><?php echo $formattedDate; ?></span>
                        </div>
                        <span class="status-badge <?php echo $statusClass; ?>">
                            <?php echo ucfirst($order['Status']); ?>
                        </span>
                    </div>

                    <div class="card-divider"></div>

                    <div class="order-details-grid">
                        <div class="detail-item">
                            <i class="fas fa-hashtag"></i>
                            <div>
                                <label>Order ID</label>
                                <span>#<?php echo $order['OrderId']; ?></span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-wallet"></i>
                            <div>
                                <label>Payment</label>
                                <span><?php echo htmlspecialchars($payMethod); ?></span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <label>Pickup Time</label>
                                <span><?php echo $pickupDisplay; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card-actions">
                        <a href="/U-Order/index.php?stall_id=<?php echo htmlspecialchars($order['StallId']); ?>" class="reorder-link">
                            View Stall <i class="fas fa-store"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="height: 60px;"></div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>