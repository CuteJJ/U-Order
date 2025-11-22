<?php
include '../includes/functions.php';

// Unset all session values
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear Remember Me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

session_start(); // Start a new session just to flash the message
flash('notice', 'You have been logged out.'); 
header("Location: login.php");
exit;
?>