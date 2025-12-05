<?php
// pages/vendor_profile.php
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
$initials = strtoupper(substr($vendor['Name'], 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Vendor Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/vendor_profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="page-wrapper">
    <?php include 'vendor_sidebar.php'; ?>

    <div class="vp-main-content">
        
        <div id="vp-global-notif" class="vp-global-notif" aria-live="polite"></div>

        <div class="vp-container">
            
            <div class="vp-header-card">
                <div class="vp-header-bg"></div>
                
                <div class="vp-header-body">
                    <div class="vp-avatar-wrapper">
                        <div class="vp-avatar"><?= $initials ?></div>
                    </div>
                    
                    <div class="vp-identity">
                        <h1 class="vp-username"><?= htmlspecialchars($vendor['Name']) ?></h1>
                        <span class="vp-badge">VENDOR</span>
                    </div>
                </div>
            </div>

            <div class="vp-grid-layout">
                
                <div class="vp-col">
                    
                    <div class="vp-section">
                        <div class="vp-section-header">
                            <h2 class="vp-section-title">
                                <span class="vp-icon">👤</span> Personal Details
                            </h2>
                        </div>
                        
                        <div class="vp-info-card">
                            <div class="vp-info-row">
                                <label>Email</label>
                                <div class="vp-static-value"><?= htmlspecialchars($vendor['Email']) ?></div>
                            </div>
                            <div class="vp-info-row">
                                <label>Joined</label>
                                <div class="vp-static-value"><?= htmlspecialchars($joined) ?></div>
                            </div>
                            <div class="vp-info-row">
                                <label>Stall</label>
                                <div class="vp-static-value"><?= htmlspecialchars($vendor['StallName']) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="vp-section" data-field="phone">
                        <div class="vp-section-header">
                            <h2 class="vp-section-title">
                                <span class="vp-icon">📞</span> Contact Info
                            </h2>
                        </div>

                        <div class="vp-info-card">
                            <div class="vp-edit-row">
                                
                                <div class="vp-display-mode">
                                    <div class="vp-label-group">
                                        <label>Phone Number</label>
                                        <div class="vp-value-text">
                                            <?= $vendor['PhoneNumber'] ? htmlspecialchars($vendor['PhoneNumber']) : '<span class="vp-placeholder">Add phone number</span>' ?>
                                        </div>
                                    </div>
                                    <button type="button" class="vp-edit-link vp-edit-trigger">Edit</button>
                                </div>

                                <div class="vp-edit-mode">
                                    <label>Phone Number</label>
                                    <input type="text" name="phone" class="vp-input" value="<?= htmlspecialchars($vendor['PhoneNumber']) ?>" placeholder="e.g. 0123456789">
                                    <div class="vp-action-btns">
                                        <button type="button" class="vp-btn vp-btn-primary vp-save-edit" data-action="update_phone">Save</button>
                                        <button type="button" class="vp-btn vp-btn-ghost vp-cancel-edit">Cancel</button>
                                    </div>
                                    <div class="vp-error" data-error-for="phone"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="vp-col">
                    
                    <div class="vp-section" data-field="password">
                        <div class="vp-section-header">
                            <h2 class="vp-section-title">
                                <span class="vp-icon">🛡️</span> Security
                            </h2>
                        </div>

                        <div class="vp-info-card">
                            <div class="vp-edit-row">
                                
                                <div class="vp-display-mode">
                                    <div>
                                        <div style="font-weight:600; color:#1e293b; margin-bottom: 2px;">Password</div>
                                        <div style="font-size:13px; color:#64748b;">Manage account security</div>
                                    </div>
                                    <button type="button" class="vp-btn-outline vp-edit-trigger">Change</button>
                                </div>

                                <div class="vp-edit-mode">
                                    <div class="vp-form-group">
                                        <label>Current Password</label>
                                        <input type="password" name="old_password" class="vp-input">
                                    </div>
                                    <div class="vp-form-group">
                                        <label>New Password</label>
                                        <input type="password" name="new_password" class="vp-input" placeholder="Min. 6 chars">
                                    </div>
                                    <div class="vp-action-btns">
                                        <button type="button" class="vp-btn vp-btn-primary vp-save-edit" data-action="update_password">Update</button>
                                        <button type="button" class="vp-btn vp-btn-ghost vp-cancel-edit">Cancel</button>
                                    </div>
                                    <div class="vp-error" data-error-for="password"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vp-section" data-field="description">
                        <div class="vp-section-header">
                            <h2 class="vp-section-title">
                                <span class="vp-icon">🏪</span> Stall Info
                            </h2>
                        </div>

                        <div class="vp-info-card">
                            <div class="vp-edit-row">
                                
                                <div class="vp-display-mode">
                                    <div class="vp-label-group" style="width: 100%;">
                                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                            <label>Description</label>
                                            <button type="button" class="vp-edit-link vp-edit-trigger">Edit</button>
                                        </div>
                                        <div class="vp-value-text">
                                            <?php 
                                                $desc = trim((string)$vendor['Description']);
                                                echo $desc ? nl2br(htmlspecialchars($desc)) : '<span class="vp-placeholder">No description yet.</span>';
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="vp-edit-mode">
                                    <label>Description</label>
                                    <textarea name="description" class="vp-textarea" rows="4"><?= htmlspecialchars($vendor['Description']) ?></textarea>
                                    <div class="vp-action-btns">
                                        <button type="button" class="vp-btn vp-btn-primary vp-save-edit" data-action="update_description">Save</button>
                                        <button type="button" class="vp-btn vp-btn-ghost vp-cancel-edit">Cancel</button>
                                    </div>
                                    <div class="vp-error" data-error-for="description"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/vendor_profile.js"></script>
</body>
</html>