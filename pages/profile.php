<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    flash('warning', 'Please login to access your profile.');
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

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
    header("Location: profile.php");
    exit;
}

$sql = "SELECT * FROM users WHERE UserId = :id";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<!-- Link to the new Profile CSS -->
<link rel="stylesheet" href="/U-Order/assets/css/profile.css">

<div class="profile-wrapper">
    
    <div class="profile-card">
        <!-- Full width column for Back button & Title -->
        <div style="grid-column: span 2; margin:none; padding:none;">
            <a href="/U-Order/index.php" class="back-pill">
            <i class="fas fa-arrow-left"></i> Back to Menu
        </a>
             <h2 class="profile-section-title" style="border:none; margin-bottom:0; text-align:center;">My Profile</h2>
             <?php flash(); ?>
        </div>

        <!-- Left Column (Desktop): Info & Contact -->
        <div>
            <h3 class="profile-section-title">Personal Details</h3>
            <div class="form-group">
                <div class="info-row">
                    <span class="info-label">Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['Name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['Email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Role</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['Role']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Joined</span>
                    <span class="info-value"><?php echo date('d M Y', strtotime($user['CreatedAt'])); ?></span>
                </div>
            </div>

            <form method="POST" action="" style="margin-top: 30px;">
                <h3 class="profile-section-title">Contact Info</h3>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone_number" class="form-input" value="<?php echo htmlspecialchars($user['PhoneNumber']); ?>">
                </div>
                <button type="submit" name="update_phone" class="btn-action btn-update">Update Phone</button>
            </form>
        </div>

        <!-- Right Column (Desktop): Security -->
        <div>
            <form method="POST" action="">
                <h3 class="profile-section-title">Security</h3>
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-input" required>
                </div>
                <button type="submit" name="change_password" class="btn-action btn-update">Change Password</button>
            </form>

            <a href="logout.php" class="btn-action btn-logout">Logout</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>