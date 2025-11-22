<?php
include '../configs/db.php';
include '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    
    // Check if user exists
    $sql = "SELECT UserId, Name FROM users WHERE Email = :email";
    $stmt = $db->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Generate Token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Insert into passwordresets table
        $sql = "INSERT INTO passwordresets (UserId, Token, ExpiresAt) VALUES (:uid, :token, :expiry)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':uid' => $user['UserId'], 
            ':token' => $token, 
            ':expiry' => $expiry
        ]);
        
        // I will do the mail sending part later. For now, just flash the link for testing.
        $link = "reset_password.php?token=" . $token;
        flash('notice', "Simulation: Reset link sent. <a href='$link'>[Click Here]</a>");
    } else {
        // Security: Don't reveal if email exists or not, but for this UI we'll show generic success
        flash('notice', 'If that email exists, we have sent a reset link.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php flash(); ?>
        <p>Enter your email address to receive a reset link.</p>
        <form method="POST" action="">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-primary">Send Link</button>
        </form>
        <div class="links">
            <a href="../pages/login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>