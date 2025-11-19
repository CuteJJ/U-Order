<?php
date_default_timezone_set('Asia/Kuala_Lumpur');

$db = new PDO('mysql:host=localhost;dbname=canteen;charset=utf8', 'root', '', [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,  // i set as fetch object  
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,    // just for easy debug if db connection error
]);

// Always start Session ,then every file no need session start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>
