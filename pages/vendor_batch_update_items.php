<?php
// pages/vendor_batch_update_items.php

// 1. 設置腳本可以後台運行，不受瀏覽器關閉影響
ignore_user_abort(true); 
set_time_limit(0); 

session_start();
require_once '../configs/db.php';

// 開啟日誌
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 加載庫
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

// 權限檢查
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

// 準備一個數組，用來暫存「等一下要發郵件」的清單
// 我們不在數據庫事務裡發郵件，因為那會卡住數據庫
$mailQueue = [];
$processedPayments = [];
$affectedOrderIds = [];

try {
    $db->beginTransaction();

    $fetchSql = "
        SELECT 
            oi.OrderListId, o.OrderId, o.PaymentId, o.UserId,
            pm.TotalAmount, pm.Status as PaymentStatus, pm.PaymentMethod,
            u.Email, u.Name
        FROM orderitems oi
        JOIN orders o    ON o.OrderId = oi.OrderId
        JOIN payments pm ON pm.PaymentId = o.PaymentId
        JOIN users u     ON u.UserId = o.UserId  
        WHERE oi.OrderListId = ?
    ";
    $stmtFetch = $db->prepare($fetchSql);
    $stmtUpdateItem = $db->prepare("UPDATE orderitems SET Status = ? WHERE OrderListId = ?");
    $stmtUpdatePay  = $db->prepare("UPDATE payments SET Status = 'paid' WHERE PaymentId = ?");
    $stmtCheckOrder = $db->prepare("SELECT COUNT(*) FROM orderitems WHERE OrderId = ? AND Status != 'ready'");
    $stmtUpdateOrder = $db->prepare("UPDATE orders SET Status = 'ready' WHERE OrderId = ?");

    foreach ($itemIds as $id) {
        $orderListId = (int)$id;
        $stmtFetch->execute([$orderListId]);
        $data = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        if (!$data) continue;

        // 更新菜品狀態
        $stmtUpdateItem->execute([$newStatus, $orderListId]);
        
        if (!in_array($data['OrderId'], $affectedOrderIds)) {
            $affectedOrderIds[] = $data['OrderId'];
        }

        // 收集郵件發送需求 (注意：這裡不發送，只收集)
        $isReady   = ($newStatus === 'ready');
        $isCash    = (strtolower($data['PaymentMethod']) === 'cash');
        $isPending = ($data['PaymentStatus'] === 'pending');
        $pid       = $data['PaymentId'];

        if ($isReady && $isCash && $isPending) {
            if (!in_array($pid, $processedPayments)) {
                $stmtUpdatePay->execute([$pid]); // 數據庫先改狀態
                
                // 把發信需要的資料存起來，等一下再發
                $mailQueue[] = [
                    'email' => $data['Email'],
                    'name'  => $data['Name'],
                    'pid'   => $pid,
                    'amount'=> $data['TotalAmount']
                ];
                
                $processedPayments[] = $pid;
            }
        }
    }

    // 更新主訂單狀態
    foreach ($affectedOrderIds as $oid) {
        $stmtCheckOrder->execute([$oid]);
        $notReadyCount = $stmtCheckOrder->fetchColumn();
        if ($notReadyCount == 0) {
            $stmtUpdateOrder->execute([$oid]);
        }
    }

    $db->commit();
    
    // =================================================================
    // 【關鍵優化】 這裡開始「騙」瀏覽器說我們做完了
    // =================================================================
    
    // 1. 準備返回的 JSON
    $response = json_encode(['success' => true]);
    
    // 2. 關閉 Session 寫入，避免鎖死其他頁面
    session_write_close();
    
    // 3. 清空並關閉緩衝區
    ob_end_clean();
    
    // 4. 告訴瀏覽器：「內容長度就這麼多，你可以斷線了」
    header("Connection: close");
    header("Content-Encoding: none");
    header("Content-Length: " . strlen($response));
    
    // 5. 輸出內容並強制刷新
    echo $response;
    flush();
    
    // --- 此刻，瀏覽器的 Loading 圈圈已經消失，用戶覺得已經完成了 ---
    // --- 下面的代碼是在服務器後台默默執行的 ---

    if (!empty($mailQueue)) {
        if (function_exists('get_mail')) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            
            foreach ($mailQueue as $task) {
                try {
                    // 稍微休息 0.5秒，避免瞬間發送太快被 SMTP 服務器拒絕
                    usleep(500000); 

                    $mail = get_mail(); // 每次都拿新的 mail 實例
                    $link = "$protocol://$host/U-Order/pages/view_receipt.php?payment_id=" . $task['pid'];

                    $mail->addAddress($task['email'], $task['name']);
                    $mail->isHTML(true);
                    $mail->Subject = "Order Ready & Payment Confirmed #" . $task['pid'];
                    $mail->Body = "
                        <h3>Hello " . htmlspecialchars($task['name']) . ",</h3>
                        <p>Your cash order is now <strong>READY</strong>.</p>
                        <p><strong>Payment Status Updated: PAID</strong></p>
                        <p>Order ID: #" . $task['pid'] . "<br>Total: RM " . number_format($task['amount'], 2) . "</p>
                        <p><a href='$link' style='background:#6772e5;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>View Receipt</a></p>
                    ";
                    $mail->send();
                    error_log("Background Mail Sent to: " . $task['email']);
                    
                } catch (Exception $e) {
                    error_log("Background Mail Error: " . $e->getMessage());
                }
            }
        }
    }

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("Batch Error: " . $e->getMessage());
    // 如果出錯，因為還沒騙瀏覽器，所以這裡返回正常的錯誤 JSON
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>