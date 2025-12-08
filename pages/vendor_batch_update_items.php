<?php
// pages/vendor_batch_update_items.php
session_start();
require_once '../configs/db.php';

// 開啟日誌，方便出錯時查看
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 1. 確保加載 PHPMailer 和 functions.php
$libDir = __DIR__ . '/../lib/';
if (file_exists($libDir . 'Exception.php')) require_once $libDir . 'Exception.php';
if (file_exists($libDir . 'PHPMailer.php')) require_once $libDir . 'PHPMailer.php';
if (file_exists($libDir . 'SMTP.php'))      require_once $libDir . 'SMTP.php';

if (file_exists(__DIR__ . '/../includes/functions.php')) {
    require_once __DIR__ . '/../includes/functions.php';
} elseif (file_exists(__DIR__ . '/../functions.php')) {
    require_once __DIR__ . '/../functions.php';
}

header('Content-Type: application/json');

// 2. 權限檢查
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$newStatus = $_POST['status'] ?? '';
$itemIds   = $_POST['item_ids'] ?? [];

if (empty($itemIds) || !is_array($itemIds)) {
    echo json_encode(['success' => false, 'message' => 'No items selected']);
    exit;
}

// 用來防止同一個訂單重複發信
$processedPayments = [];
// 用來記錄哪些主訂單需要檢查狀態 (避免重複檢查)
$affectedOrderIds = [];

try {
    $db->beginTransaction();

    // 準備 SQL
    // A. 獲取詳細資料 (包括 Email, PaymentMethod, PaymentStatus)
    $fetchSql = "
        SELECT 
            oi.OrderListId, 
            o.OrderId, 
            o.PaymentId,
            o.UserId,
            pm.TotalAmount,
            pm.Status as PaymentStatus,
            pm.PaymentMethod,
            u.Email,      
            u.Name
        FROM orderitems oi
        JOIN orders o    ON o.OrderId = oi.OrderId
        JOIN payments pm ON pm.PaymentId = o.PaymentId
        JOIN users u     ON u.UserId = o.UserId  
        WHERE oi.OrderListId = ?
    ";
    $stmtFetch = $db->prepare($fetchSql);
    
    // B. 更新菜品狀態
    $stmtUpdateItem = $db->prepare("UPDATE orderitems SET Status = ? WHERE OrderListId = ?");
    
    // C. 更新支付狀態
    $stmtUpdatePay  = $db->prepare("UPDATE payments SET Status = 'paid' WHERE PaymentId = ?");

    // D. 檢查主訂單是否全部完成的 SQL (檢查該訂單還有多少個 '非 ready' 的菜品)
    $stmtCheckOrder = $db->prepare("SELECT COUNT(*) FROM orderitems WHERE OrderId = ? AND Status != 'ready'");
    // E. 更新主訂單狀態
    $stmtUpdateOrder = $db->prepare("UPDATE orders SET Status = 'ready' WHERE OrderId = ?");

    foreach ($itemIds as $id) {
        $orderListId = (int)$id;

        // 1. 獲取數據
        $stmtFetch->execute([$orderListId]);
        $data = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        if (!$data) continue;

        // 2. 更新當前菜品狀態 (如 Cooking -> Ready)
        $stmtUpdateItem->execute([$newStatus, $orderListId]);
        
        // 收集 OrderId 以便稍後檢查主訂單狀態
        if (!in_array($data['OrderId'], $affectedOrderIds)) {
            $affectedOrderIds[] = $data['OrderId'];
        }

        // =================================================================
        // 3. 核心業務邏輯：發送郵件 (條件已加回)
        // 條件：狀態是 Ready + 是 Cash + 還是 Pending
        // =================================================================
        $isReady   = ($newStatus === 'ready');
        $isCash    = (strtolower($data['PaymentMethod']) === 'cash');
        $isPending = ($data['PaymentStatus'] === 'pending');
        $pid       = $data['PaymentId'];

        if ($isReady && $isCash && $isPending) {
            
            // 確保這個訂單這一次還沒發過信
            if (!in_array($pid, $processedPayments)) {
                
                // 3.1 更新 Payment 為 paid
                $stmtUpdatePay->execute([$pid]);

                // 3.2 發送郵件 (直接調用 get_mail，不依賴外部不穩定的函數)
                $email = $data['Email'];
                $name  = $data['Name'];
                
                if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    try {
                        if (function_exists('get_mail')) {
                            $mail = get_mail();
                            
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $host = $_SERVER['HTTP_HOST'];
                            $link = "$protocol://$host/U-Order/pages/view_receipt.php?payment_id=" . $pid;

                            $mail->addAddress($email, $name);
                            $mail->isHTML(true);
                            $mail->Subject = "Order Ready & Payment Confirmed #" . $pid;
                            $mail->Body = "
                                <h3>Hello $name,</h3>
                                <p>Your cash order is now <strong>READY</strong>.</p>
                                <p><strong>Payment Status Updated: PAID</strong></p>
                                <p>Order ID: #$pid<br>Total: RM " . number_format($data['TotalAmount'], 2) . "</p>
                                <p><a href='$link' style='background:#6772e5;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>View Receipt</a></p>
                            ";
                            $mail->send();
                        }
                    } catch (Exception $e) {
                        error_log("Mail Error for ID $pid: " . $e->getMessage());
                    }
                }
                
                // 標記已處理
                $processedPayments[] = $pid;
            }
        }
    }

    // =================================================================
    // 4. 新增後端邏輯：檢查並更新主訂單 (Orders) 狀態
    // 邏輯：如果該訂單下的所有菜品都已經是 'ready'，則將主訂單狀態改為 'ready'
    // =================================================================
    foreach ($affectedOrderIds as $oid) {
        // 查詢該訂單下，狀態 *不是* ready 的菜品數量
        $stmtCheckOrder->execute([$oid]);
        $notReadyCount = $stmtCheckOrder->fetchColumn();

        // 如果數量為 0，代表所有菜品都好了
        if ($notReadyCount == 0) {
            $stmtUpdateOrder->execute([$oid]);
            error_log("Order #$oid all items ready. Updated main order status to 'ready'.");
        }
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("Batch Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>