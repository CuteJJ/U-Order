<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'U-Order'; // Change to your database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

echo "<!-- Database connected successfully -->";
?>