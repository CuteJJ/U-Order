<?php

// Load .env file
if (!function_exists('loadEnv')) {
    function loadEnv($path)
    {
        if (!is_file($path)) return;

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            // Skip comments
            if ($line === '' || str_starts_with($line, '#')) continue;

            // Parse KEY=VALUE
            [$key, $value] = array_map('trim', explode('=', $line, 2));

            // Remove surrounding quotes if any
            $value = trim($value, "'\"");

            // Set environment variables
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Load .env from root
loadEnv(__DIR__ . '/../.env');

// Added error reporting for easier debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Kuala_Lumpur');

$db = new PDO('mysql:host=localhost;dbname=canteen;charset=utf8', 'root', '', [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,  // i set as fetch object  
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,    // just for easy debug if db connection error
]);

// Always start Session ,then every file no need session start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
