<?php
// pages/vendor_batch_update_items.php
session_start();
require_once '../configs/db.php';

// =======================================================================
// 1. 引用你的 functions.php
// 這裡我們不自己寫 env()，直接用你文件裡的
// =======================================================================
if (file_exists(__DIR__ . '/../functions.php')) {
    require_once __DIR__ . '/../functions.php';
} elseif (file_exists(__DIR__ . '/../includes/functions.php')) {
    require_once __DIR__ . '/../includes/functions.php';
}

header('Content-Type: application/json');

// 2. 權限檢查
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// 3. 獲取數據
$newStatus = $_POST['status'] ?? '';
$itemIds   = $_POST['item_ids'] ?? [];

if (empty($itemIds) || !is_array($itemIds)) {
    echo json_encode(['success' => false, 'message' => 'No items selected']);
    exit;
}

try {
    $db->beginTransaction();

    // 準備 SQL
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
    $stmtUpdateItem = $db->prepare("UPDATE orderitems SET Status = ? WHERE OrderListId = ?");
    $stmtUpdatePay = $db->prepare("UPDATE payments SET Status = 'paid' WHERE PaymentId = ? AND Status = 'pending'");

    foreach ($itemIds as $id) {
        $orderListId = (int)$id;

        // A. 查數據
        $stmtFetch->execute([$orderListId]);
        $itemData = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        if (!$itemData) continue;

        // B. 更新狀態
        $stmtUpdateItem->execute([$newStatus, $orderListId]);

        // C. 核心邏輯 (Cash + Ready)
        if ($newStatus === 'ready' && 
            strtolower($itemData['PaymentMethod']) === 'cash' && 
            $itemData['PaymentStatus'] === 'pending') {
            
            // 更新 Payment -> Paid
            $stmtUpdatePay->execute([$itemData['PaymentId']]);

            // D. 發送郵件 (加了防護罩)
            // 只有當數據庫真的更新了才發
            if ($stmtUpdatePay->rowCount() > 0) {
                try {
                    if (function_exists('sendReceipt')) {
                        // 使用 @ 符號抑制錯誤，並用 try-catch 包裹
                        // 這樣就算 functions.php 裡報錯，代碼也會繼續往下走，commit 數據庫
                        @sendReceipt(
                            $db, 
                            $itemData['UserId'], 
                            $itemData['PaymentId'], 
                            $itemData['TotalAmount'], 
                            [] 
                        );
                    }
                } catch (Throwable $e) {
                    // 如果 functions.php 崩潰了，我們在這裡記錄日誌
                    // 但是不拋出錯誤，讓流程繼續，這樣前端就不會 Network Error
                    error_log("Email Failed (ignored to keep flow alive): " . $e->getMessage());
                }
            }
        }
    }

    // 提交事務
    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("Batch Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}