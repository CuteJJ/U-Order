<?php
// 1. Setup & Auth
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    flash('warning', 'Please login to view your orders.');
    header("Location: /U-Order/pages/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// 2. Fetch Data
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

$itemSql = "
    SELECT p.ProductName, oi.Quantity, oi.Subtotal, oi.Note 
    FROM orderitems oi
    JOIN products p ON oi.ProductId = p.ProductId 
    WHERE OrderId = :oid
";
$itemStmt = $db->prepare($itemSql);

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

            <?php
            $index = 0; // Initialize counter
            foreach ($orders as $order):
                // 1. Fetch Items
                $itemStmt->execute(['oid' => $order['OrderId']]);
                $orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

                // 2. Calculate Total Order Price
                $orderTotal = 0;
                foreach ($orderItems as $item) {
                    $orderTotal += $item['Subtotal'];
                }

                // 3. Status Logic
                $statusClass = 'status-pending';
                if ($order['Status'] == 'completed') $statusClass = 'status-completed';
                if ($order['Status'] == 'cancelled') $statusClass = 'status-cancelled';
                if ($order['Status'] == 'ready') $statusClass = 'status-ready';

                // 4. Date Logic
                $dateObj = new DateTime($order['CreatedAt']);
                $formattedDate = $dateObj->format('d M, h:i A');
            ?>
                <div class="history-card">
            
            <?php if ($index === 0): ?>
                <span style="
                    position: absolute; 
                    top: 0; 
                    left: 0; 
                    background: #81A1C1; 
                    color: white; 
                    font-size: 0.7rem; 
                    padding: 4px 12px; 
                    border-bottom-right-radius: 10px; /* Rounds the inner corner */
                    border-top-left-radius: 10px;
                    font-weight: bold; 
                    letter-spacing: 1px;
                    z-index: 10;
                ">
                    LATEST
                </span>
            <?php endif; ?>

                    <div class="card-header-toggle">
                        <div class="card-top">
                            <div class="stall-info">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <h3><?php echo htmlspecialchars($order['StallName']); ?></h3>
    
                                    <span class="order-total-price">RM <?php echo number_format($orderTotal, 2); ?></span>
                                </div>
                                <div class="sub-info">
                                    <span class="order-date">#<?php echo $order['OrderId']; ?> &bull; <?php echo $formattedDate; ?></span>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($order['Status']); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="toggle-icon">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>

                    <div class="order-items-list" style="display: none;">
                        <div class="card-divider"></div>
                        <?php if (count($orderItems) > 0): ?>
                            <?php foreach ($orderItems as $item): ?>
                                <div class="item-row">
                                    <div class="item-left">
                                        <span class="item-qty"><?php echo $item['Quantity']; ?>x</span>
                                        <span class="item-name"><?php echo htmlspecialchars($item['ProductName']); ?></span>
                                    </div>
                                    <span class="item-price">RM <?php echo number_format($item['Subtotal'], 2); ?></span>
                                </div>
                                <?php if (!empty($item['Note'])): ?>
                                    <div class="item-note">
                            <i style="color: #7f7f7fff; font-size: 0.8rem;">Notes: <?php echo htmlspecialchars($item['Note']); ?></i>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="font-size:0.8rem; color:#999;">No items found.</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-actions">
                        <a href="/U-Order/pages/menu.php?stallid=<?php echo htmlspecialchars($order['StallId']); ?>" class="reorder-link">
                            View Stall <i class="fas fa-store"></i>
                        </a>
                    </div>
                </div>
            <?php
                $index++; // Increment
            endforeach;
            ?>
        </div>
    <?php endif; ?>
    <div style="height: 60px;"></div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>