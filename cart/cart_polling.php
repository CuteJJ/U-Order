<?php
session_start();
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'items' => []]);
    exit;
}

$userId = $_SESSION['user_id'];

$payload = json_decode(file_get_contents('php://input'), true);
$idList = isset($payload['items']) ? $payload['items'] : [];

$idList = array_filter(array_map('intval', $idList));
if (empty($idList)) {
    echo json_encode(['success' => true, 'items' => []]);
    exit;
}

$ph = implode(',', array_fill(0, count($idList), '?'));

$sql = "
SELECT 
    ci.CartItemId,
    ci.Quantity,

    p.UnitPrice,
    p.IsUnlimitedStock,
    p.Stock,
    p.IsAvailable AS ProductAvailable,

    s.IsAvailable AS StallAvailable
FROM cartitems ci
JOIN carts c ON ci.CartId = c.CartId
JOIN products p ON ci.ProductId = p.ProductId
JOIN stalls s ON p.StallId = s.StallId
WHERE ci.CartItemId IN ($ph)
  AND c.UserId = ?
";

$stmt = $db->prepare($sql);
$stmt->execute([...$idList, $userId]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = [];

foreach ($rows as $r) {

    $isUnavailable = false;
    $statusLabel = '';

    if ((int)$r['StallAvailable'] !== 1) {
        $isUnavailable = true;
        $statusLabel = 'Stall closed';

    } elseif ((int)$r['ProductAvailable'] !== 1) {
        $isUnavailable = true;
        $statusLabel = 'Unavailable';

    } elseif (!(int)$r['IsUnlimitedStock'] && (int)$r['Stock'] <= 0) {
        $isUnavailable = true;
        $statusLabel = 'Out of stock';
    }

    $result[] = [
        'cartItemId'    => (int)$r['CartItemId'],
        'unitPrice'     => (float)$r['UnitPrice'],
        'stock'         => (int)$r['Stock'],
        'isUnlimited'   => (int)$r['IsUnlimitedStock'],
        'isUnavailable' => $isUnavailable,
        'statusLabel'   => $statusLabel,
    ];
}

echo json_encode([
    'success' => true,
    'items'   => $result
]);
