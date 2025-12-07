<?php
include '../configs/db.php';
include '../includes/functions.php';

$hideNav = true;
$token = $_GET['token'] ?? '';
$validToken = false;

// 1. Validate Token
if ($token) {
    $sql = "SELECT * FROM passwordresets WHERE Token = :token AND ExpiresAt > NOW()";
    $stmt = $db->prepare($sql);
    $stmt->execute([':token' => $token]);
    $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($resetRequest) {
        $validToken = true;
    } else {
        flash('error', 'This reset link is invalid or has expired.');
    }
}

// 2. Handle Password Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPass = $_POST['password'];
    $confirmPass = $_POST['confirm_password'];
    
    // Basic Validation
    if (strlen($newPass) < 8 || strlen($newPass) > 16) {
        flash('error', 'Password must be between 8 and 16 characters.');
    } elseif ($newPass !== $confirmPass) {
        flash('error', 'Passwords do not match.');
    } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        
        // Update User Password
        $sql = "UPDATE users SET HashedPassword = :pass WHERE UserId = :uid";
        $stmt = $db->prepare($sql);
        $stmt->execute([':pass' => $hashed, ':uid' => $resetRequest['UserId']]);
        
        // Delete Used Token
        $sql = "DELETE FROM passwordresets WHERE ResetId = :rid";
        $stmt = $db->prepare($sql);
        $stmt->execute([':rid' => $resetRequest['ResetId']]);
        
        flash('success', 'Password updated successfully. Please login.');
        header("Location: login.php");
        exit;
    }
}
include '../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/auth.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-image-side" style="background-image: url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1000&q=80');">
            <div class="auth-image-text">
                <h1>Secure<br>Account.</h1>
                <p>Create a strong new password to protect your profile.</p>
            </div>
        </div>

        <div class="auth-form-side">
            <h2>Set New Password</h2>
            
            <?php if ($validToken): ?>
                <p class="sub-text">Please choose a unique password.</p>
                <?php flash(); ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" required placeholder="New Password (8-16 chars)">
                            <i class="fas fa-eye password-toggle"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="confirm_password" required placeholder="Confirm New Password">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        Update Password <i class="fas fa-check-circle"></i>
                    </button>
                </form>

            <?php else: ?>
                <div style="text-align: center; margin-top: 20px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--nord11); margin-bottom: 20px;"></i>
                    <p style="color: var(--text-muted); margin-bottom: 30px;">
                        The link you clicked is invalid or has expired.
                    </p>
                    <a href="forgot_password.php" class="btn btn-primary">Request New Link</a>
                </div>
            <?php endif; ?>
            
            <?php if ($validToken): ?>
                <div class="links">
                    <a href="login.php" style="color: var(--text-muted); font-weight: 500;">Cancel</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="/U-Order/assets/js/auth.js"></script>

<?php include '../includes/footer.php'; ?>