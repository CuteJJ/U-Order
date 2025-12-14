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
        flash('success', 'Welcome back, ' . $user['Name']);
        if ($user['Role'] === 'admin') {
            header("Location: admin_dashboard.php");
            exit;
        }
        elseif ($user['Role'] === 'vendor') {
            header("Location: vendor_dashboard.php");
            exit;
        }
        else {
            header("Location: profile.php");
            exit;
        }
    } else {
        flash('error', 'Invalid ID/Email or Password.');
    }
}
include '../includes/header.php'; 
?>

<link rel="stylesheet" href="/U-Order/assets/css/auth.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-image-side" style="background-image: url('https://images.unsplash.com/photo-1543353071-87d3e7c91d81?ixlib=rb-1.2.1&auto=format&fit=crop&w=1000&q=80');">
            <div class="auth-image-text">
                <h1>Welcome<br>Back.</h1>
                <p>Hungry? You're in the right place.</p>
            </div>
        </div>

        <div class="auth-form-side">
            <h2>Login</h2>
            <p class="sub-text">Enter your details to continue.</p>
            
            <?php flash(); ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="login_id" required placeholder="Student ID or Email">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" style="font-family: Arial, Helvetica, sans-serif;" required placeholder="Password">
                        <i class="fas fa-eye password-toggle"></i>
                    </div>
                </div>
                
                <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                    <label class="checkbox-group">
                        <input type="checkbox" name="remember"> Remember Me
                    </label>
                    <a href="forgot_password.php" style="font-size: 0.9rem; color: var(--nord10); font-weight: 600;">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    Login <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="links">
                New here? <a href="register.php">Create Account</a>
                <br><br>
                <a href="../index.php" style="color: var(--text-muted); font-weight: 500;">&larr; Back to Home</a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="/U-Order/assets/js/auth.js"></script>

<?php include '../includes/footer.php'; ?>