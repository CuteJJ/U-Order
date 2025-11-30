<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// --- STRIPE CONFIGURATION(Private Key) ---

// 1. Function to Charge CARD (Background)
function chargeStripeCard($token, $amount, $secretKey) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/charges");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'amount' => round($amount * 100), 
        'currency' => 'myr',
        'source' => $token,
        'description' => 'Canteen Card Payment'
    ]));
    curl_setopt($ch, CURLOPT_USERPWD, $secretKey . ':');
    $result = curl_exec($ch);
    $response = json_decode($result, true);
    curl_close($ch);
    
    if (isset($response['error'])) {
        throw new Exception("Stripe Error: " . $response['error']['message']);
    }
    return $response;
}

// 2. Function to Create E-WALLET Session (Redirect)
function createStripeSession($amount, $secretKey, $paymentId) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $successUrl = "$protocol://$host/U-Order/pages/order_success.php?payment_id=$paymentId&status=success";
    $cancelUrl  = "$protocol://$host/U-Order/pages/payment.php?error=cancelled";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/checkout/sessions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'payment_method_types' => ['grabpay', 'fpx'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'myr',
                'product_data' => ['name' => 'Canteen Order #' . $paymentId],
                'unit_amount' => round($amount * 100),
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
    ]));
    curl_setopt($ch, CURLOPT_USERPWD, $secretKey . ':');
    $result = curl_exec($ch);
    $response = json_decode($result, true);
    curl_close($ch);

    if (isset($response['error'])) {
        throw new Exception("Stripe Session Error: " . $response['error']['message']);
    }
    return $response['url'];
}

$userId = $_SESSION['user_id'];
$paymentMethodInput = $_POST['payment_method'] ?? 'cash';

// --- MAP FRONTEND VALUE TO DATABASE ENUM ---
// payment.php sends: 'stripe', 'ewallet', 'cash'
// Database table 'payments' column 'PaymentMethod' expects: 'card', 'e-wallet', 'cash'
switch ($paymentMethodInput) {
    case 'stripe':
        $dbPaymentMethod = 'card';
        break;
    case 'ewallet':
        $dbPaymentMethod = 'e-wallet';
        break;
    default:
        $dbPaymentMethod = 'cash';
        break;
}

// Retrieve Checkout Data
$pickupTime = $_SESSION['checkout_time'] ?? 'ASAP';
$userNotes = $_SESSION['checkout_notes'] ?? '';
$finalNote = "Pickup: " . $pickupTime . " | Method: " . ucfirst($dbPaymentMethod);
if (!empty($userNotes)) $finalNote .= " | Note: " . $userNotes;

// Determine Initial Status
if ($paymentMethodInput === 'stripe' || $paymentMethodInput === 'ewallet') {
    $paymentStatus = 'paid';
}else {
    $paymentStatus = 'pending'; // Cash is pending
}

try {
    $db->beginTransaction();

    // Get Cart
    $sql = "SELECT ci.*, p.UnitPrice, p.StallId, p.ProductName FROM carts c JOIN cartitems ci ON c.CartId = ci.CartId JOIN products p ON ci.ProductId = p.ProductId WHERE c.UserId = :uid";
    $stmt = $db->prepare($sql);
    $stmt->execute([':uid' => $userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) throw new Exception("Cart is empty.");

    $totalAmount = 0;
    foreach ($cartItems as $item) $totalAmount += ($item['UnitPrice'] * $item['Quantity']);

    // --- HANDLE STRIPE CARD (Immediate Charge) ---
    if ($paymentMethodInput === 'stripe') {
        if (!isset($_POST['stripeToken'])) throw new Exception("No payment token.");
        chargeStripeCard($_POST['stripeToken'], $totalAmount, $stripeSecretKey);
    }

    // --- CREATE PAYMENT RECORD (WITH PAYMENT METHOD) ---
    $sql = "INSERT INTO payments (UserId, TotalAmount, Status, PaymentMethod, CreatedAt) 
            VALUES (:uid, :amt, :status, :method, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':uid' => $userId, 
        ':amt' => $totalAmount, 
        ':status' => $paymentStatus,
        ':method' => $dbPaymentMethod // Storing the mapped value ('card', 'e-wallet', or 'cash')
    ]);
    $paymentId = $db->lastInsertId();

    // Create Orders per Stall
    $ordersByStall = [];
    foreach ($cartItems as $item) { $ordersByStall[$item['StallId']][] = $item; }

    $sqlDeduct = "UPDATE products SET Stock = Stock - :qty WHERE ProductId = :pid AND IsUnlimitedStock = 0 AND Stock >= :qty";
    $stmtDeduct = $db->prepare($sqlDeduct);

    foreach ($ordersByStall as $stallId => $items) {
        $sql = "INSERT INTO orders (PaymentId, UserId, StallId, Status, Notes, CreatedAt) VALUES (:pid, :uid, :sid, 'pending', :notes, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([':pid' => $paymentId, ':uid' => $userId, ':sid' => $stallId, ':notes' => $finalNote]);
        $orderId = $db->lastInsertId();

        $sqlList = "INSERT INTO orderitems (OrderId, ProductId, Quantity, Subtotal) VALUES (:oid, :prod, :qty, :sub)";
        $stmtList = $db->prepare($sqlList);

        foreach ($items as $item) {
            $stmtList->execute([':oid' => $orderId, ':prod' => $item['ProductId'], ':qty' => $item['Quantity'], ':sub' => ($item['UnitPrice'] * $item['Quantity'])]);
            $stmtDeduct->execute([':qty' => $item['Quantity'], ':pid' => $item['ProductId']]);
        }
    }

    // Clear Cart & Session
    $stmt = $db->prepare("DELETE FROM cartitems WHERE CartId = :cid");
    $stmt->execute([':cid' => $cartItems[0]['CartId']]);
    unset($_SESSION['checkout_notes']);
    unset($_SESSION['checkout_time']);

    $db->commit();

    // --- HANDLE E-WALLET REDIRECT (Post-Database) ---
    if ($paymentMethodInput === 'ewallet') {
        // Generate the Real Stripe Checkout Link (GrabPay/FPX)
        $redirectUrl = createStripeSession($totalAmount, $stripeSecretKey, $paymentId);
        header("Location: " . $redirectUrl);
        exit;
    }

    // For Card/Cash, go straight to success
    header("Location: order_success.php?payment_id=" . $paymentId);
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("Payment Error: " . $e->getMessage()); 
    flash('error', 'Payment Error: ' . $e->getMessage());
    header("Location: payment.php");
    exit;
}
?>