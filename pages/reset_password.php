<?php
include '../configs/db.php';
include '../includes/functions.php';

$token = $_GET['token'] ?? '';
$validToken = false;

if ($token) {
    // Validate Token
    $sql = "SELECT * FROM passwordresets WHERE Token = :token AND ExpiresAt > NOW()";
    $stmt = $db->prepare($sql);
    $stmt->execute([':token' => $token]);
    $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resetRequest) {
        $validToken = true;
    } else {
        flash('error', 'Invalid or expired reset token.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPass = $_POST['password'];
    $confirmPass = $_POST['confirm_password'];
    
    if ($newPass === $confirmPass) {
        // Update User Password
        $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET HashedPassword = :pass WHERE UserId = :uid";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':pass' => $hashedPassword,
            ':uid' => $resetRequest['UserId']
        ]);
        
        // Delete the used token
        $sql = "DELETE FROM passwordresets WHERE ResetId = :rid";
        $stmt = $db->prepare($sql);
        $stmt->execute([':rid' => $resetRequest['ResetId']]);
        
        flash('success', 'Password has been reset. Please login.');
        header("Location: login.php");
        exit;
    } else {
        flash('error', 'Passwords do not match.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password</title>
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
    <div class="container">
        <h2>New Password</h2>
        <?php flash(); ?>
        
        <?php if ($validToken): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
        <?php else: ?>
            <div class="links"><a href="../pages/forgot_password.php">Request new link</a></div>
        <?php endif; ?>
    </div>
</body>
</html>