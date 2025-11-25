<?php
include '../configs/db.php';
include '../includes/functions.php';

$hideNav = true;
$token = $_GET['token'] ?? '';
$validToken = false;

if ($token) {
    $sql = "SELECT * FROM passwordresets WHERE Token = :token AND ExpiresAt > NOW()";
    $stmt = $db->prepare($sql);
    $stmt->execute([':token' => $token]);
    $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($resetRequest) $validToken = true;
    else flash('error', 'Invalid token.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPass = $_POST['password'];
    $confirmPass = $_POST['confirm_password'];
    
    if ($newPass === $confirmPass) {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET HashedPassword = :pass WHERE UserId = :uid";
        $stmt = $db->prepare($sql);
        $stmt->execute([':pass' => $hashed, ':uid' => $resetRequest['UserId']]);
        
        $sql = "DELETE FROM passwordresets WHERE ResetId = :rid";
        $stmt = $db->prepare($sql);
        $stmt->execute([':rid' => $resetRequest['ResetId']]);
        
        flash('success', 'Password reset. Please login.');
        header("Location: login.php");
        exit;
    } else {
        flash('error', 'Passwords do not match.');
    }
}
include '../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/auth.css">

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-image-side" style="background-image: url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1000&q=80');">
            <div class="auth-image-text">
                <h1>Secure Account</h1>
                <p>Create a strong new password.</p>
            </div>
        </div>
        <div class="auth-form-side">
            <h2>Set New Password</h2>
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
                <div class="links"><a href="forgot_password.php">Request new link</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>