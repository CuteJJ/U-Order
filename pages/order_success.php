<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) { header("Location: login.php"); exit; }

$paymentId = $_GET['payment_id'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Successful</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <style>
        .success-container { text-align: center; padding: 50px; max-width: 600px; margin: 0 auto; }
        .check-icon { color: #28a745; font-size: 80px; }
        .btn-home { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #4a90e2; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="check-icon">✓</div>
        <h1>Payment Successful!</h1>
        <p>Your payment (ID: #<?php echo htmlspecialchars($paymentId); ?>) has been processed.</p>
        <p>Your orders have been sent to the respective stalls.</p>
        
        <a href="view_my_order.php" class="btn-home">View My Orders</a>
    </div>
</body>
</html>