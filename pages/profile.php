<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    flash('warning', 'Please login to access your profile.');
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_phone'])) {
        $newPhone = $_POST['phone_number'];
        $sql = "UPDATE users SET PhoneNumber = :phone WHERE UserId = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':phone' => $newPhone, ':id' => $userId]);
        flash('success', 'Phone number updated.');
    } 
    elseif (isset($_POST['change_password'])) {
        $currentPass = $_POST['current_password'];
        $newPass = $_POST['new_password'];
        
        // Verify current
        $sql = "SELECT HashedPassword FROM users WHERE UserId = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($currentPass, $user['HashedPassword'])) {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET HashedPassword = :pass WHERE UserId = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':pass' => $newHash, ':id' => $userId]);
            flash('success', 'Password changed successfully.');
        } else {
            flash('error', 'Current password incorrect.');
        }
    }
    // Refresh to prevent resubmission
    header("Location: profile.php");
    exit;
}

// Fetch data from users table for display
$sql = "SELECT * FROM users WHERE UserId = :id";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
    <div class="container">
        <h2>User Profile</h2>
        <?php flash(); ?>
        
        <div class="form-group">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['Name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['Email']); ?></p>
            <p><strong>Role:</strong> <?php echo htmlspecialchars($user['Role']); ?></p>
            <p><strong>Joined:</strong> <?php echo htmlspecialchars($user['CreatedAt']); ?></p>
        </div>

        <hr style="border: 0; border-top: 1px solid #D8DEE9; margin: 20px 0;">

        <!-- Update Phone -->
        <form method="POST" action="">
            <h3>Update Contact</h3>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['PhoneNumber']); ?>">
            </div>
            <button type="submit" name="update_phone" class="btn btn-primary">Update Phone</button>
        </form>

        <hr style="border: 0; border-top: 1px solid #D8DEE9; margin: 20px 0;">

        <!-- Change Password -->
        <form method="POST" action="">
            <h3>Change Password</h3>
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required>
            </div>
            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
        </form>

        <a href="../pages/logout.php" class="btn btn-secondary">Logout</a>
    </div>
</body>
</html>