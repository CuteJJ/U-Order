<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    flash('warning', 'Please login.');
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    // --- 1. TIME CONVERSION & ROUNDING LOGIC ---
    date_default_timezone_set('Asia/Kuala_Lumpur');
    
    $rawPickup = $_POST['pickup_time'] ?? 'ASAP';
    $pickupDateTime = null;

    if ($rawPickup === 'ASAP') {
        // ASAP = Current Time + 15 mins buffer, Rounded to nearest 5 mins
        $bufferTime = time() + (15 * 60);
        $roundedTime = ceil($bufferTime / 300) * 300;
        $pickupDateTime = date('Y-m-d H:i:00', $roundedTime);
    } else {
        // Specific time (e.g. 17:30)
        if (preg_match('/^\d{2}:\d{2}$/', $rawPickup)) {
            $pickupDateTime = date('Y-m-d') . ' ' . $rawPickup . ':00';
        } else {
            $ts = strtotime($rawPickup);
            $pickupDateTime = date('Y-m-d H:i:00', $ts);
        }
    }

    if ($action === 'add') {
        $productId = $_POST['product_id'];
        $qty = intval($_POST['quantity']);
        $note = isset($_POST['note']) ? trim($_POST['note']) : ''; 

        // Get/Create Cart
        $cartSql = "SELECT CartId FROM carts WHERE UserId = :uid";
        $stmt = $db->prepare($cartSql);
        $stmt->execute([':uid' => $userId]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cart) {
            $db->prepare("INSERT INTO carts (UserId, CreatedAt) VALUES (:uid, NOW())")->execute([':uid' => $userId]);
            $cartId = $db->lastInsertId();
        } else {
            $cartId = $cart['CartId'];
        }

        // Check Duplicate (Product + Time + Note)
        $checkSql = "SELECT CartItemId, Quantity FROM cartitems 
                     WHERE CartId = :cid AND ProductId = :pid AND PickupTime = :ptime AND Note = :note";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([':cid' => $cartId, ':pid' => $productId, ':ptime' => $pickupDateTime, ':note' => $note]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $newQty = $existing['Quantity'] + $qty;
            $sql = "UPDATE cartitems SET Quantity = :qty WHERE CartItemId = :id";
            $db->prepare($sql)->execute([':qty' => $newQty, ':id' => $existing['CartItemId']]);
        } else {
            $sql = "INSERT INTO cartitems (CartId, ProductId, Quantity, PickupTime, Note) VALUES (:cid, :pid, :qty, :ptime, :note)";
            $db->prepare($sql)->execute([':cid' => $cartId, ':pid' => $productId, ':qty' => $qty, ':ptime' => $pickupDateTime, ':note' => $note]);
        }

        flash('success', 'Added to cart!');
        header("Location: ../index.php");
        exit;
    }

    if ($action === 'update_item') {
        $cartItemId = $_POST['cart_item_id'];
        $qty = intval($_POST['quantity']);
        $note = isset($_POST['note']) ? trim($_POST['note']) : '';

        $sql = "UPDATE cartitems SET Quantity = :qty, PickupTime = :ptime, Note = :note WHERE CartItemId = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':qty' => $qty, ':ptime' => $pickupDateTime, ':note' => $note, ':id' => $cartItemId]);

        flash('success', 'Order updated!');
        // FIX: Redirect to the new cart location
        header("Location: /U-Order/cart/cart.php"); 
        exit;
    }

    if ($action === 'remove') {
        $cartItemId = $_POST['item_id'];
        $sql = "DELETE ci FROM cartitems ci JOIN carts c ON ci.CartId = c.CartId WHERE ci.CartItemId = :id AND c.UserId = :uid";
        $db->prepare($sql)->execute([':id' => $cartItemId, ':uid' => $userId]);
        flash('notice', 'Item removed.');
        
        // Safe redirect
        $referer = $_SERVER['HTTP_REFERER'] ?? '/U-Order/cart/cart.php';
        header("Location: " . $referer);
        exit;
    }
}
?>