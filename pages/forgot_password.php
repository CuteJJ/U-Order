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
        
        // Delete any previous reset requests for this user
        $delSql = "DELETE FROM passwordresets WHERE UserId = :uid";
        $delStmt = $db->prepare($delSql);
        $delStmt->execute([':uid' => $user['UserId']]);

        // Insert into passwordresets table
        $sql = "INSERT INTO passwordresets (UserId, Token, ExpiresAt) VALUES (:uid, :token, :expiry)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':uid' => $user['UserId'], 
            ':token' => $token, 
            ':expiry' => $expiry
        ]);
        
        $resetLink = "http://localhost/U-Order/pages/reset_password.php?token=" . $token;
        $m = get_mail();
        $m->addAddress($email, $user['Name']);
        $m->isHTML(true);
        $m->Subject = "Reset Your Password - Canteen App";
        $m->Body = "
        <h3>Password Reset Request</h3>
        <p>Hi " . htmlspecialchars($user['Name']) . ",</p>
        <p>We received a request to reset your password. Click the link below to proceed:</p>
        <p><a href='$resetLink' style='background: #5E81AC; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
        <p>If you did not request this, please ignore this email.</p>
        <p>Link expires in 1 hour.</p>
        ";
        $sent = $m->send();
        if ($sent === true) {
            flash('success', 'Reset link sent to your email.');
        } else {
            flash('error', 'Failed to send email. Server said: ' . $sent);
        }
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