<?php
// pages/vendor_fetch_orders.php
session_start();
require_once '../configs/db.php';

header('Content-Type: application/json');

// 1. 權限檢查
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['html' => '', 'hasMore' => false, 'error' => 'Unauthorized']);
    exit;
}

$vendorId = (int)$_SESSION['user_id'];

// 2. 獲取 StallId
$stmt = $db->prepare("SELECT StallId FROM stalls WHERE StaffId = ? LIMIT 1");
$stmt->execute([$vendorId]);
$stall = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stall) {
    echo json_encode(['html' => '', 'hasMore' => false, 'error' => 'No stall assigned']);
    exit;
}
$stallId = (int)$stall['StallId'];

// 3. 接收參數
$status   = $_GET['status'] ?? 'pending'; // pending, preparing, ready
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 5; // 每次加載 5 張卡片 (你可以根據需要調整)
$offset   = ($page - 1) * $limit;

$category = $_GET['category'] ?? 'all';
$pickup   = $_GET['pickup'] ?? 'all';

// 4. 構建 SQL
// 我們使用 "Limit + 1" 的技巧：如果請求 5 條，我們查 6 條。
// 如果查到 6 條，說明還有 "下一頁"，但我們只顯示前 5 條。
$params = [$stallId, $status];
$whereSQL = "WHERE o.StallId = ? AND oi.Status = ?";

// --- 處理 Category 過濾 ---
if ($category !== 'all') {
    $whereSQL .= " AND p.CategoryId = ?";
    $params[] = $category;
}

// --- 處理 Pickup Time 過濾 ---
if ($pickup !== 'all') {
    if ($pickup === 'now') {
        // Now = ASAP (NULL) 或者 未來 30 分鐘內的單
        $whereSQL .= " AND (oi.PickupTime IS NULL OR oi.PickupTime <= DATE_ADD(NOW(), INTERVAL 30 MINUTE))";
    } elseif ($pickup === '1h') {
        $whereSQL .= " AND (oi.PickupTime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR))";
    } elseif ($pickup === '2h') {
        $whereSQL .= " AND (oi.PickupTime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR))";
    }
}

// 查詢主體 (包含 Payment 信息)
$sql = "
    SELECT 
        oi.OrderListId,
        oi.OrderId,
        oi.ProductId,
        oi.Quantity,
        oi.Note,
        oi.PickupTime,
        oi.Status,
        p.ProductName,
        p.CategoryId,
        u.Name AS CustomerName,
        pm.PaymentMethod,
        pm.Status AS PaymentStatus
    FROM orderitems oi
    JOIN products p  ON p.ProductId = oi.ProductId
    JOIN orders o    ON o.OrderId = oi.OrderId
    JOIN users u     ON u.UserId = o.UserId
    JOIN payments pm ON pm.PaymentId = o.PaymentId
    $whereSQL
    ORDER BY 
        (oi.PickupTime IS NULL) DESC, -- ASAP 排最前
        oi.PickupTime ASC,            -- 按時間順序
        oi.OrderListId ASC
    LIMIT " . ($limit + 1) . " OFFSET $offset
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. 判斷是否有下一頁
    $hasMore = false;
    if (count($items) > $limit) {
        $hasMore = true;
        array_pop($items); // 移除第 6 條，只保留前 5 條
    }

    // 6. 渲染 HTML (使用 Output Buffering)
    ob_start();
    
    if (empty($items) && $page === 1) {
        // 如果第一頁就沒數據，顯示空狀態
        echo '<div class="empty-state">No orders here.</div>';
    } else {
        foreach ($items as $item) {
            // 這裡引入我們上一步寫好的模板
            // 變量 $item 會自動傳進去
            include 'vendor_order_card.php';
        }
    }
    
    $htmlContent = ob_get_clean();

    // 7. 返回 JSON
    echo json_encode([
        'success' => true,
        'html'    => $htmlContent,
        'hasMore' => $hasMore,
        'page'    => $page
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}