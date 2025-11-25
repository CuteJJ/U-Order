<?php
include '../configs/db.php';
include '../includes/functions.php';

header("Content-Type: application/json");

if (!isLoggedIn()) {
    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$productId   = $data["product_id"] ?? null;
$qty         = $data["qty"] ?? 1;
$note        = $data["remarks"] ?? null;
$pickupTime  = $data["pickup_time"] ?? null;
$cartItemId  = $data["cart_item_id"] ?? null;  // ★★ 关键点

if (!$pickupTime) {
    $pickupTime = date("Y-m-d H:i:s");
}

if (!$productId || $qty < 1) {
    echo json_encode(["success" => false, "message" => "Missing product or invalid quantity"]);
    exit;
}

try {
    // ------ 1. Get/Create Cart ------
    $sql = "SELECT CartId FROM carts WHERE UserId = :uid";
    $stmt = $db->prepare($sql);
    $stmt->execute([':uid' => $userId]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart) {
        $cartId = $cart['CartId'];
    } else {
        $sql = "INSERT INTO carts (UserId) VALUES (:uid)";
        $stmt = $db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $cartId = $db->lastInsertId();
    }


    // =====================================================================
    //  ⭐⭐⭐ CASE A：Edit Mode（cart_item_id 存在）→ 覆盖数量（不要合并）
    // =====================================================================
    if ($cartItemId) {
        $sql = "UPDATE cartitems 
                SET Quantity = :qty, Note = :note, PickupTime = :ptime
                WHERE CartItemId = :ciid";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':qty'  => $qty,
            ':note' => $note,
            ':ptime'=> $pickupTime,
            ':ciid' => $cartItemId
        ]);

        echo json_encode(["success" => true, "mode" => "edit"]);
        exit;
    }


    // =====================================================================
    //  ⭐⭐⭐ CASE B：Add Mode（没有 cart_item_id）→ 合并数量（正常 add to cart）
    // =====================================================================

    // 先查是否已经加入过同一个 product
    $sql = "SELECT CartItemId, Quantity 
            FROM cartitems 
            WHERE CartId = :cid AND ProductId = :pid";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':cid' => $cartId,
        ':pid' => $productId
    ]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $newQty = $existing['Quantity'] + $qty;

        $sql = "UPDATE cartitems 
                SET Quantity = :qty, Note = :note, PickupTime = :ptime
                WHERE CartItemId = :ciid";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':qty'  => $newQty,
            ':note' => $note,
            ':ptime'=> $pickupTime,
            ':ciid' => $existing['CartItemId']
        ]);

    } else {
        $sql = "INSERT INTO cartitems (CartId, ProductId, Quantity, Note, PickupTime)
                VALUES (:cid, :pid, :qty, :note, :ptime)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':cid'  => $cartId,
            ':pid'  => $productId,
            ':qty'  => $qty,
            ':note' => $note,
            ':ptime'=> $pickupTime
        ]);
    }

    echo json_encode(["success" => true, "mode" => "add"]);

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
