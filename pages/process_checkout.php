<?php
session_start();
include '../configs/db.php';

$userId = 4; // Alice Student
$grandTotal = $_POST['grand_total'];
$pickupTime = $_POST['pickup_time'];
$paymentMethod = $_POST['payment_method'];
$userNotes = $_POST['notes'];

try {
    // START TRANSACTION (Important! If one step fails, everything cancels)
    $db->beginTransaction();

    // 1. Create the Payment Record
    // Since your SQL is missing 'PaymentMethod', we only save Amount and Status
    $paymentSql = "INSERT INTO payments (UserId, TotalAmount, Status, CreatedAt) 
                   VALUES (:uid, :amount, 'paid', NOW())";
    $stmt = $db->prepare($paymentSql);
    $stmt->execute([':uid' => $userId, ':amount' => $grandTotal]);
    $paymentId = $db->lastInsertId();

    // 2. Get all items in Cart to process them
    $cartSql = "SELECT ci.*, p.UnitPrice, p.StallId 
                FROM cartitems ci
                JOIN carts c ON ci.CartId = c.CartId
                JOIN products p ON ci.ProductId = p.ProductId
                WHERE c.UserId = :uid";
    $stmt = $db->prepare($cartSql);
    $stmt->execute([':uid' => $userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Group Items by Stall ID
    // We do this because one order belongs to one stall.
    // If user buys from 2 stalls, we create 2 Orders linked to 1 Payment.
    $ordersByStall = [];
    foreach ($cartItems as $item) {
        $ordersByStall[$item['StallId']][] = $item;
    }

    // 4. Loop through each Stall group and create Orders
    foreach ($ordersByStall as $stallId => $items) {
        
        // Combine User Notes with Payment Info (Workaround for missing DB columns)
        $finalNotes = "Pickup: $pickupTime | Method: $paymentMethod | Note: $userNotes";

        // Create Order
        $orderSql = "INSERT INTO orders (PaymentId, UserId, StallId, Status, Notes, CreatedAt) 
                     VALUES (:pid, :uid, :sid, 'pending', :notes, NOW())";
        $stmt = $db->prepare($orderSql);
        $stmt->execute([
            ':pid' => $paymentId,
            ':uid' => $userId,
            ':sid' => $stallId,
            ':notes' => $finalNotes
        ]);
        $orderId = $db->lastInsertId();

        // Insert into OrderLists (The actual food items)
        foreach ($items as $food) {
            $subtotal = $food['Quantity'] * $food['UnitPrice'];
            
            $listSql = "INSERT INTO orderlists (OrderId, ProductId, Quantity, Subtotal) 
                        VALUES (:oid, :pid, :qty, :sub)";
            $stmt = $db->prepare($listSql);
            $stmt->execute([
                ':oid' => $orderId,
                ':pid' => $food['ProductId'],
                ':qty' => $food['Quantity'],
                ':sub' => $subtotal
            ]);
        }
    }

    // 5. Clear the User's Cart
    // First delete items
    $clearItemsSql = "DELETE ci FROM cartitems ci 
                      JOIN carts c ON ci.CartId = c.CartId 
                      WHERE c.UserId = :uid";
    $stmt = $db->prepare($clearItemsSql);
    $stmt->execute([':uid' => $userId]);

    // We don't delete the main 'carts' row, just the items inside it.

    // COMMIT TRANSACTION (Save changes)
    $db->commit();

    // Redirect to a success page (or back to home with a message)
    echo "<script>alert('Order Placed Successfully!'); window.location.href='../index.php';</script>";

} catch (Exception $e) {
    // If error, undo everything
    $db->rollBack();
    echo "Failed to place order: " . $e->getMessage();
}
?>