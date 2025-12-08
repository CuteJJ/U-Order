<?php
/**
 * 檔案名稱: order_slip.php
 * 描述: 顯示使用者最新一個進行中的訂單通知卡片，並具備 iOS 風格的毛玻璃效果。
 */

require __DIR__ . '/../configs/db.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    return;
}

// 取得進行中訂單數量
$totalActiveOrders = 0;
try {
    $countSql = "
        SELECT COUNT(OrderId)
        FROM orders
        WHERE UserId = :uid
          AND Status IN ('pending','preparing','ready')
    ";
    $cStmt = $db->prepare($countSql);
    $cStmt->execute(['uid' => $userId]);
    $totalActiveOrders = $cStmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Database error fetching order count: " . $e->getMessage());
    return;
}

if ($totalActiveOrders == 0) {
    return;
}

// 最新進行中訂單
$order = null;
try {
    $sql = "
        SELECT o.OrderId, o.StallId, o.Status, o.CreatedAt, s.StallName
        FROM orders o
        JOIN stalls s ON o.StallId = s.StallId
        WHERE o.UserId = :uid
          AND o.Status IN ('pending','preparing','ready')
        ORDER BY o.CreatedAt DESC
        LIMIT 1
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute(['uid' => $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching latest order: " . $e->getMessage());
    return;
}

if (!$order) {
    return;
}

// 取 PickupTime
$pickup = "ASAP";
try {
    $timeSql = "
        SELECT PickupTime
        FROM orderitems
        WHERE OrderId = :oid
        ORDER BY PickupTime ASC
        LIMIT 1
    ";
    $t = $db->prepare($timeSql);
    $t->execute(['oid' => $order['OrderId']]);
    $pt = $t->fetchColumn();

    if ($pt && $pt !== '0000-00-00 00:00:00') {
        $pickup = date("h:i A", strtotime($pt));
    }
} catch (PDOException $e) {
    error_log("Database error fetching pickup time: " . $e->getMessage());
}

// 自定义显示文字
if ($order['Status'] === 'ready') {
    $status = "Ready to Pick Up";
} else {
    $status = ucfirst($order['Status']);
}

?>
<div id="order-slip" class="order-slip">
    <div class="slip-left">
        <div class="slip-label">
            <?php if ($totalActiveOrders > 1): ?>
                <span class="multi-count"><?= $totalActiveOrders ?></span> Active Orders
            <?php else: ?>
                Active Order
            <?php endif; ?>
        </div>

        <div class="slip-orderid">Order #<?= $order['OrderId'] ?></div>
        <div class="slip-stall"><?= htmlspecialchars($order['StallName']) ?></div>

        <div class="slip-meta">
            <span class="slip-status <?= $order['Status'] ?>"><?= $status ?></span>
            <span class="slip-dot">•</span>
            <span class="slip-time"><?= $pickup ?></span>
        </div>
    </div>

    <a href="/U-Order/order/order_detail.php?id=<?= $order['OrderId'] ?>" class="slip-btn">
    <i class="fas fa-chevron-right"></i>
</a>
</div>

