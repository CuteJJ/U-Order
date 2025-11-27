<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Collect Input
    $vendorId = trim($_POST['vendor_id']);
    $vendorName = trim($_POST['vendor_name']);
    $vendorEmail = trim($_POST['vendor_email']);
    $vendorPhone = trim($_POST['vendor_phone']);
    $vendorPass = $_POST['vendor_password'];
    $confirmPass = $_POST['confirm_password'];
    
    $stallName = trim($_POST['stall_name']);
    $stallDesc = trim($_POST['stall_desc']);

    // 2. Validation
    $errors = [];

    // Basic fields
    if (empty($vendorId) || empty($vendorName) || empty($stallName)) {
        $errors[] = 'Vendor ID, Name, and Stall Name are required.';
    }

    // Email validation
    if (!filter_var($vendorEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address format.';
    }

    // Password validation
    if (strlen($vendorPass) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    if ($vendorPass !== $confirmPass) {
        $errors[] = 'Passwords do not match.';
    }

    // 3. Handle File Upload (Stall Logo)
    $logoUrl = null;
    if (isset($_FILES['stall_logo']) && $_FILES['stall_logo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['stall_logo']['name'];
        $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowed)) {
            // Create unique name
            $newFilename = "stall_" . time() . "." . $fileExt;
            $uploadDir = "../assets/images/stalls/";
            
            // Create directory if not exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $destPath = $uploadDir . $newFilename;
            
            if (move_uploaded_file($_FILES['stall_logo']['tmp_name'], $destPath)) {
                // Save relative path to DB (matches your sql table `LogoUrl`)
                $logoUrl = "images/stalls/" . $newFilename;
            } else {
                $errors[] = 'Failed to move uploaded file.';
            }
        } else {
            $errors[] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
        }
    }

    // 4. Process Database Insertion if No Errors
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Create Vendor User
            $hashedPass = password_hash($vendorPass, PASSWORD_DEFAULT);
            $sqlUser = "INSERT INTO users (UserId, Name, Email, HashedPassword, Role, PhoneNumber, CreatedAt) 
                        VALUES (:id, :name, :email, :pass, 'vendor', :phone, NOW())";
            $stmt = $db->prepare($sqlUser);
            $stmt->execute([
                ':id' => $vendorId,
                ':name' => $vendorName,
                ':email' => $vendorEmail,
                ':pass' => $hashedPass,
                ':phone' => $vendorPhone
            ]);

            // Create Stall linked to Vendor
            // Using LogoUrl as per your SQL dump
            $sqlStall = "INSERT INTO stalls (StaffId, StallName, Description, LogoUrl, IsAvailable, CreatedAt) 
                         VALUES (:sid, :sname, :sdesc, :logo, 1, NOW())";
            $stmt = $db->prepare($sqlStall);
            $stmt->execute([
                ':sid' => $vendorId,
                ':sname' => $stallName,
                ':sdesc' => $stallDesc,
                ':logo' => $logoUrl
            ]);

            $db->commit();
            flash('success', "Stall '$stallName' and Vendor '$vendorName' created successfully!");
            header("Location: admin_dashboard.php");
            exit;

        } catch (PDOException $e) {
            $db->rollBack();
            // Check for duplicate entry error
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                flash('error', 'Error: User ID or Email already exists.');
            } else {
                flash('error', 'Database Error: ' . $e->getMessage());
            }
        }
    } else {
        // Display validation errors
        foreach ($errors as $error) {
            flash('error', $error);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register New Stall</title>
    <link rel="stylesheet" href="../assets/css/aurora_theme.css">
</head>
<body>
    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="admin_dashboard.php" style="font-weight: 600;">&larr; Back to Dashboard</a>
        </div>
        
        <div class="card" style="max-width: 700px; margin: 0 auto;">
            <div style="background: var(--text-main); color: white; padding: 25px;">
                <h2 style="color: white; margin: 0;">Register New Stall</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.8;">Create a vendor account and stall profile.</p>
            </div>
            
            <div style="padding: 30px;">
                <?php flash(); ?>

                <!-- Added enctype for file upload -->
                <form method="POST" action="" enctype="multipart/form-data" style="box-shadow: none; border: none; padding: 0;">
                    
                    <h4 style="color: var(--aurora-orange); border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">1. Vendor Account Details</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Staff ID</label>
                            <input type="text" name="vendor_id" required placeholder="e.g. 8001" value="<?php echo $_POST['vendor_id'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Vendor Name</label>
                            <input type="text" name="vendor_name" required placeholder="e.g. Mr. Tan" value="<?php echo $_POST['vendor_name'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="vendor_email" required value="<?php echo $_POST['vendor_email'] ?? ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="vendor_phone" required placeholder="e.g. 012-3456789" value="<?php echo $_POST['vendor_phone'] ?? ''; ?>">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Password</label>
                            <div style="position: relative;">
                                <input type="password" name="vendor_password" id="vendor_password" required placeholder="Min 8-16 characters" style="padding-right: 40px;">
                                <span onclick="togglePassword('vendor_password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none;">👁️</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <div style="position: relative;">
                                <input type="password" name="confirm_password" id="confirm_password" required placeholder="Re-enter password" style="padding-right: 40px;">
                                <span onclick="togglePassword('confirm_password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none;">👁️</span>
                            </div>
                        </div>
                    </div>

                    <h4 style="color: var(--aurora-orange); border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; margin-top: 30px;">2. Stall Profile</h4>
                    
                    <!-- File Upload Field -->
                    <div class="form-group">
                        <label>Stall Logo (Optional)</label>
                        <input type="file" name="stall_logo" accept="image/*">
                        <small style="color: #666;">Supported: JPG, PNG, GIF</small>
                    </div>

                    <div class="form-group">
                        <label>Stall Name</label>
                        <input type="text" name="stall_name" required placeholder="e.g. Nasi Lemak Best" value="<?php echo $_POST['stall_name'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="stall_desc" rows="3" placeholder="Brief description of the food..."><?php echo $_POST['stall_desc'] ?? ''; ?></textarea>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Create Stall & Vendor</button>
                    </div>
                </form>
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
</body>
</html>