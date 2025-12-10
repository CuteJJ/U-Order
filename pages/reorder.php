<?php
// ================================================================
// REORDER - Add previous order items to cart
// ================================================================
session_start();
require __DIR__ . '/../configs/db.php';

// Get order ID
$orderId = isset($_GET['orderid']) ? (int)$_GET['orderid'] : null;

if (!$orderId) {
    $_SESSION['error'] = 'Invalid order ID';
    header('Location: /U-Order/cart/cart.php');
    exit;
}

// Get order items with all details
function getOrderItemsForReorder(PDO $db, int $orderId): array
{
    $sql = "
        SELECT 
            oi.ProductId,
            oi.Quantity,
            oi.Note,
            p.ProductName,
            p.UnitPrice,
            p.StallId
        FROM orderitems oi
        JOIN products p ON oi.ProductId = p.ProductId
        WHERE oi.OrderId = :orderId
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([':orderId' => $orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

try {
    // Get all items from the previous order
    $orderItems = getOrderItemsForReorder($db, $orderId);
    
    if (empty($orderItems)) {
        $_SESSION['error'] = 'No items found in this order';
        header('Location: /U-Order/cart/cart.php');
        exit;
    }

    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Add each item to the cart
    $addedCount = 0;
    foreach ($orderItems as $item) {
        $productId = $item['ProductId'];
        
        // Create cart key (using product ID)
        $cartKey = $productId;
        
        // Check if item already exists in cart
        if (isset($_SESSION['cart'][$cartKey])) {
            // Add to existing quantity
            $_SESSION['cart'][$cartKey]['quantity'] += $item['Quantity'];
            
            // Append note if there's a new one
            if (!empty($item['Note'])) {
                $existingNote = $_SESSION['cart'][$cartKey]['note'] ?? '';
                if (!empty($existingNote)) {
                    $_SESSION['cart'][$cartKey]['note'] = $existingNote . ' | ' . $item['Note'];
                } else {
                    $_SESSION['cart'][$cartKey]['note'] = $item['Note'];
                }
            }
        } else {
            // Add new item to cart
            $_SESSION['cart'][$cartKey] = [
                'product_id' => $productId,
                'product_name' => $item['ProductName'],
                'unit_price' => $item['UnitPrice'],
                'quantity' => $item['Quantity'],
                'note' => $item['Note'] ?? '',
                'stall_id' => $item['StallId']
            ];
        }
        
        $addedCount++;
    }

    // Set success message
    $_SESSION['success'] = "Successfully added {$addedCount} item(s) from your previous order to cart!";
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to reorder: ' . $e->getMessage();
}

// Redirect to cart page
header('Location: /U-Order/cart/cart.php');
exit;
?>