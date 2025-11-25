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
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $delSql = "DELETE FROM passwordresets WHERE UserId = :uid";
        $delStmt = $db->prepare($delSql);
        $delStmt->execute([':uid' => $user['UserId']]);
        
        $sql = "INSERT INTO passwordresets (UserId, Token, ExpiresAt) VALUES (:uid, :token, :expiry)";
        $stmt = $db->prepare($sql);
        $stmt->execute([':uid' => $user['UserId'], ':token' => $token, ':expiry' => $expiry]);
        
        $resetLink = "http://localhost/U-Order/pages/reset_password.php?token=" . $token;
        $m = get_mail();
        $m->addAddress($email, $user['Name']);
        $m->isHTML(true);
        $m->Subject = "Reset Password";
        $m->Body = "<h3>Reset Password</h3><p>Click here: <a href='$resetLink'>Reset</a></p>";
        $m->send();

        flash('success', 'Reset link sent.');
    } else {
        flash('notice', 'If email exists, link sent.');
    }
}
include '../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/auth.css">

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-image-side" style="background-image: url('https://images.unsplash.com/photo-1512486130939-2c4f79935e4f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1000&q=80');">
            <div class="auth-image-text">
                <h1>Recovery</h1>
                <p>We'll help you get back in.</p>
            </div>
        </div>
        <div class="auth-form-side">
            <h2>Reset Password</h2>
            <?php flash(); ?>
            <p style="text-align:center; color:var(--nord3); margin-bottom:20px;">Enter your email to receive a reset link.</p>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="Enter your email">
                </div>
                <button type="submit" class="btn btn-primary">Send Link</button>
            </form>
            <div class="links">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>