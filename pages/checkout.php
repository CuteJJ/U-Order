<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) { header("Location: login.php"); exit; }

$userId = $_SESSION['user_id'];

// 1. Check if cart is empty before letting them here
$sql = "SELECT COUNT(*) FROM carts c JOIN cartitems ci ON c.CartId = ci.CartId WHERE c.UserId = :uid";
$stmt = $db->prepare($sql);
$stmt->execute([':uid' => $userId]);
if ($stmt->fetchColumn() == 0) {
    flash('error', 'Your cart is empty.');
    header("Location: menu.php");
    exit;
}

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save details to session to be used in process_payment.php
    $_SESSION['checkout_notes'] = $_POST['notes'];
    $_SESSION['checkout_time'] = $_POST['pickup_time'];
    
    // Go to payment
    header("Location: payment.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout Details</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <style>
        .checkout-container { max-width: 600px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-proceed { background: #4a90e2; color: white; padding: 12px 20px; border: none; border-radius: 5px; font-size: 1.1em; cursor: pointer; width: 100%; }
        .btn-proceed:hover { background: #357abd; }
    </style>
</head>
<body>
    <div class="checkout-container">
        <h2>Checkout Details</h2>
        <p>Please provide pickup details for your order.</p>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Pickup Time</label>
                <!-- Set min time to current time approximately -->
                <input type="time" name="pickup_time" class="form-control" required value="<?php echo date('H:i', strtotime('+15 minutes')); ?>">
                <small style="color: #666;">Please allow at least 15 minutes for preparation.</small>
            </div>
            
            <div class="form-group">
                <label>Remarks / Special Notes</label>
                <textarea name="notes" class="form-control" rows="4" placeholder="E.g. No spicy, extra tissue..."></textarea>
            </div>
            
            <button type="submit" class="btn-proceed">Proceed to Payment</button>
            <div style="text-align: center; margin-top: 15px;">
                <a href="cart.php">Back to Cart</a>
            </div>
        </form>
    </div>
</body>
</html>