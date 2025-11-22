<?php
include 'configs/db.php';
include 'includes/functions.php';

// Attempt to restore session via "Remember Me" cookie
checkRememberMe($db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>U-Order - Index</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        .hub-menu {
            display: grid;
            gap: 1rem;
            text-align: left;
        }
        .status-indicator {
            margin-bottom: 1.5rem;
            padding: 0.5rem;
            border-radius: 4px;
            text-align: center;
            font-size: 0.9rem;
        }
        .status-active {
            background-color: var(--nord14);
            color: var(--nord0);
        }
        .status-inactive {
            background-color: var(--nord11);
            color: var(--nord6);
        }
        .description {
            font-size: 0.85rem;
            color: var(--nord3);
            margin-bottom: 0.2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Canteen App Hub</h2>
        
        <!-- Session Status -->
        <?php if (isLoggedIn()): ?>
            <div class="status-indicator status-active">
                <strong>Status:</strong> Logged In as <?php echo htmlspecialchars($_SESSION['name']); ?>
            </div>
        <?php else: ?>
            <div class="status-indicator status-inactive">
                <strong>Status:</strong> Guest (Not Logged In)
            </div>
        <?php endif; ?>

        <?php flash(); ?>

        <div class="hub-menu">
            <?php if (isLoggedIn()): ?>
                <!-- Authenticated Options -->
                 <div>
                    <div class="description">Manage your account details</div>
                    <a href="pages/profile.php" class="btn btn-primary">Go to Profile</a>
                </div>
                <div>
                    <div class="description">End current session</div>
                    <a href="pages/logout.php" class="btn btn-secondary">Logout</a>
                </div>
            
            <?php else: ?>
                <!-- Guest Options -->
                <div>
                    <div class="description">Access existing account</div>
                    <a href="pages/login.php" class="btn btn-primary">Login</a>
                </div>
                <div>
                    <div class="description">Create a new student/staff account</div>
                    <a href="pages/register.php" class="btn btn-secondary">Register New Account</a>
                </div>
                <div>
                    <div class="description">Test password reset flow (email simulation)</div>
                    <a href="pages/forgot_password.php" style="display:block; text-align:center; margin-top:10px; color: var(--nord10); text-decoration:none;">Forgot Password?</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>