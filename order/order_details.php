<?php
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

session_start();

$orderId = $_GET['orderid'] ?? null;
if (!$orderId) {
    echo "Invalid order.";
    exit;
}

// 取订单
$sql = "
    SELECT o.OrderId, o.Status, o.CreatedAt, s.StallName
    FROM orders o
    JOIN stalls s ON o.StallId = s.StallId
    WHERE o.OrderId = :oid
";
$stmt = $db->prepare($sql);
$stmt->execute(['oid' => $orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Order not found.";
    exit;
}

// 取 order items
$itemSql = "
    SELECT ProductName, Quantity, UnitPrice, PickupTime
    FROM orderitems
    WHERE OrderId = :oid
";
$i = $db->prepare($itemSql);
$i->execute(['oid' => $orderId]);
$items = $i->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order #<?= $order['OrderId'] ?></title>
    <link rel="stylesheet" href="/U-Order/assets/css/app.css">
</head>
<body>

<h2>Order #<?= $order['OrderId'] ?></h2>
<p><strong>Stall:</strong> <?= htmlspecialchars($order['StallName']) ?></p>
<p><strong>Status:</strong> <?= ucfirst($order['Status']) ?></p>
<p><strong>Created:</strong> <?= $order['CreatedAt'] ?></p>

<hr>

<h3>Items</h3>
<?php foreach ($items as $it): ?>
    <div style="margin-bottom: 10px;">
        <strong><?= htmlspecialchars($it['ProductName']) ?></strong><br>
        Qty: <?= $it['Quantity'] ?> x RM <?= number_format($it['UnitPrice'], 2) ?><br>
        Pickup: <?= $it['PickupTime'] ?? "ASAP" ?>
    </div>
<?php endforeach; ?>

</body>
</html>
