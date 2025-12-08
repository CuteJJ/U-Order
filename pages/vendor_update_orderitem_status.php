<?php
require_once '../configs/db.php';

header('Content-Type: application/json');

// 必须登录
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in'
    ]);
    exit;
}

$vendorId = (int)$_SESSION['user_id'];

// 只允许 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// 拿参数
$orderItemId = isset($_POST['order_item_id']) ? (int)$_POST['order_item_id'] : 0;
$newStatus   = $_POST['status'] ?? '';

// 状态白名单（你现在只有这几种）
$allowedStatuses = ['pending', 'preparing', 'ready'];

if ($orderItemId <= 0 || !in_array($newStatus, $allowedStatuses, true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameters'
    ]);
    exit;
}

try {
    // 1）确认这个 orderitem 是属于当前档口的（安全）
    $sql = "
        SELECT 
            oi.OrderListId,
            oi.OrderId,
            o.StallId
        FROM orderitems oi
        JOIN orders o ON o.OrderId = oi.OrderId
        JOIN stalls s ON s.StallId = o.StallId
        WHERE oi.OrderListId = ?
          AND s.StaffId = ?
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$orderItemId, $vendorId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode([
            'success' => false,
            'message' => 'Order item not found or not yours'
        ]);
        exit;
    }

    $orderId = (int)$row['OrderId'];

    // 2）更新该行 item 的状态
    $updateItemSql = "
        UPDATE orderitems
        SET Status = ?
        WHERE OrderListId = ?
    ";
    $stmt = $db->prepare($updateItemSql);
    $stmt->execute([$newStatus, $orderItemId]);

    // 3）重新计算整张订单的状态（A 逻辑）
    $statusSql = "
        SELECT Status
        FROM orderitems
        WHERE OrderId = ?
          AND Status <> 'cancelled'
    ";
    $stmt = $db->prepare($statusSql);
    $stmt->execute([$orderId]);
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $hasPending   = false;
    $hasPreparing = false;
    $allReady     = true;

    foreach ($statuses as $st) {
        if ($st === 'pending') {
            $hasPending = true;
            $allReady   = false;
        } elseif ($st === 'preparing') {
            $hasPreparing = true;
            $allReady     = false;
        } elseif ($st === 'ready') {
            // keep allReady 可能为 true
        } else {
            $allReady = false;
        }
    }

    if ($hasPending) {
        $orderStatus = 'pending';
    } elseif ($hasPreparing) {
        $orderStatus = 'preparing';
    } elseif ($allReady && count($statuses) > 0) {
        $orderStatus = 'ready';
    } else {
        // 理论上不会进来，给个默认
        $orderStatus = 'pending';
    }

    // 4）同步更新 orders.Status（如果你不想用可以注释掉）
    $updateOrderSql = "
        UPDATE orders
        SET Status = ?
        WHERE OrderId = ?
    ";
    $stmt = $db->prepare($updateOrderSql);
    $stmt->execute([$orderStatus, $orderId]);

    echo json_encode([
        'success'     => true,
        'message'     => 'Status updated',
        'orderStatus' => $orderStatus
    ]);

} catch (Exception $ex) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $ex->getMessage()
    ]);
}
