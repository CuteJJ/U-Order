<?php
include '../configs/db.php';
include '../includes/functions.php';

$hideNav = true; 

if (isLoggedIn()) { header("Location: profile.php"); exit; }

// Initialize variables to empty strings to avoid PHP warnings on first load
$id_val = '';
$name_val = '';
$email_val = '';
$phone_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentStaffId = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $phone = $_POST['phone_number'];
    
    // Keep input values to repopulate the form (Sticky Form)
    $id_val = $studentStaffId;
    $name_val = $name;
    $email_val = $email;
    $phone_val = $phone;

    // (Basic Logic Abbreviated)
    if (empty($studentStaffId) || empty($name) || empty($email) || empty($password)) {
        flash('error', 'Please fill in all required fields.');

    // Email validation
    }if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Invalid email address format.');

    // Password validation
    }if (strlen($password) < 8 || strlen($password) > 16) {
        flash('error', 'Password must be at least 8-16 characters long.');
    

    // Confirm Password validation
    }if ($password !== $confirmPassword) {
        flash('error', 'Passwords do not match.');
    }else {

        // Check if ID or Email exists
        $sql = "SELECT UserId FROM users WHERE UserId = :id OR Email = :email";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $studentStaffId, ':email' => $email]);
        
        if ($stmt->rowCount() > 0) {
            flash('error', 'User ID or Email already registered.');
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
                // Secure Error Handling
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    flash('error', 'User ID or Email already registered.');
                } else {
                    error_log("Register Error: " . $e->getMessage()); 
                    flash('error', 'System error occurred. Please try again.');
                }
            }
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
                    <input type="number" name="user_id" required placeholder="e.g. 2200123" value="<?php echo htmlspecialchars($id_val); ?>">
                </div>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required placeholder="John Doe" value="<?php echo htmlspecialchars($name_val); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="student@uni.edu.my" value="<?php echo htmlspecialchars($email_val); ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone_number" placeholder="012-3456789" value="<?php echo htmlspecialchars($phone_val); ?>">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="password" required placeholder="Min 8-16 characters" style="padding-right: 40px;">
                        <span onclick="togglePassword('password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none;">👁️</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div style="position: relative;">
                        <input type="password" name="confirm_password" id="confirm_password" required placeholder="Re-enter password" style="padding-right: 40px;">
                        <span onclick="togglePassword('confirm_password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none;">👁️</span>
                    </div>
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

<script>
    function togglePassword(fieldId, icon) {
        const field = document.getElementById(fieldId);
        if (field.type === "password") {
            field.type = "text";
            icon.textContent = "🙈"; // Icon for hidden
        } else {
            field.type = "password";
            icon.textContent = "👁️"; // Icon for visible
        }
    }
</script>

<?php include '../includes/footer.php'; ?>