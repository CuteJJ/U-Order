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

// 2. Fetch Data - Only Completed Orders
$sql = "
    SELECT o.*, s.StallName
    FROM orders o
    JOIN stalls s ON o.StallId = s.StallId
    WHERE o.UserId = :uid AND o.Status = 'complete'
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

<style>
/* Additional styles for the card actions */
.card-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    align-items: center;
    padding-top: 12px;
}

.view-details-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #6B8DB8 0%, #5a7ba6 100%);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(107, 141, 184, 0.3);
}

.view-details-btn:hover {
    background: linear-gradient(135deg, #5a7ba6 0%, #4a6a96 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(107, 141, 184, 0.4);
    color: white;
}

.reorder-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #f3f4f6;
    color: #6B8DB8;
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 1px solid #e5e7eb;
}

.reorder-link:hover {
    background: #e5e7eb;
    color: #5a7ba6;
    transform: translateY(-2px);
}
</style>

<div class="order-history-wrapper">
    <div class="history-nav">
        <a href="/U-Order/index.php" class="back-pill">
            <i class="fas fa-arrow-left"></i> Back to Menu
        </a>
    </div>
    <?php flash(); ?>

    <div class="history-header">
        <h1>Order History</h1>
        <p>Your completed orders</p>
    </div>

    <?php if (empty($orders)): ?>
        <div class="no-orders">
            <div class="icon-box">
                <i class="fas fa-receipt"></i>
            </div>
            <h3>No completed orders yet</h3>
            <p>You don't have any completed orders yet. Start ordering to see your history here!</p>
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
                       <a href="/U-Order/pages/reorder.php?orderid=<?= $order['OrderId'] ?>" class="reorder-link">
    <i class="fas fa-redo"></i> Order Again
</a>

                        <a href="/U-Order/pages/menu.php?stallid=<?php echo htmlspecialchars($order['StallId']); ?>" class="reorder-link">
                            <i class="fas fa-store"></i>
                            View Stall
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