<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$sql = "SELECT ci.CartItemId, ci.Quantity, p.ProductName, p.UnitPrice, s.StallName 
        FROM carts c
        JOIN cartitems ci ON c.CartId = ci.CartId
        JOIN products p ON ci.ProductId = p.ProductId
        JOIN stalls s ON p.StallId = s.StallId
        WHERE c.UserId = :uid";
$stmt = $db->prepare($sql);
$stmt->execute([':uid' => $userId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPrice = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <style>
        .cart-container { max-width: 800px; margin: 30px auto; padding: 20px; }
        .cart-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .cart-table th, .cart-table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        .cart-total { text-align: right; font-size: 1.2em; margin-top: 20px; font-weight: bold; }
        .btn-checkout { background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; display: inline-block; border-radius: 5px; }
        .stall-badge { background: #e2e8f0; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; color: #4a5568; }
        .empty-cart { text-align: center; padding: 50px; color: #777; }
        
        /* Quantity Control Styles */
        .qty-control { display: flex; align-items: center; gap: 5px; }
        .btn-qty { 
            background: #eee; border: none; width: 25px; height: 25px; 
            cursor: pointer; border-radius: 4px; font-weight: bold; 
        }
        .btn-qty:hover { background: #ddd; }
    </style>
</head>
<body>
    
    <div class="cart-container">
        <h2>Your Shopping Cart</h2>
        <?php flash(); ?>

        <?php if (empty($items)): ?>
            <div class="empty-cart">
                <p>Your cart is empty.</p>
                <!-- Updated to menu.php -->
                <a href="menu.php" class="btn btn-primary">Go to Menu</a>
            </div>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): 
                        $subtotal = $item['UnitPrice'] * $item['Quantity'];
                        $totalPrice += $subtotal;
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($item['ProductName']); ?></strong><br>
                            <span class="stall-badge">Stall: <?php echo htmlspecialchars($item['StallName']); ?></span>
                        </td>
                        <td>RM <?php echo number_format($item['UnitPrice'], 2); ?></td>
                        <td>
                            <!-- Quantity Edit Form -->
                            <div class="qty-control">
                                <form action="update_cart.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['CartItemId']; ?>">
                                    <input type="hidden" name="action" value="decrease">
                                    <button type="submit" class="btn-qty">-</button>
                                </form>
                                
                                <span><?php echo $item['Quantity']; ?></span>
                                
                                <form action="update_cart.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['CartItemId']; ?>">
                                    <input type="hidden" name="action" value="increase">
                                    <button type="submit" class="btn-qty">+</button>
                                </form>
                            </div>
                        </td>
                        <td>RM <?php echo number_format($subtotal, 2); ?></td>
                        <td>
                            <a href="remove_from_cart.php?id=<?php echo $item['CartItemId']; ?>" style="color: red; text-decoration:none;">&times; Remove</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-total">
                Total: RM <?php echo number_format($totalPrice, 2); ?>
            </div>

            <div style="text-align: right; margin-top: 20px;">
                <!-- Updated to menu.php -->
                <a href="menu.php" class="btn" style="margin-right: 10px;">Continue Shopping</a>
                
                <!-- Updated to Checkout -->
                <a href="checkout.php" class="btn-checkout">Check Out</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>