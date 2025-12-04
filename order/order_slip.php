<?php
/**
 * 檔案名稱: order_slip.php (或其他您使用的名稱)
 * 描述: 顯示使用者最新一個進行中的訂單通知卡片，並具備 iOS 風格的毛玻璃效果。
 */

// 確保 db.php 存在且 $db 連線物件已初始化
require __DIR__ . '/../configs/db.php';

$userId = $_SESSION['user_id'] ?? null;
// 這裡假設 $_SESSION['user_id'] 已經設置，如果沒有則不執行後續邏輯
if (!$userId) {
    // 可以在這裡加上一個註解：
    // return; // 如果沒有登入，則不顯示訂單卡片
}

// ----------------------------------------------------------------------
// 1. 取得所有進行中訂單的數量 (用於 UI 提示)
// ----------------------------------------------------------------------
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
    // 處理資料庫錯誤，例如：log error
    error_log("Database error fetching order count: " . $e->getMessage());
    return;
}


if ($totalActiveOrders == 0) {
    return; // 沒有活動訂單，不顯示卡片
}

// ----------------------------------------------------------------------
// 2. 取得最新一筆進行中的訂單資訊
// ----------------------------------------------------------------------
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
    return; // 理論上不該發生，除非 count 錯了
}

// ----------------------------------------------------------------------
// 3. 取得取餐時間 (取第一個 item 的時間)
// ----------------------------------------------------------------------
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
    // 檢查 $pt 是否有效且非 NULL
    if ($pt && $pt !== '0000-00-00 00:00:00') { 
        $pickup = date("h:i A", strtotime($pt));
    }
} catch (PDOException $e) {
    error_log("Database error fetching pickup time: " . $e->getMessage());
    // 保持 ASAP 或其他預設值
}


// 將 Status 的第一個字母轉為大寫
$status = ucfirst($order['Status']);
?>

<div id="order-slip" class="order-slip show">

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
            <span class="slip-status <?= $order['Status'] ?>">
                <?= $status ?>
            </span>
            <span class="slip-dot">•</span>
            <span class="slip-time"><?= $pickup ?></span>
        </div>
    </div>

    <a href="order_details.php?orderid=<?= $order['OrderId'] ?>" class="slip-btn">
        <i class="fas fa fa-chevron-right"></i>
    </a>
</div>

<style>
/* 容器：強玻璃感與矩形 */
.order-slip {
    position: fixed;
    left: 50%;
    bottom: 20px;
    transform: translateX(-50%);
    width: 90%;
    max-width: 420px;

    /* 強 Frosted Glass (Acrylic) 效果 */
    background: rgba(255, 255, 255, 0.65); /* 更透明 */
    backdrop-filter: blur(16px) saturate(200%); /* 增加模糊度，強化玻璃感 */
    -webkit-backdrop-filter: blur(16px) saturate(200%); /* Safari 支援 */
    border: 1px solid rgba(255, 255, 255, 0.4); /* 亮色邊框 */
    border-radius: 14px; /* 更方正的圓角 */
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1); /* 柔和陰影 */
    
    padding: 16px 18px;
    display: flex;
    justify-content: space-between;
    z-index: 9999;
    /* 預設隱藏，等待 JS 加上 .show */
    opacity: 0; 
    pointer-events: none;
    transition: all .3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
}

.order-slip.show {
    opacity: 1;
    pointer-events: auto;
}

/* 文字樣式 */
.slip-left {
    line-height: 1.3;
}

/* "Active Order" 標籤顏色變更 (系統藍色) */
.slip-label {
    color: #4c84ff; /* 略微淡化的藍色，代表通知 */
    font-size: 0.75rem;
    font-weight: 600; 
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.slip-label .multi-count {
    color: #1f2937; /* 數字使用深色以增加對比 */
    margin-right: 2px;
}

/* 訂單 ID 強調 */
.slip-orderid {
    font-weight: 800; 
    font-size: 1.2rem; 
    margin-top: 2px;
    color: #1f2937; 
}

.slip-stall {
    color: #4b5563;
    font-size: 0.95rem;
    margin-bottom: 6px;
}

.slip-meta {
    font-size: 0.85rem;
}

/* 狀態顏色 */
.slip-status.pending { color: #d97706; font-weight: 600; }
.slip-status.preparing { color: #2563eb; font-weight: 600; }
.slip-status.ready { color: #059669; font-weight: 600; }
.slip-dot { color: #9ca3af; margin: 0 4px; } 
.slip-time { color: #4b5563; }

/* 按鈕樣式 (透明玻璃按鈕) */
.slip-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4b5563;
    /* 玻璃按鈕效果 */
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    width: 40px; 
    height: 40px;
    border-radius: 50%;
    text-decoration: none;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); 
    transition: background 0.2s ease;
}

.slip-btn:hover { 
    background: rgba(243, 244, 246, 0.9);
}

/* 確保fontawesome圖標顯示 */
.slip-btn i {
    font-size: 1rem;
}
</style>