<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    flash('warning', 'Please login.');
    header("Location: ../pages/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// =============================
// FIX: Validate user exists
// =============================
$checkUser = $db->prepare("SELECT UserId FROM users WHERE UserId = :uid LIMIT 1");
$checkUser->execute([':uid' => $userId]);
if (!$checkUser->fetch()) {
    session_destroy();
    flash('error', 'Your session expired. Please login again.');
    header("Location: /login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'];

    // --- TIME CONVERSION ---
    date_default_timezone_set('Asia/Kuala_Lumpur');
    
    $rawPickup = $_POST['pickup_time'] ?? 'ASAP';
    $pickupDateTime = null;

    if ($rawPickup === 'ASAP') {
        $bufferTime = time() + (15 * 60);
        $roundedTime = ceil($bufferTime / 300) * 300;
        $pickupDateTime = date('Y-m-d H:i:00', $roundedTime);
    } else {
        if (preg_match('/^\d{2}:\d{2}$/', $rawPickup)) {
            $pickupDateTime = date('Y-m-d') . ' ' . $rawPickup . ':00';
        } else {
            $pickupDateTime = date('Y-m-d H:i:00', strtotime($rawPickup));
        }
    }

    // ======================================================
    // ADD ITEM
    // ======================================================
    if ($action === 'add') {

        $productId = $_POST['product_id'];
        $qty = intval($_POST['quantity']);
        $note = trim($_POST['note'] ?? '');

        // Get / Create Cart
        $cartSql = "SELECT CartId FROM carts WHERE UserId = :uid";
        $stmt = $db->prepare($cartSql);
        $stmt->execute([':uid' => $userId]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cart) {
            // FIXED: remove NOW(), carts 没有 CreatedAt column
            $insertCart = $db->prepare("INSERT INTO carts (UserId) VALUES (:uid)");
            $insertCart->execute([':uid' => $userId]);
            $cartId = $db->lastInsertId();
        } else {
            $cartId = $cart['CartId'];
        }

        // Check Duplicate
        $checkSql = "SELECT CartItemId, Quantity FROM cartitems 
                     WHERE CartId = :cid 
                       AND ProductId = :pid 
                       AND PickupTime = :ptime 
                       AND Note = :note";

        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([
            ':cid' => $cartId,
            ':pid' => $productId,
            ':ptime' => $pickupDateTime,
            ':note' => $note
        ]);

        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $newQty = $existing['Quantity'] + $qty;

            $sql = "UPDATE cartitems SET Quantity = :qty WHERE CartItemId = :id";
            $db->prepare($sql)->execute([
                ':qty' => $newQty,
                ':id'  => $existing['CartItemId']
            ]);

        } else {
            $sql = "INSERT INTO cartitems 
                    (CartId, ProductId, Quantity, PickupTime, Note) 
                    VALUES (:cid, :pid, :qty, :ptime, :note)";

            $db->prepare($sql)->execute([
                ':cid' => $cartId,
                ':pid' => $productId,
                ':qty' => $qty,
                ':ptime'=> $pickupDateTime,
                ':note' => $note
            ]);
        }

        flash('success', 'Added to cart!');
        header("Location: ../index.php");
        exit;
    }

    // ======================================================
    // UPDATE ITEM
    // ======================================================
    if ($action === 'update_item') {

        $cartItemId = $_POST['cart_item_id'];
        $qty = intval($_POST['quantity']);
        $note = trim($_POST['note'] ?? '');

        $sql = "UPDATE cartitems
                SET Quantity = :qty, PickupTime = :ptime, Note = :note
                WHERE CartItemId = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':qty' => $qty,
            ':ptime' => $pickupDateTime,
            ':note' => $note,
            ':id' => $cartItemId
        ]);

        flash('success', 'Order updated!');
        header("Location: /U-Order/cart/cart.php");
        exit;
    }

    // ======================================================
    // REMOVE ITEM
    // ======================================================
    if ($action === 'remove') {

        $cartItemId = $_POST['item_id'];

        $sql = "DELETE ci 
                FROM cartitems ci 
                JOIN carts c ON ci.CartId = c.CartId
                WHERE ci.CartItemId = :id
                  AND c.UserId = :uid";

        $db->prepare($sql)->execute([
            ':id' => $cartItemId,
            ':uid'=> $userId
        ]);

        flash('notice', 'Item removed.');

        $referer = $_SERVER['HTTP_REFERER'] ?? '/U-Order/cart/cart.php';
        header("Location: " . $referer);
        exit;
    }
}
?>
