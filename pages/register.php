<?php
include '../configs/db.php';
include '../includes/functions.php';

$hideNav = true; 

if (isLoggedIn()) { header("Location: profile.php"); exit; }

$id_val = ''; $name_val = ''; $email_val = ''; $phone_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentStaffId = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $phone = $_POST['phone_number'];
    
    $id_val = $studentStaffId; $name_val = $name; $email_val = $email; $phone_val = $phone;

    if (empty($studentStaffId) || empty($name) || empty($email) || empty($password)) {
        flash('error', 'Please fill in all required fields.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Invalid email address format.');
    } elseif (strlen($password) < 8 || strlen($password) > 16) {
        flash('error', 'Password must be between 8 and 16 characters.');
    } elseif ($password !== $confirmPassword) {
        flash('error', 'Passwords do not match.');
    } else {
        // Logic abbreviated for brevity (same as your original)
        $sql = "SELECT UserId FROM users WHERE UserId = :id OR Email = :email";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $studentStaffId, ':email' => $email]);
        
        if ($stmt->rowCount() > 0) {
            flash('error', 'User ID or Email already registered.');
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            try {
                $sql = "INSERT INTO users (UserId, Name, Email, HashedPassword, Role, PhoneNumber, CreatedAt) VALUES (:id, :name, :email, :pass, 'customer', :phone, NOW())";
                $stmt = $db->prepare($sql);
                $stmt->execute([':id'=>$studentStaffId, ':name'=>$name, ':email'=>$email, ':pass'=>$hashedPassword, ':phone'=>$phone]);
                flash('success', 'Registration successful!');
                header("Location: login.php");
                exit;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    flash('error', 'User ID or Email already registered.');
                } else {
                    flash('error', 'System error occurred.');
                }
            }
        }
    }
}
include '../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/auth.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-image-side" style="background-image: url('https://images.unsplash.com/photo-1552611052-33e04de081de?ixlib=rb-1.2.1&auto=format&fit=crop&w=1000&q=80');">
            <div class="auth-image-text">
                <h1>Join<br>Us.</h1>
                <p>Skip the queue. Eat better.</p>
            </div>
        </div>

        <div class="auth-form-side">
            <h2>Register</h2>
            <p class="sub-text">Create your account to get started.</p>
            <?php flash(); ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-id-card input-icon"></i>
                        <input type="number" name="user_id" required placeholder="Student/Staff ID" value="<?php echo htmlspecialchars($id_val); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="name" required placeholder="Full Name" value="<?php echo htmlspecialchars($name_val); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" required placeholder="Email Address" value="<?php echo htmlspecialchars($email_val); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="text" name="phone_number" placeholder="Phone (Optional)" value="<?php echo htmlspecialchars($phone_val); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" required placeholder="Password (8-16 chars)">
                        <i class="fas fa-eye password-toggle"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="confirm_password" required placeholder="Confirm Password">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Register</button>
            </form>
            
            <div class="links">
                Already have an account? <a href="login.php">Login</a>
                <br><br>
                <a href="../index.php" style="color: var(--text-muted); font-weight: 500;">&larr; Back to Home</a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="/U-Order/assets/js/auth.js"></script>

<?php include '../includes/footer.php'; ?>