<?php
include '../configs/db.php';
include '../includes/functions.php';

// Disable Nav for Auth
$hideNav = true;

checkRememberMe($db);
if (isLoggedIn()) { header("Location: profile.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginId = $_POST['login_id']; 
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $sql = "SELECT * FROM users WHERE Email = :login OR UserId = :login";
    $stmt = $db->prepare($sql);
    $stmt->execute([':login' => $loginId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['HashedPassword'])) {
        $_SESSION['user_id'] = $user['UserId'];
        $_SESSION['role'] = $user['Role'];
        $_SESSION['name'] = $user['Name'];

        if ($remember) {
            $secret = "YOUR_SECRET_KEY";
            $token = $user['UserId'] . ':' . hash_hmac('sha256', $user['UserId'], $secret);
            setcookie('remember_token', $token, time() + (86400 * 30), "/");
        }
        flash('success', 'Welcome back, ' . $user['Name']);
        header("Location: profile.php");
        exit;
    } else {
        flash('error', 'Invalid ID/Email or Password.');
    }
}
include '../includes/header.php'; 
?>

<link rel="stylesheet" href="/U-Order/assets/css/auth.css">

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-image-side" style="background-image: url('https://images.unsplash.com/photo-1543353071-87d3e7c91d81?ixlib=rb-1.2.1&auto=format&fit=crop&w=1000&q=80');">
            <div class="auth-image-text">
                <h1>Welcome Back</h1>
                <p>Login to continue ordering.</p>
            </div>
        </div>

        <div class="auth-form-side">
            <h2>Login</h2>
            <?php flash(); ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>ID or Email</label>
                    <input type="text" name="login_id" required placeholder="Enter ID or Email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter Password">
                </div>
                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="remember" style="width:auto; margin:0;"> Remember Me
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            <div class="links">
                <a href="register.php">Register</a> | 
                <a href="forgot_password.php">Forgot Password?</a>
                <br><br>
                <a href="../index.php">&larr; Home</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>