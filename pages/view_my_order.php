<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch Orders with Stall Info and Payment Status
$sql = "SELECT o.OrderId, o.Status as OrderStatus, o.CreatedAt, o.Notes,
               s.StallName, 
               p.Status as PaymentStatus, p.TotalAmount as PaymentTotal
        FROM orders o
        JOIN stalls s ON o.StallId = s.StallId
        JOIN payments p ON o.PaymentId = p.PaymentId
        WHERE o.UserId = :uid
        ORDER BY o.CreatedAt DESC";

$stmt = $db->prepare($sql);
$stmt->execute([':uid' => $userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <style>
        .order-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-preparing { background: #cce5ff; color: #004085; }
        .status-ready { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .item-list {
            margin-top: 10px;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2>My Order History</h2>
            <a href="menu.php" class="btn btn-primary">Back to Menu</a>
        </div>

        <?php if (empty($orders)): ?>
            <p>You haven't ordered anything yet.</p>
        <?php else: ?>
            <?php foreach ($orders as $order): 
                // Fetch Items for this specific order
                $sqlItems = "SELECT ol.Quantity, ol.Subtotal, pr.ProductName 
                             FROM orderlists ol
                             JOIN products pr ON ol.ProductId = pr.ProductId
                             WHERE ol.OrderId = :oid";
                $stmtItem = $db->prepare($sqlItems);
                $stmtItem->execute([':oid' => $order['OrderId']]);
                $items = $stmtItem->fetchAll(PDO::FETCH_ASSOC);
                
                // Calculate total for this specific stall order (since PaymentTotal is the combined total)
                $orderTotal = 0;
                foreach($items as $i) $orderTotal += $i['Subtotal'];
            ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <strong>Order #<?php echo $order['OrderId']; ?></strong>
                            <span style="color:#666; margin-left:10px;">
                                <?php echo date('d M Y, h:i A', strtotime($order['CreatedAt'])); ?>
                            </span>
                            <br>
                            <small>Stall: <strong><?php echo htmlspecialchars($order['StallName']); ?></strong></small>
                        </div>
                        <div style="text-align:right;">
                            <span class="status-badge status-<?php echo strtolower($order['OrderStatus']); ?>">
                                <?php echo $order['OrderStatus']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="item-list">
                        <?php foreach ($items as $item): ?>
                            <div class="item-row">
                                <span><?php echo $item['Quantity']; ?>x <?php echo htmlspecialchars($item['ProductName']); ?></span>
                                <span>RM <?php echo number_format($item['Subtotal'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="item-row" style="border-top:1px solid #ddd; margin-top:5px; padding-top:5px; font-weight:bold;">
                            <span>Total</span>
                            <span>RM <?php echo number_format($orderTotal, 2); ?></span>
                        </div>
                    </div>
                    
                    <?php if(!empty($order['Notes'])): ?>
                        <div style="margin-top:10px; font-size:0.9em; color:#555;">
                            <strong>Notes:</strong> <?php echo htmlspecialchars($order['Notes']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>