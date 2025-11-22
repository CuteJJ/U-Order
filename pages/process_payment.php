<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$paymentMethod = $_POST['payment_method'] ?? 'cash';

// Retrieve Checkout Data from Session
$pickupTime = $_SESSION['checkout_time'] ?? 'ASAP';
$userNotes = $_SESSION['checkout_notes'] ?? '';

// Format Notes
$finalNote = "Pickup: " . $pickupTime . " | Method: " . ucfirst($paymentMethod);
if (!empty($userNotes)) {
    $finalNote .= " | Note: " . $userNotes;
}

// Determine Payment Status
// If Cash -> Pending (Pay at counter)
// If Stripe/E-wallet -> Paid (Simulated success)
$paymentStatus = ($paymentMethod === 'cash') ? 'pending' : 'paid';

// Optional: Check for Stripe Token if method is stripe
if ($paymentMethod === 'stripe' && !isset($_POST['stripeToken'])) {
    flash('error', 'Stripe payment failed. No token received.');
    header("Location: payment.php");
    exit;
}

try {
    $db->beginTransaction();

    // 1. Get Cart Items
    $sql = "SELECT ci.*, p.UnitPrice, p.StallId 
            FROM carts c
            JOIN cartitems ci ON c.CartId = ci.CartId
            JOIN products p ON ci.ProductId = p.ProductId
            WHERE c.UserId = :uid";
    $stmt = $db->prepare($sql);
    $stmt->execute([':uid' => $userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        throw new Exception("Cart is empty.");
    }

    // 2. Calculate Total
    $totalAmount = 0;
    foreach ($cartItems as $item) {
        $totalAmount += ($item['UnitPrice'] * $item['Quantity']);
    }

    // 3. Create Payment Record
    $sql = "INSERT INTO payments (UserId, TotalAmount, Status, CreatedAt) VALUES (:uid, :amt, :status, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':uid' => $userId, 
        ':amt' => $totalAmount,
        ':status' => $paymentStatus
    ]);
    $paymentId = $db->lastInsertId();

    // 4. Group by Stall
    $ordersByStall = [];
    foreach ($cartItems as $item) {
        $stallId = $item['StallId'];
        if (!isset($ordersByStall[$stallId])) {
            $ordersByStall[$stallId] = [];
        }
        $ordersByStall[$stallId][] = $item;
    }

    // 5. Create Orders
    foreach ($ordersByStall as $stallId => $items) {
        $sql = "INSERT INTO orders (PaymentId, UserId, StallId, Status, Notes, CreatedAt) 
                VALUES (:pid, :uid, :sid, 'pending', :notes, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':pid' => $paymentId, 
            ':uid' => $userId, 
            ':sid' => $stallId,
            ':notes' => $finalNote
        ]);
        $orderId = $db->lastInsertId();

        $sqlList = "INSERT INTO orderlists (OrderId, ProductId, Quantity, Subtotal) VALUES (:oid, :prod, :qty, :sub)";
        $stmtList = $db->prepare($sqlList);

        foreach ($items as $item) {
            $subtotal = $item['UnitPrice'] * $item['Quantity'];
            $stmtList->execute([
                ':oid' => $orderId,
                ':prod' => $item['ProductId'],
                ':qty' => $item['Quantity'],
                ':sub' => $subtotal
            ]);
        }
    }

    // 6. Clear Cart
    $cartId = $cartItems[0]['CartId'];
    $sql = "DELETE FROM cartitems WHERE CartId = :cid";
    $stmt = $db->prepare($sql);
    $stmt->execute([':cid' => $cartId]);

    // 7. Clear Session
    unset($_SESSION['checkout_notes']);
    unset($_SESSION['checkout_time']);

    $db->commit();
    
    header("Location: order_success.php?payment_id=" . $paymentId);
    exit;

} catch (Exception $e) {
    $db->rollBack();
    flash('error', 'Payment Failed: ' . $e->getMessage());
    header("Location: payment.php");
    exit;
}
?>