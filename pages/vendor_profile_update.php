<?php
require_once "../configs/db.php";
require_once "../includes/functions.php";

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'vendor') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized.'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'update_phone') {

    $phone = trim($_POST['phone'] ?? '');

    if (!preg_match('/^\d{10,11}$/', $phone)) {
        echo json_encode([
            'success' => false,
            'message' => 'Phone must be 10–11 digits, numbers only.'
        ]);
        exit;
    }

    $stmt = $db->prepare("UPDATE users SET PhoneNumber = ? WHERE UserId = ?");
    $stmt->execute([$phone, $userId]);

    echo json_encode([
        'success' => true,
        'message' => 'Phone number updated.',
        'newValue' => $phone
    ]);
    exit;
}

if ($action === 'update_description') {

    $description = trim($_POST['description'] ?? '');
    if (strlen($description) > 500) {
        echo json_encode([
            'success' => false,
            'message' => 'Description must be at most 500 characters.'
        ]);
        exit;
    }

    $stmt = $db->prepare("UPDATE stalls SET Description = ? WHERE StaffId = ?");
    $stmt->execute([$description, $userId]);

    echo json_encode([
        'success' => true,
        'message' => 'Description updated.',
        'newValue' => $description
    ]);
    exit;
}

if ($action === 'update_password') {

    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if ($oldPassword === '' || $newPassword === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Current and new password are required.'
        ]);
        exit;
    }

    if (strlen($newPassword) < 6) {
        echo json_encode([
            'success' => false,
            'message' => 'New password must be at least 6 characters.'
        ]);
        exit;
    }

    // 取目前密碼 hash
    $stmt = $db->prepare("SELECT HashedPassword FROM users WHERE UserId = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($oldPassword, $row['HashedPassword'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Current password is incorrect.'
        ]);
        exit;
    }

    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

    $update = $db->prepare("UPDATE users SET HashedPassword = ? WHERE UserId = ?");
    $update->execute([$newHash, $userId]);

    echo json_encode([
        'success' => true,
        'message' => 'Password updated successfully.'
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Invalid action.'
]);
