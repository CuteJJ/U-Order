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
        $rawPhone = trim($_POST['phone_number']);

        // 1. Remove dashes for backend validation logic
        $cleanPhone = str_replace('-', '', $rawPhone);

        // 2. Validate Phone Number
        // Starts with 01, followed by specific digits. Length 10-11 digits. Done in regex.
        if (!preg_match('/^01[0-46-9][0-9]{7,8}$/', $cleanPhone)) {
            flash('error', 'Invalid phone number format.');
        } else {
            // Save it cleanly or with dashes? (User preference: keeping dashes for display consistency)
            // Let's format it nicely for storage: 012-3456789
            if (strlen($cleanPhone) == 10) {
                $formatted = substr($cleanPhone, 0, 3) . '-' . substr($cleanPhone, 3);
            } elseif (strlen($cleanPhone) == 11) {
                $formatted = substr($cleanPhone, 0, 3) . '-' . substr($cleanPhone, 3);
            } else {
                $formatted = $cleanPhone; // Fallback
            }

            $sql = "UPDATE users SET PhoneNumber = :phone WHERE UserId = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':phone' => $formatted, ':id' => $userId]);
            flash('success', 'Phone number updated successfully.');
        }
        header("Location: profile.php");
        exit;
    } elseif (isset($_POST['change_password'])) {
        $currentPass = $_POST['current_password'];
        $newPass = $_POST['new_password'];

        $sql = "SELECT HashedPassword FROM users WHERE UserId = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($currentPass, $user['HashedPassword'])) {
            if (strlen($newPass) < 8 || strlen($newPass) > 16) {
                flash('error', 'New password must be 8-16 characters.');
            } else {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET HashedPassword = :pass WHERE UserId = :id";
                $stmt = $db->prepare($sql);
                $stmt->execute([':pass' => $newHash, ':id' => $userId]);
                flash('success', 'Password changed successfully.');
            }
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

include '../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/profile.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
                        <div class="input-wrapper">
                            <i class="fas fa-mobile-alt input-icon" style="z-index: 10; left: 16px; position:absolute;"></i>
                            <input type="text" id="phoneInput" name="phone_number" class="modern-input"
                                value="<?php echo htmlspecialchars($user['PhoneNumber']); ?>"
                                placeholder="01X-XXXXXXX"
                                maxlength="12" required
                                style="padding-left: 45px;">
                        </div>
                    </div>
                    <button type="submit" name="update_phone" class="btn-primary">Update Phone</button>
                </form>
            </div>

            <div class="profile-column">
                <h3 class="section-title"><i class="fas fa-shield-alt"></i> Security</h3>

                <div class="security-section">
                    <div class="security-header" onclick="togglePasswordForm()">
                        <div class="security-status">
                            <strong style="color:var(--text-main);">Password</strong><br>
                            <span style="font-size:0.8rem; color:var(--text-muted);">Manage your account security</span>
                        </div>
                        <button type="button" class="btn-toggle-password">Change</button>
                    </div>

                    <div class="password-form-container" id="passwordForm">
                        <form method="POST" action="">
                            <div class="modern-input-group">
                                <label>Current Password</label>
                                <div class="input-wrapper">
                                    <input type="password" name="current_password" class="modern-input" placeholder="Enter current password" required>
                                    <i class="fas fa-eye password-toggle"></i>
                                </div>
                            </div>
                            <div class="modern-input-group">
                                <label>New Password</label>
                                <div class="input-wrapper">
                                    <input type="password" name="new_password" class="modern-input" placeholder="Enter new password (8-16 chars)" required>
                                    <i class="fas fa-eye password-toggle"></i>
                                </div>
                            </div>
                            <button type="submit" name="change_password" class="btn-primary" style="background:var(--text-muted);">Save Password</button>
                        </form>
                    </div>
                </div>

                <a href="logout.php" onclick="return confirm('Are you sure you want to log out?');" class="btn-primary btn-danger" style="display:flex; justify-content:center; align-items:center; gap:10px; text-decoration:none;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="/U-Order/assets/js/auth.js"></script>

<script>
    // 1. Password Form Toggle Logic
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

    // 2. Phone Number Formatter
    document.getElementById('phoneInput').addEventListener('input', function(e) {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,8})/);
        // If user has typed at least 3 digits, format into 01X-XXXXXXX
        if (x[1].length >= 3) {
            e.target.value = x[1] + '-' + x[2];
        } else {
            e.target.value = x[1] + (x[2] ? '-' + x[2] : '');
        }
    });
</script>

<?php include '../includes/footer.php'; ?>