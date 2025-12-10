<?php
include '../configs/db.php';
include '../includes/functions.php';
include '../includes/email_helper.php';

$stripeSecretKey = getenv('STRIPE_SECRET_KEY');

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// 1. GET SELECTED ITEMS
$selectedItems = $_POST['selected_items'] ?? null;

if (empty($selectedItems)) {
    flash('error', 'No items received for processing.');
    header("Location: ../cart/cart.php");
    exit;
}

if (!is_array($selectedItems)) {
    $selectedItems = explode(',', $selectedItems);
}
$selectedIds = array_map('intval', $selectedItems);
$selectedIds = array_filter($selectedIds);
$selectedIdsStr = implode(',', $selectedIds);

if (empty($selectedIds)) {
    flash('error', 'Invalid items selected.');
    header("Location: ../cart/cart.php");
    exit;
}

// --- STRIPE CONFIGURATION ---
// Helper: Create Checkout Session
function createStripeSession($amount, $secretKey, $methodTypes)
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];

    // SUCCESS URL: Points to the NEW callback file to insert data
    $successUrl = "$protocol://$host/U-Order/pages/payment_callback.php?session_id={CHECKOUT_SESSION_ID}";

    // CANCEL URL: Points back to cart or payment page (No DB changes)
    $cancelUrl  = "$protocol://$host/U-Order/pages/payment.php?error=cancelled";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/checkout/sessions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'payment_method_types' => $methodTypes,
        'line_items' => [[
            'price_data' => [
                'currency' => 'myr',
                'product_data' => ['name' => 'Canteen Order Payment'], // Generic name since order ID isn't made yet
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

// Map Inputs
switch ($paymentMethodInput) {
    case 'stripe':
        $dbPaymentMethod = 'card';
        $stripeMethods = ['card', 'fpx'];
        break;
    case 'ewallet':
        $dbPaymentMethod = 'e-wallet';
        $stripeMethods = ['grabpay'];
        break;
    default:
        $dbPaymentMethod = 'cash';
        $stripeMethods = [];
        break;
}

// Prepare Data
$pickupTime = $_SESSION['checkout_time'] ?? null;
$userNotes = $_SESSION['checkout_notes'] ?? '';
$finalNote = $userNotes; // Notes = Just the remark

try {
    // 2. GET CART ITEMS (FILTERED) to calculate total and prep data
    $sql = "SELECT ci.*, p.UnitPrice, p.StallId, p.ProductName 
            FROM carts c
            JOIN cartitems ci ON c.CartId = ci.CartId
            JOIN products p ON ci.ProductId = p.ProductId
            WHERE c.UserId = :uid 
              AND ci.CartItemId IN ($selectedIdsStr)";

    $stmt = $db->prepare($sql);
    $stmt->execute([':uid' => $userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) throw new Exception("No valid items found.");

    $totalAmount = 0;
    foreach ($cartItems as $item) $totalAmount += ($item['UnitPrice'] * $item['Quantity']);

    // ==========================================
    // PATH A: ONLINE PAYMENT (Stripe/E-Wallet)
    // ==========================================
    if ($paymentMethodInput !== 'cash') {
        // 1. Store ALL necessary data in Session (Temporary)
        $_SESSION['pending_order'] = [
            'userId' => $userId,
            'totalAmount' => $totalAmount,
            'dbPaymentMethod' => $dbPaymentMethod,
            'finalNote' => $finalNote,
            'pickupTime' => $pickupTime,
            'cartItems' => $cartItems, // The full array of items
            'selectedIdsStr' => $selectedIdsStr // IDs to delete later
        ];

        // 2. Redirect to Stripe
        $redirectUrl = createStripeSession($totalAmount, $stripeSecretKey, $stripeMethods);
        header("Location: " . $redirectUrl);
        exit;
        // STOP HERE. DB insertion happens in 'payment_callback.php'
    }

    // ==========================================
    // PATH B: CASH PAYMENT (Immediate)
    // ==========================================
    $db->beginTransaction();

    // Insert Payment
    $sql = "INSERT INTO payments (UserId, TotalAmount, Status, PaymentMethod, CreatedAt) 
            VALUES (:uid, :amt, 'pending', :method, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([':uid' => $userId, ':amt' => $totalAmount, ':method' => 'cash']);
    $paymentId = $db->lastInsertId();

    // Insert Orders & Items
    // New Logic: Group by BOTH StallId AND PickupTime
    $ordersGrouped = [];

    foreach ($cartItems as $item) {
        // We create a unique key combining Stall ID and Time
        // Example Key: "5_16:00:00" (Stall 5 at 4pm)
        // Use 'default' if no pickup time is set to prevent errors
        $timeKey = !empty($item['PickupTime']) ? $item['PickupTime'] : ($pickupTime ?? 'default');

        // Create a composite key
        $compositeKey = $item['StallId'] . '|' . $timeKey;

        $ordersGrouped[$compositeKey][] = $item;
    }

    $sqlDeduct = "UPDATE products SET Stock = Stock - :qty WHERE ProductId = :pid AND IsUnlimitedStock = 0 AND Stock >= :qty";
    $stmtDeduct = $db->prepare($sqlDeduct);

    foreach ($ordersGrouped as $compositeKey => $items) {
        // Extract StallId from the items (all items in this group have the same StallId)
        $stallId = $items[0]['StallId'];

        // INSERT ORDER
        $sql = "INSERT INTO orders (PaymentId, UserId, StallId, Status, Notes, CreatedAt) VALUES (:pid, :uid, :sid, 'pending', :notes, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([':pid' => $paymentId, ':uid' => $userId, ':sid' => $stallId, ':notes' => $finalNote]);
        $orderId = $db->lastInsertId();

        $sqlList = "INSERT INTO orderitems (OrderId, ProductId, Quantity, Subtotal, Note, PickupTime) VALUES (:oid, :prod, :qty, :sub, :note, :time)";
        $stmtList = $db->prepare($sqlList);

        foreach ($items as $item) {
            $pTime = !empty($item['PickupTime']) ? $item['PickupTime'] : $pickupTime;

            $stmtList->execute([
                ':oid' => $orderId,
                ':prod' => $item['ProductId'],
                ':qty' => $item['Quantity'],
                ':sub' => ($item['UnitPrice'] * $item['Quantity']),
                ':note' => $item['Note'],
                ':time' => $pTime
            ]);
            $stmtDeduct->execute([':qty' => $item['Quantity'], ':pid' => $item['ProductId']]);
        }
    }

    // Delete Processed Items
    $db->exec("DELETE FROM cartitems WHERE CartItemId IN ($selectedIdsStr)");
    unset($_SESSION['checkout_notes']);
    unset($_SESSION['checkout_time']);

    $db->commit();
    //Vendor Dashboard的在这里拿
    //Add sendReceipt() to your Vendor Dashboard
    // In your vendor order update logic:
    // if ($newStatus === 'paid' && $oldStatus !== 'paid' && $paymentMethod === 'cash') {
    //     sendReceipt($db, $userId, $paymentId, $totalAmount, $items);
    // }
    header("Location: order_success.php?payment_id=" . $paymentId);
    exit;
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("Payment Error: " . $e->getMessage());
    flash('error', 'Payment Failed: ' . $e->getMessage());
    header("Location: payment.php");
    exit;
}
