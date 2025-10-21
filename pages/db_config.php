<?php
// Database Configuration
$host = 'localhost';          // Your database host
$user = 'root';               // Your database user (default for XAMPP)
$password = '';               // Your database password (empty for XAMPP default)
$database = 'green_directory'; // Your database name

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