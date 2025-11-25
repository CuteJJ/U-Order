<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// --- STRIPE CONFIGURATION (Private Key) ---

// Helper Function to Charge Stripe via cURL (No Composer needed)
function chargeStripe($token, $amount, $secretKey) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/charges");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    // Stripe expects amount in CENTS (e.g., 10.00 becomes 1000)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'amount' => round($amount * 100), 
        'currency' => 'myr',
        'source' => $token,
        'description' => 'Canteen Order Payment'
    ]));
    curl_setopt($ch, CURLOPT_USERPWD, $secretKey . ':');
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $response = json_decode($result, true);
    
    if ($httpCode !== 200) {
        throw new Exception("Stripe Error: " . ($response['error']['message'] ?? 'Unknown error'));
    }
    
    return $response;
}

$userId = $_SESSION['user_id'];
$paymentMethod = $_POST['payment_method'] ?? 'cash';

// Retrieve Checkout Data from Session
$pickupTime = $_SESSION['checkout_time'] ?? 'ASAP';
$userNotes = $_SESSION['checkout_notes'] ?? '';

$finalNote = "Pickup: " . $pickupTime . " | Method: " . ucfirst($paymentMethod);
if (!empty($userNotes)) {
    $finalNote .= " | Note: " . $userNotes;
}

$paymentStatus = ($paymentMethod === 'cash') ? 'pending' : 'paid';

try {
    $db->beginTransaction();

    // 1. Get Cart Items & Calculate Total
    $sql = "SELECT ci.*, p.UnitPrice, p.StallId, p.ProductName 
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

    $totalAmount = 0;
    foreach ($cartItems as $item) {
        $totalAmount += ($item['UnitPrice'] * $item['Quantity']);
    }

    // --- REAL STRIPE CHARGE ---
    if ($paymentMethod === 'stripe') {
        if (!isset($_POST['stripeToken'])) {
            throw new Exception("No payment token received.");
        }
        
        // Attempt to charge the card
        // If this fails, it jumps to catch() block and cancels everything
        chargeStripe($_POST['stripeToken'], $totalAmount, $stripeSecretKey);
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

    // 5. Create Orders & Deduct Stock
    $sqlDeduct = "UPDATE products SET Stock = Stock - :qty WHERE ProductId = :pid AND IsUnlimitedStock = 0 AND Stock >= :qty";
    $stmtDeduct = $db->prepare($sqlDeduct);

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

        $sqlList = "INSERT INTO orderitems (OrderId, ProductId, Quantity, Subtotal) VALUES (:oid, :prod, :qty, :sub)";
        $stmtList = $db->prepare($sqlList);

        foreach ($items as $item) {
            $subtotal = $item['UnitPrice'] * $item['Quantity'];
            $stmtList->execute([
                ':oid' => $orderId,
                ':prod' => $item['ProductId'],
                ':qty' => $item['Quantity'],
                ':sub' => $subtotal
            ]);

            $stmtDeduct->execute([':qty' => $item['Quantity'], ':pid' => $item['ProductId']]);
        }
    }

    // 6. Clear Cart & Session
    $cartId = $cartItems[0]['CartId'];
    $sql = "DELETE FROM cartitems WHERE CartId = :cid";
    $stmt = $db->prepare($sql);
    $stmt->execute([':cid' => $cartId]);

    unset($_SESSION['checkout_notes']);
    unset($_SESSION['checkout_time']);

    $db->commit();
    header("Location: order_success.php?payment_id=" . $paymentId);
    exit;

} catch (Exception $e) {
    $db->rollBack();
    error_log("Payment Error: " . $e->getMessage());
    header("Location: payment.php");
    exit;
}
?>