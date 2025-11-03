<?php
// Database Configuration
// Disable error display
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$host = 'localhost';          // Your database host
$user = 'root';               // Your database user (default for XAMPP)
$password = '';               // Your database password (empty for XAMPP default)
$database = 'green_directory'; // Your database name

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    // Log error instead of displaying
    error_log('Database connection failed: ' . $conn->connect_error);
    // Don't output anything here - let the calling script handle it
    $conn = null;
} else {
    // Set charset to utf8 only if connection succeeded
    $conn->set_charset("utf8");
}

// Function to get DB connection
function getDBConnection() {
    global $conn;
    return $conn;
}

// Optional: Set timezone
date_default_timezone_set('Asia/Singapore');
?>