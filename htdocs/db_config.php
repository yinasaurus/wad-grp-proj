<?php
// Database Configuration
// Disable error display
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$host = 'sql113.infinityfree.com';          // database host
$user = 'if0_40329348';               // database user
$password = '4N4K48tfL4k3';               // database password
$database = 'if0_40329348_greenbiz'; // database name

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    $conn = null;
} else {
    $conn->set_charset("utf8");
    $conn->query("SET time_zone = '+08:00'");
}

function getDBConnection() {
    global $conn;
    return $conn;
}

date_default_timezone_set('Asia/Singapore');