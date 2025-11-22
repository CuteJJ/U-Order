<?php
include '../configs/db.php';
include '../includes/functions.php';

// Check cookie first
checkRememberMe($db);

if (isLoggedIn()) {
    header("Location: profile.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginId = $_POST['login_id']; // Can be Student/Staff ID or Email
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    // Fetch data from users table
    $sql = "SELECT * FROM users WHERE Email = :login OR UserId = :login";
    $stmt = $db->prepare($sql);
    $stmt->execute([':login' => $loginId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['HashedPassword'])) {
        // Set Session
        $_SESSION['user_id'] = $user['UserId'];
        $_SESSION['role'] = $user['Role'];
        $_SESSION['name'] = $user['Name'];

        // Handle Remember Me
        if ($remember) {
            $secret = "YOUR_SECRET_KEY";
            $token = $user['UserId'] . ':' . hash_hmac('sha256', $user['UserId'], $secret);
            // Set cookie for 30 days
            setcookie('remember_token', $token, time() + (86400 * 30), "/");
        }

        flash('success', 'Welcome back, ' . $user['Name']);
        header("Location: profile.php");
        exit;
    } else {
        flash('error', 'Invalid ID/Email or Password.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Canteen App</title>
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php flash(); ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Student/Staff ID or Email</label>
                <input type="text" name="login_id" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="remember"> Remember Me
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <div class="links">
            <a href="../pages/register.php">Register</a>
            <a href="../pages/forgot_password.php">Forgot Password?</a>
        </div>
    </div>
</body>
</html>