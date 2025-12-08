<?php
// pages/vendor_batch_update_items.php
session_start();
require_once '../configs/db.php';

// !!! 確保這裡引用了包含 sendReceipt() 的文件 !!!
// 如果你的 sendReceipt 在 functions.php 裡，請確保路徑正確
require_once '../includes/email_helper.php';

header('Content-Type: application/json');

// 1. 安全權限檢查
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// 2. 獲取並驗證輸入
$newStatus = $_POST['status'] ?? '';
$itemIds   = $_POST['item_ids'] ?? [];

if (empty($itemIds) || !is_array($itemIds)) {
    echo json_encode(['success' => false, 'message' => 'No items selected']);
    exit;
}

// 只允許合法的狀態流轉
$allowedStatuses = ['preparing', 'ready', 'completed']; 
if (!in_array($newStatus, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // 開啟事務，確保一系列操作要麼全成功，要麼全失敗
    $db->beginTransaction();

    // 準備 SQL 語句 (性能優化：在循環外準備)
    
    // A. 查詢 Item 詳情 (包含支付信息)
    // 我們需要 JOIN orders 和 payments 表來獲取這單是不是 Cash
    $fetchSql = "
        SELECT 
            oi.OrderListId, 
            o.PaymentId,
            o.UserId,
            pm.TotalAmount,
            pm.Status as PaymentStatus,
            pm.PaymentMethod
        FROM orderitems oi
        JOIN orders o    ON o.OrderId = oi.OrderId
        JOIN payments pm ON pm.PaymentId = o.PaymentId
        WHERE oi.OrderListId = ?
    ";
    $stmtFetch = $db->prepare($fetchSql);

    // B. 更新 Item 狀態
    $updateItemSql = "UPDATE orderitems SET Status = ? WHERE OrderListId = ?";
    $stmtUpdateItem = $db->prepare($updateItemSql);

    // C. 更新 Payment 狀態 (針對 Cash)
    // 條件：PaymentId 匹配，且當前狀態必須是 'pending' (防止重複更新)
    $updatePaySql = "UPDATE payments SET Status = 'paid' WHERE PaymentId = ? AND Status = 'pending'";
    $stmtUpdatePay = $db->prepare($updatePaySql);

    // --- 循環處理每個選中的 ID ---
    foreach ($itemIds as $id) {
        $orderListId = (int)$id;

        // 1. 獲取數據
        $stmtFetch->execute([$orderListId]);
        $itemData = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        if (!$itemData) continue; // 找不到數據，跳過

        // 2. 更新 OrderItem 狀態 (例如變成 preparing 或 ready)
        $stmtUpdateItem->execute([$newStatus, $orderListId]);

        // 3. 【核心邏輯】 處理 Cash 自動付款與郵件
        // 觸發條件：
        // a. 新狀態是 'ready' (代表廚師做好了，一手交錢一手交貨)
        // b. 支付方式是 'cash'
        // c. 當前支付狀態是 'pending' (還沒給錢)
        if ($newStatus === 'ready' && 
            strtolower($itemData['PaymentMethod']) === 'cash' && 
            $itemData['PaymentStatus'] === 'pending') {
            
            // 執行 Payment 更新
            $stmtUpdatePay->execute([$itemData['PaymentId']]);

            // 檢查是否真的更新了數據 (rowCount > 0)
            // 如果 rowCount 為 0，說明被同一個訂單的其他菜品先觸發更新了，我們就不重複發郵件
            if ($stmtUpdatePay->rowCount() > 0) {
                
                // 4. 發送郵件 (調用 functions.php 裡的函數)
                // 這裡傳一個空數組作為 $items 參數，因為你的 sendReceipt 主要是發送總金額確認
                // 如果你需要列出具體菜品，這裡需要額外查詢，但目前需求看起來不需要
                $itemsForReceipt = []; 

                sendReceipt(
                    $db, 
                    $itemData['UserId'], 
                    $itemData['PaymentId'], 
                    $itemData['TotalAmount'], 
                    $itemsForReceipt
                );
            }
        }
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Vendor Batch Update Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}