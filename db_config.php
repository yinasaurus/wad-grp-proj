<?php
// Database Configuration
$host = 'sql301.infinityfree.com';          // Your database host
$user = 'if0_40323761';               // Your database user (default for XAMPP)
$password = 'greenbiz123';               // Your database password (empty for XAMPP default)
$database = 'if0_40323761_main'; // Your database name

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

// Set charset to utf8
$conn->set_charset("utf8");

// Function to get DB connection
function getDBConnection() {
    global $conn;
    return $conn;
}

// Optional: Set timezone
date_default_timezone_set('Asia/Singapore');
?>
