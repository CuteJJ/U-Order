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
    <link rel="stylesheet" href="../assets/css/aurora_theme.css">
    <style>
        .success-container { text-align: center; padding: 60px 20px; max-width: 600px; margin: 40px auto; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .check-icon { 
            width: 80px; height: 80px; line-height: 80px; 
            background: #A3BE8C; color: white; 
            border-radius: 50%; font-size: 40px; 
            margin: 0 auto 20px; 
            box-shadow: 0 4px 15px rgba(163, 190, 140, 0.4);
        }
        h1 { color: #2E3440; margin-bottom: 10px; }
        p { color: #4C566A; margin-bottom: 30px; font-size: 1.1rem; }
        
        .btn-group { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn-home { padding: 12px 25px; background: #5E81AC; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: transform 0.2s; }
        .btn-home:hover {transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="check-icon">✓</div>
        <h1>Payment Successful!</h1>
        <p>Your payment (ID: #<?php echo htmlspecialchars($paymentId); ?>) has been processed successfully.</p>
        <p style="font-size: 0.95rem; color: #888;">Your orders have been sent to the respective stalls.</p>
        
        <div class="btn-group">      
            <a href="../order/order.php" class="btn-home">View My Orders</a>
        </div>
    </div>
</body>
</html>