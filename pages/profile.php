<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    flash('warning', 'Please login to access your profile.');
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// --- HANDLE FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_phone'])) {
        $newPhone = trim($_POST['phone_number']);
        if (strlen($newPhone) < 8) {
            flash('error', 'Phone number is too short.');
        } else {
            $sql = "UPDATE users SET PhoneNumber = :phone WHERE UserId = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':phone' => $newPhone, ':id' => $userId]);
            flash('success', 'Phone number updated.');
        }
        // Redirect to prevent form resubmission
        header("Location: profile.php");
        exit;
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
        header("Location: profile.php");
        exit;
    }
}

// Fetch User Data
$sql = "SELECT * FROM users WHERE UserId = :id";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$initial = strtoupper(substr($user['Name'], 0, 1));

include '../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/profile.css">

<div class="profile-wrapper">
    <div class="profile-card">
        
        <div class="profile-hero">
            <a href="/U-Order/index.php" class="back-btn-hero">
                <i class="fas fa-arrow-left"></i> Menu
            </a>
            <?php flash(); ?>
        </div>

        <div class="profile-header-content">
            <div class="avatar-container">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['Name']); ?>&background=ffffff&color=2E3440&size=256&font-size=0.4&bold=true" alt="Avatar" class="avatar-img">
            </div>
            <div class="user-identity">
                <h1 class="user-name"><?php echo htmlspecialchars($user['Name']); ?></h1>
                <span class="user-role-badge"><?php echo htmlspecialchars($user['Role']); ?></span>
            </div>
        </div>

        <div class="profile-body">
            
            <div class="profile-column">
                <h3 class="section-title"><i class="fas fa-user-circle"></i> Personal Details</h3>
                
                <div class="info-list">
                    <div class="info-item">
                        <label>Email</label>
                        <span><?php echo htmlspecialchars($user['Email']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Joined</label>
                        <span><?php echo date('d M Y', strtotime($user['CreatedAt'])); ?></span>
                    </div>
                </div>

                <br>
                
                <form method="POST" action="">
                    <h3 class="section-title"><i class="fas fa-phone-alt"></i> Contact Info</h3>
                    <div class="modern-input-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone_number" class="modern-input" value="<?php echo htmlspecialchars($user['PhoneNumber']); ?>">
                    </div>
                    <button type="submit" name="update_phone" class="btn-primary">Update Phone</button>
                </form>
            </div>

            <div class="profile-column">
                <h3 class="section-title"><i class="fas fa-shield-alt"></i> Security</h3>
                
                <div class="security-section">
                    <div class="security-header" onclick="togglePasswordForm()">
                        <div class="security-status">
                            <strong style="color:var(--nord0);">Password</strong><br>
                            <span style="font-size:0.8rem; color:var(--nord3);">Manage your account security</span>
                        </div>
                        <button type="button" class="btn-toggle-password">Change</button>
                    </div>

                    <div class="password-form-container" id="passwordForm">
                        <form method="POST" action="">
                            <div class="modern-input-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" class="modern-input" placeholder="Enter current password" required>
                            </div>
                            <div class="modern-input-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" class="modern-input" placeholder="Enter new password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn-primary" style="background:var(--nord3);">Save Password</button>
                        </form>
                    </div>
                </div>

                <a href="logout.php" class="btn-primary btn-danger" style="display:flex; justify-content:center; align-items:center; gap:10px; text-decoration:none;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

        </div>
    </div>
</div>

<script>
    function togglePasswordForm() {
        const form = document.getElementById('passwordForm');
        const btn = document.querySelector('.btn-toggle-password');
        if (form.classList.contains('active')) {
            form.classList.remove('active');
            btn.textContent = "Change";
        } else {
            form.classList.add('active');
            btn.textContent = "Cancel";
        }
    }
</script>

<?php include '../includes/footer.php'; ?>