<?php
require '../configs/db.php';

$itemId = $_POST['orderitem_id'];

// 1. 更新 item status = complete
$db->prepare("UPDATE orderitems SET Status='complete' WHERE OrderListId=:id")
   ->execute(['id' => $itemId]);

// 2. 找该 item 属于哪个 order
$oid = $db->query("SELECT OrderId FROM orderitems WHERE OrderListId=$itemId")->fetchColumn();

// 3. 检查订单的所有 item 是否都 complete
$check = $db->prepare("
    SELECT COUNT(*) 
    FROM orderitems 
    WHERE OrderId=? 
      AND Status!='complete'
");
$check->execute([$oid]);
$left = $check->fetchColumn();

// 4. 如果全部完成 → orders.Status = complete
if ($left == 0) {
    $db->prepare("UPDATE orders SET Status='complete' WHERE OrderId=?")->execute([$oid]);
}

header("Location: order_details.php?orderid=" . $oid);
exit;
