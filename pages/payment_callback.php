<?php
include '../configs/db.php';
include '../includes/functions.php';
// FIX: Include the email helper so sendReceipt() is defined
include '../includes/email_helper.php'; 

if (!isLoggedIn()) { header("Location: login.php"); exit; }

// Check if we have the pending order data
if (!isset($_SESSION['pending_order'])) {
    flash('error', 'Session expired or invalid order data.');
    header("Location: ../cart/cart.php");
    exit;
}

$orderData = $_SESSION['pending_order'];

try {
    $db->beginTransaction();

    // 1. Create Payment Record (Status = Paid)
    $sql = "INSERT INTO payments (UserId, TotalAmount, Status, PaymentMethod, CreatedAt) 
            VALUES (:uid, :amt, 'paid', :method, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':uid' => $orderData['userId'], 
        ':amt' => $orderData['totalAmount'], 
        ':method' => $orderData['dbPaymentMethod']
    ]);
    $paymentId = $db->lastInsertId();

    // 2. Group items by Stall
    $ordersByStall = [];
    foreach ($orderData['cartItems'] as $item) { 
        $ordersByStall[$item['StallId']][] = $item; 
    }

    // Prepare Statements
    $sqlDeduct = "UPDATE products SET Stock = Stock - :qty WHERE ProductId = :pid AND IsUnlimitedStock = 0 AND Stock >= :qty";
    $stmtDeduct = $db->prepare($sqlDeduct);

    $sqlOrder = "INSERT INTO orders (PaymentId, UserId, StallId, Status, Notes, CreatedAt) VALUES (:pid, :uid, :sid, 'pending', :notes, NOW())";
    $stmtOrder = $db->prepare($sqlOrder);

    $sqlItem = "INSERT INTO orderitems (OrderId, ProductId, Quantity, Subtotal, Note, PickupTime) VALUES (:oid, :prod, :qty, :sub, :note, :time)";
    $stmtItem = $db->prepare($sqlItem);

    // 3. Insert Orders & Items
    foreach ($ordersByStall as $stallId => $items) {
        $stmtOrder->execute([
            ':pid' => $paymentId, 
            ':uid' => $orderData['userId'], 
            ':sid' => $stallId, 
            ':notes' => $orderData['finalNote']
        ]);
        $orderId = $db->lastInsertId();

        foreach ($items as $item) {
            $stmtItem->execute([
                ':oid' => $orderId, 
                ':prod' => $item['ProductId'], 
                ':qty' => $item['Quantity'], 
                ':sub' => ($item['UnitPrice'] * $item['Quantity']),
                ':note' => $item['Note'],
                ':time' => !empty($item['PickupTime']) ? $item['PickupTime'] : $orderData['pickupTime']
            ]);
            $stmtDeduct->execute([
                ':qty' => $item['Quantity'], 
                ':pid' => $item['ProductId']
            ]);
        }
    }

    // 4. Delete processed items from Cart
    $ids = $orderData['selectedIdsStr'];
    $db->exec("DELETE FROM cartitems WHERE CartItemId IN ($ids)");

    $db->commit();

    // --- SEND EMAIL (Online) ---
    // Note: We use $orderData here because it comes from the session
    sendReceipt($db, $orderData['userId'], $paymentId, $orderData['totalAmount'], $orderData['cartItems']);


    // 5. Cleanup Session
    unset($_SESSION['pending_order']);
    unset($_SESSION['checkout_notes']);
    unset($_SESSION['checkout_time']);

    // Success!
    header("Location: order_success.php?payment_id=" . $paymentId);
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("Callback Error: " . $e->getMessage());
    flash('error', 'Error processing order: ' . $e->getMessage());
    header("Location: payment.php");
    exit;
}
?>