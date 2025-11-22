<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vendor User Details
    $vendorId = $_POST['vendor_id']; // Staff ID
    $vendorName = $_POST['vendor_name'];
    $vendorEmail = $_POST['vendor_email'];
    $vendorPass = $_POST['vendor_password'];
    
    // Stall Details
    $stallName = $_POST['stall_name'];
    $stallDesc = $_POST['stall_desc'];

    try {
        $db->beginTransaction();

        // 1. Create Vendor User
        $hashedPass = password_hash($vendorPass, PASSWORD_DEFAULT);
        $sqlUser = "INSERT INTO users (UserId, Name, Email, HashedPassword, Role, CreatedAt) 
                    VALUES (:id, :name, :email, :pass, 'vendor', NOW())";
        $stmt = $db->prepare($sqlUser);
        $stmt->execute([
            ':id' => $vendorId,
            ':name' => $vendorName,
            ':email' => $vendorEmail,
            ':pass' => $hashedPass
        ]);

        // 2. Create Stall linked to Vendor
        $sqlStall = "INSERT INTO stalls (StaffId, StallName, Description, IsAvailable, CreatedAt) 
                     VALUES (:sid, :sname, :sdesc, 1, NOW())";
        $stmt = $db->prepare($sqlStall);
        $stmt->execute([
            ':sid' => $vendorId,
            ':sname' => $stallName,
            ':sdesc' => $stallDesc
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register New Stall</title>
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
    <div class="container">
        <a href="admin_dashboard.php">&larr; Back to Dashboard</a>
        <h2>Register New Stall & Vendor</h2>
        <?php flash(); ?>

        <form method="POST" action="" style="max-width:600px; background:white; padding:30px; border-radius:8px;">
            
            <h4>Step 1: Vendor Account Details</h4>
            <div class="form-group">
                <label>Staff ID (Login ID)</label>
                <input type="text" name="vendor_id" required placeholder="e.g. 8001">
            </div>
            <div class="form-group">
                <label>Vendor Name</label>
                <input type="text" name="vendor_name" required placeholder="e.g. Mr. Tan">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="vendor_email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="vendor_password" required>
            </div>

            <hr>

            <h4>Step 2: Stall Details</h4>
            <div class="form-group">
                <label>Stall Name</label>
                <input type="text" name="stall_name" required placeholder="e.g. Nasi Lemak Best">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="stall_desc" rows="3" placeholder="Description of food sold..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Create Stall</button>
        </form>
    </div>
</body>
</html>