<?php
include '../configs/db.php';
include '../includes/functions.php';

$hideNav = true; 

if (isLoggedIn()) { header("Location: profile.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentStaffId = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone_number'];
    
    // (Basic Logic Abbreviated)
    if (empty($studentStaffId) || empty($name) || empty($email) || empty($password)) {
        flash('error', 'Please fill in all required fields.');
    } else {
        // ... Checks ...
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try {
            $sql = "INSERT INTO users (UserId, Name, Email, HashedPassword, Role, PhoneNumber, CreatedAt) VALUES (:id, :name, :email, :pass, 'customer', :phone, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id'=>$studentStaffId, ':name'=>$name, ':email'=>$email, ':pass'=>$hashedPassword, ':phone'=>$phone]);
            flash('success', 'Registration successful!');
            header("Location: login.php");
            exit;
        } catch (PDOException $e) {
            flash('error', 'Database error: ' . $e->getMessage());
        }
    }
}
include '../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/auth.css">

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-image-side" style="background-image: url('https://images.unsplash.com/photo-1552611052-33e04de081de?ixlib=rb-1.2.1&auto=format&fit=crop&w=1000&q=80');">
            <div class="auth-image-text">
                <h1>Join Us</h1>
                <p>Skip the queue.</p>
            </div>
        </div>

        <div class="auth-form-side">
            <h2>Register</h2>
            <?php flash(); ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>ID</label>
                    <input type="number" name="user_id" required placeholder="e.g. 2200123">
                </div>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="student@uni.edu.my">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone_number" placeholder="012-3456789">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Register</button>
            </form>
            <div class="links">
                <a href="login.php">Login</a>
                <br><br>
                <a href="../index.php">&larr; Home</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>