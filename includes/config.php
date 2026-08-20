<?php

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'resident_db');

// Attempt to connect to MySQL database
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Disable exception mode for mysqli (PHP 8.1+ defaults to throwing exceptions)
// This lets our if(mysqli_prepare()) pattern work correctly
mysqli_report(MYSQLI_REPORT_OFF);

// Session timeout in seconds (e.g., 30 minutes)
define('SESSION_TIMEOUT', 1800);

// Allowed image types for upload
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png']);

// Maximum image size in bytes (e.g., 5MB)
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024);

// Maximum failed login attempts before lockout
define('MAX_LOGIN_ATTEMPTS', 4);

// Lockout duration in seconds (4 minutes)
define('LOCKOUT_TIME', 240);

// CSRF token session key name
define('CSRF_TOKEN_NAME', 'csrf_token');

?>
