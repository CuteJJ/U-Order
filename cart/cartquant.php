<?php
session_start();
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$userId     = $_SESSION['user_id'] ?? 0;
$cartItemId = isset($_POST['cartItemId']) ? (int)$_POST['cartItemId'] : 0;
$newQty     = isset($_POST['newQty'])     ? (int)$_POST['newQty']     : 0;

if ($cartItemId <= 0 || $newQty <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Get cart item with product and stall info
$sql = "
    SELECT 
        ci.CartItemId, 
        c.UserId, 
        p.IsUnlimitedStock, 
        p.Stock,
        p.IsAvailable AS ProductIsAvailable,
        p.ProductName,
        s.IsAvailable AS StallIsAvailable,
        s.StallName
    FROM cartitems ci
    JOIN carts c  ON ci.CartId = c.CartId
    JOIN products p ON ci.ProductId = p.ProductId
    JOIN stalls s ON p.StallId = s.StallId
    WHERE ci.CartItemId = :id
";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $cartItemId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || (int)$row['UserId'] !== (int)$userId) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit;
}

// Check stall availability
if ((int)$row['StallIsAvailable'] !== 1) {
    echo json_encode([
        'success' => false,
        'message' => $row['StallName'] . ' is currently closed'
    ]);
    exit;
}

// Check product availability
if ((int)$row['ProductIsAvailable'] !== 1) {
    echo json_encode([
        'success' => false,
        'message' => $row['ProductName'] . ' is currently unavailable'
    ]);
    exit;
}

$isUnlimited = (int)$row['IsUnlimitedStock'] === 1;
$stock       = (int)$row['Stock'];

// Validate stock
if (!$isUnlimited) {
    if ($stock <= 0) {
        echo json_encode([
            'success' => false,
            'message' => $row['ProductName'] . ' is out of stock'
        ]);
        exit;
    }
    
    if ($newQty > $stock) {
        echo json_encode([
            'success' => false,
            'message' => 'Only ' . $stock . ' available in stock'
        ]);
        exit;
    }
}

// Update quantity
$upd = $db->prepare("UPDATE cartitems SET Quantity = :qty WHERE CartItemId = :id");
$upd->execute([
    'qty' => $newQty,
    'id'  => $cartItemId
]);

echo json_encode(['success' => true]);      