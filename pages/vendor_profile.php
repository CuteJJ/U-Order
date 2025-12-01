<?php
require_once "../configs/db.php";
require_once "../includes/functions.php";

if (!isLoggedIn() || $_SESSION['role'] !== 'vendor') {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// 讀取 vendor 資料
$sql = "SELECT u.UserId, u.Name, u.Email, u.PhoneNumber, u.Role, u.CreatedAt,
               s.StallName, s.Description
        FROM users u
        JOIN stalls s ON s.StaffId = u.UserId
        WHERE u.UserId = ? LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
    die("Vendor not found.");
}

$joined = date('d M Y', strtotime($vendor['CreatedAt']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Vendor Profile</title>
    <link rel="stylesheet" href="../assets/css/vendor_profile.css">
</head>
<body>

<?php include 'vendor_sidebar.php'; ?>

<div class="vp-main">
    <div class="vp-inner">

        <h1 class="vp-page-title">My Profile</h1>

        <!-- 全局通知（保存成功 / 失敗） -->
        <div id="vp-global-notif" class="vp-global-notif" aria-live="polite"></div>

        <div class="vp-layout">

            <!-- 左側：個人資料 / 聯絡 -->
            <div class="vp-column">

                <!-- Personal details -->
                <section class="vp-card">
                    <div class="vp-card-header">
                        <div>
                            <h2 class="vp-card-title">Personal Details</h2>
                            <p class="vp-card-subtitle">Basic information about your account.</p>
                        </div>
                    </div>

                    <div class="vp-field-row">
                        <div class="vp-field-label">Name</div>
                        <div class="vp-field-static"><?= htmlspecialchars($vendor['Name']) ?></div>
                    </div>
                    <div class="vp-field-row">
                        <div class="vp-field-label">Email</div>
                        <div class="vp-field-static"><?= htmlspecialchars($vendor['Email']) ?></div>
                    </div>
                    <div class="vp-field-row">
                        <div class="vp-field-label">Role</div>
                        <div class="vp-field-static"><?= htmlspecialchars($vendor['Role']) ?></div>
                    </div>
                    <div class="vp-field-row">
                        <div class="vp-field-label">Joined</div>
                        <div class="vp-field-static"><?= htmlspecialchars($joined) ?></div>
                    </div>
                    <div class="vp-field-row">
                        <div class="vp-field-label">Stall</div>
                        <div class="vp-field-static"><?= htmlspecialchars($vendor['StallName']) ?></div>
                    </div>
                </section>

                <!-- Phone -->
                <section class="vp-card vp-field" data-field="phone">
                    <div class="vp-card-header">
                        <div>
                            <h2 class="vp-card-title">Contact</h2>
                            <p class="vp-card-subtitle">Used for students or admin to contact you.</p>
                        </div>
                        <button type="button" class="vp-icon-btn vp-edit-trigger">
                            <span class="vp-icon-pencil" aria-hidden="true"></span>
                            <span class="vp-edit-text">Edit</span>
                        </button>
                    </div>

                    <div class="vp-field-body">
                        <div class="vp-field-label-inline">Phone Number</div>
                        <div class="vp-display-value" data-display-for="phone">
                            <?= $vendor['PhoneNumber'] !== null && $vendor['PhoneNumber'] !== '' 
                                ? htmlspecialchars($vendor['PhoneNumber']) 
                                : '<span class="vp-muted">Not set</span>' ?>
                        </div>

                        <div class="vp-edit-area">
                            <input
                                type="text"
                                name="phone"
                                class="vp-input"
                                placeholder="10–11 digits, numbers only"
                                value="<?= htmlspecialchars($vendor['PhoneNumber']) ?>"
                            >
                            <div class="vp-hint">Must be 10–11 digits, numbers only.</div>

                            <div class="vp-edit-actions">
                                <button type="button" class="vp-btn vp-btn-secondary vp-cancel-edit">
                                    Cancel
                                </button>
                                <button type="button" class="vp-btn vp-btn-primary vp-save-edit"
                                        data-action="update_phone">
                                    Save
                                </button>
                            </div>
                            <div class="vp-error" data-error-for="phone"></div>
                        </div>
                    </div>
                </section>

            </div>

            <!-- 右側：Security + Stall Description -->
            <div class="vp-column">

                <!-- Security -->
                <section class="vp-card vp-field" data-field="password">
                    <div class="vp-card-header">
                        <div>
                            <h2 class="vp-card-title">Security</h2>
                            <p class="vp-card-subtitle">Change your account password.</p>
                        </div>
                        <button type="button" class="vp-icon-btn vp-edit-trigger">
                            <span class="vp-icon-pencil" aria-hidden="true"></span>
                            <span class="vp-edit-text">Edit</span>
                        </button>
                    </div>

                    <div class="vp-field-body">
                        <div class="vp-field-label-inline">Password</div>
                        <div class="vp-display-value" data-display-for="password">
                            ••••••••
                        </div>

                        <div class="vp-edit-area">
                            <div class="vp-field-group">
                                <label class="vp-label" for="vp-current-password">Current password</label>
                                <input
                                    id="vp-current-password"
                                    type="password"
                                    name="old_password"
                                    class="vp-input"
                                    autocomplete="current-password"
                                >
                            </div>

                            <div class="vp-field-group">
                                <label class="vp-label" for="vp-new-password">New password</label>
                                <input
                                    id="vp-new-password"
                                    type="password"
                                    name="new_password"
                                    class="vp-input"
                                    autocomplete="new-password"
                                    placeholder="At least 6 characters"
                                >
                                <div class="vp-hint">Make sure this is something secure and easy to remember.</div>
                            </div>

                            <div class="vp-edit-actions">
                                <button type="button" class="vp-btn vp-btn-secondary vp-cancel-edit">
                                    Cancel
                                </button>
                                <button type="button" class="vp-btn vp-btn-primary vp-save-edit"
                                        data-action="update_password">
                                    Save
                                </button>
                            </div>
                            <div class="vp-error" data-error-for="password"></div>
                        </div>
                    </div>
                </section>

                <!-- Stall Description（移到这里） -->
                <section class="vp-card vp-field" data-field="description">
                    <div class="vp-card-header">
                        <div>
                            <h2 class="vp-card-title">Stall Description</h2>
                            <p class="vp-card-subtitle">Short description shown to students.</p>
                        </div>
                        <button type="button" class="vp-icon-btn vp-edit-trigger">
                            <span class="vp-icon-pencil" aria-hidden="true"></span>
                            <span class="vp-edit-text">Edit</span>
                        </button>
                    </div>

                    <div class="vp-field-body">
                        <div class="vp-display-value vp-display-multiline" data-display-for="description">
                            <?php
                            $desc = trim((string)$vendor['Description']);
                            echo $desc !== '' 
                                ? nl2br(htmlspecialchars($desc))
                                : '<span class="vp-muted">No description yet.</span>';
                            ?>
                        </div>

                        <div class="vp-edit-area">
                            <textarea
                                name="description"
                                class="vp-textarea"
                                rows="4"
                                placeholder="Describe what your stall offers..."
                            ><?= htmlspecialchars($vendor['Description']) ?></textarea>

                            <div class="vp-edit-actions">
                                <button type="button" class="vp-btn vp-btn-secondary vp-cancel-edit">
                                    Cancel
                                </button>
                                <button type="button" class="vp-btn vp-btn-primary vp-save-edit"
                                        data-action="update_description">
                                    Save
                                </button>
                            </div>
                            <div class="vp-error" data-error-for="description"></div>
                        </div>
                    </div>
                </section>

            </div>

        </div>
    </div>
</div>

<script src="../assets/js/vendor_profile.js"></script>
</body>
</html>
