<?php
include '../configs/db.php';
include '../includes/functions.php';

if (isLoggedIn()) {
    header("Location: profile.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentStaffId = $_POST['user_id']; // Using this as the PK
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone_number'];
    $role = 'customer'; // Default role
    $createdAt = date('Y-m-d H:i:s');

    // Basic Validation
    if (empty($studentStaffId) || empty($name) || empty($email) || empty($password)) {
        flash('error', 'Please fill in all required fields.');
    } else {
        // Check if ID or Email exists
        $sql = "SELECT UserId FROM users WHERE UserId = :id OR Email = :email";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $studentStaffId, ':email' => $email]);
        
        if ($stmt->rowCount() > 0) {
            flash('error', 'User ID or Email already registered.');
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $sql = "INSERT INTO users (UserId, Name, Email, HashedPassword, Role, PhoneNumber, CreatedAt) 
                        VALUES (:id, :name, :email, :pass, :role, :phone, :created)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':id' => $studentStaffId,
                    ':name' => $name,
                    ':email' => $email,
                    ':pass' => $hashedPassword,
                    ':role' => $role,
                    ':phone' => $phone,
                    ':created' => $createdAt
                ]);
                
                flash('success', 'Registration successful! Please login.');
                header("Location: login.php");
                exit;
            } catch (PDOException $e) {
                flash('error', 'Database error: ' . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Canteen App</title>
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <?php flash(); ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Student / Staff ID</label>
                <input type="number" name="user_id" required placeholder="e.g. 2200123">
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <div class="links">
            <a href="../pages/login.php">Already have an account? Login</a>
        </div>
    </div>
</body>
</html>