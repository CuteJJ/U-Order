<?php
/**
 * REORDER
 * 将已完成订单的 items 加回当前用户的 cart（数据库版）
 * Updated: Now sets PickupTime to ASAP by default
 */

session_start();
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

/* -------------------------------------------------
   1. Auth & basic validation
-------------------------------------------------- */
if (!isLoggedIn()) {
    header("Location: /U-Order/pages/login.php");
    exit;
}

$userId  = (int)($_SESSION['user_id'] ?? 0);
$orderId = isset($_GET['orderid']) ? (int)$_GET['orderid'] : 0;

if ($userId <= 0 || $orderId <= 0) {
    $_SESSION['error'] = 'Invalid reorder request.';
    header('Location: /U-Order/cart/cart.php');
    exit;
}

/* -------------------------------------------------
   2. Calculate ASAP Pickup Time (matching cart_action.php logic)
-------------------------------------------------- */
date_default_timezone_set('Asia/Kuala_Lumpur');

// ASAP = current time + 15 min buffer, rounded up to nearest 5 min
$bufferTime = time() + (15 * 60);
$roundedTime = ceil($bufferTime / 300) * 300;
$pickupDateTime = date('Y-m-d H:i:00', $roundedTime);

/* -------------------------------------------------
   3. Fetch order items (must exist)
-------------------------------------------------- */
$sqlOrderItems = "
    SELECT 
        oi.ProductId,
        oi.Quantity,
        oi.Note
    FROM orderitems oi
    JOIN orders o ON oi.OrderId = o.OrderId
    WHERE oi.OrderId = :oid
      AND o.UserId  = :uid
      AND o.Status  = 'complete'
";

$stmt = $db->prepare($sqlOrderItems);
$stmt->execute([
    ':oid' => $orderId,
    ':uid' => $userId
]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($orderItems)) {
    $_SESSION['error'] = 'No items found in this order.';
    header('Location: /U-Order/cart/cart.php');
    exit;
}

try {
    /* -------------------------------------------------
       4. Find or create cart for user
    -------------------------------------------------- */
    $stmt = $db->prepare("SELECT CartId FROM carts WHERE UserId = :uid LIMIT 1");
    $stmt->execute([':uid' => $userId]);
    $cartId = $stmt->fetchColumn();

    if (!$cartId) {
        $stmt = $db->prepare("INSERT INTO carts (UserId) VALUES (:uid)");
        $stmt->execute([':uid' => $userId]);
        $cartId = (int)$db->lastInsertId();
    }

    /* -------------------------------------------------
       5. Insert / update cartitems with ASAP pickup time
    -------------------------------------------------- */
    $db->beginTransaction();

    $addedCount = 0;

    foreach ($orderItems as $item) {
        $productId = (int)$item['ProductId'];
        $qty       = (int)$item['Quantity'];
        $note      = $item['Note'] ?? '';

        // Check if product already exists in cart with same pickup time and note
        $check = $db->prepare("
            SELECT CartItemId, Quantity
            FROM cartitems
            WHERE CartId = :cid 
              AND ProductId = :pid
              AND PickupTime = :ptime
              AND Note = :note
            LIMIT 1
        ");
        $check->execute([
            ':cid' => $cartId,
            ':pid' => $productId,
            ':ptime' => $pickupDateTime,
            ':note' => $note
        ]);

        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Merge quantity (same product, same pickup time, same note)
            $newQty = (int)$existing['Quantity'] + $qty;

            $upd = $db->prepare("
                UPDATE cartitems
                SET Quantity = :q
                WHERE CartItemId = :id
            ");
            $upd->execute([
                ':q'  => $newQty,
                ':id' => $existing['CartItemId']
            ]);
        } else {
            // Insert new cart item with ASAP pickup time
            $ins = $db->prepare("
                INSERT INTO cartitems (CartId, ProductId, Quantity, PickupTime, Note)
                VALUES (:cid, :pid, :q, :ptime, :n)
            ");
            $ins->execute([
                ':cid' => $cartId,
                ':pid' => $productId,
                ':q'   => $qty,
                ':ptime' => $pickupDateTime,
                ':n'   => $note
            ]);
        }

        $addedCount++;
    }

    $db->commit();

    $_SESSION['success'] = "Successfully added {$addedCount} item(s) back to your cart.";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['error'] = 'Reorder failed: ' . $e->getMessage();
}

/* -------------------------------------------------
   6. Redirect back to cart
-------------------------------------------------- */
header('Location: /U-Order/cart/cart.php');
exit;