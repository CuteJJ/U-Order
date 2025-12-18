<?php
include '../configs/db.php';
include '../includes/functions.php';

$hideNav = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $sql = "SELECT UserId, Name FROM users WHERE Email = :email";
    $stmt = $db->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+8 minutes')); // 8 minutes till link expire

        // Clean old tokens
        $delSql = "DELETE FROM passwordresets WHERE UserId = :uid";
        $delStmt = $db->prepare($delSql);
        $delStmt->execute([':uid' => $user['UserId']]);
        
        // Insert new token
        $sql = "INSERT INTO passwordresets (UserId, Token, ExpiresAt) VALUES (:uid, :token, :expiry)";
        $stmt = $db->prepare($sql);
        $stmt->execute([':uid' => $user['UserId'], ':token' => $token, ':expiry' => $expiry]);
        
        // Send Email
        $resetLink = "http://localhost/U-Order/pages/reset_password.php?token=" . $token;
        
        try {
            $m = get_mail();
            $m->addAddress($email, $user['Name']);
            $m->isHTML(true);
            $m->Subject = "Reset Password Request";
            $m->Body = "<h3>Password Reset</h3><p>Click the link below to reset your password:</p><p><a href='$resetLink'>$resetLink</a></p><p>This link expires in 8 minutes.</p>";
            $m->send();
            flash('success', 'Reset link sent to your email.');
        } catch (Exception $e) {
            flash('error', 'Could not send email. Please try again later.');
        }

    } else {
        // generic message sent to prevent email enumeration
        flash('notice', 'If an account exists with that email, a reset link has been sent.');
    }
}
include '../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/auth.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-image-side" style="background-image: url('https://images.unsplash.com/photo-1512486130939-2c4f79935e4f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1000&q=80');">
            <div class="auth-image-text">
                <h1>Recovery<br>Mode.</h1>
                <p>Don't worry, it happens to the best of us.</p>
            </div>
        </div>

        <div class="auth-form-side">
            <h2>Reset Password</h2>
            <p class="sub-text">Enter your email and we'll send you a link to get back into your account.</p>
            
            <?php flash(); ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" required placeholder="Enter your email address">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    Send Reset Link <i class="fas fa-paper-plane"></i>
                </button>
            </form>
            
            <div class="links">
                <a href="login.php" style="color: var(--text-muted); font-weight: 500;">&larr; Back to Login</a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="/U-Order/assets/js/auth.js"></script>

<?php include '../includes/footer.php'; ?>