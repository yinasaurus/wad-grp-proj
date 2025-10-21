<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Test 1: PHP is working\n\n";

echo "Test 2: Checking file paths\n";
echo "Current directory: " . __DIR__ . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n\n";

$dbConfigPath = '../../db_config.php';
echo "Test 3: Looking for db_config.php\n";
echo "Path: " . $dbConfigPath . "\n";
echo "Full path: " . realpath($dbConfigPath) . "\n";
echo "File exists: " . (file_exists($dbConfigPath) ? 'YES' : 'NO') . "\n\n";

if (file_exists($dbConfigPath)) {
    echo "Test 4: Attempting to require db_config.php\n";
    try {
        require_once $dbConfigPath;
        echo "Successfully loaded db_config.php\n";
        
        echo "Test 5: Testing getDBConnection()\n";
        $conn = getDBConnection();
        echo "Connection successful\n";
        echo "Connection type: " . gettype($conn) . "\n";
        
        // Test query
        $result = $conn->query("SELECT 1");
        echo "Query test successful\n";
        $conn->close();
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "ERROR: db_config.php not found!\n";
    echo "Checked at: " . getcwd() . '/' . $dbConfigPath . "\n";
}

echo "\nTest 6: Session test\n";
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Session user_type: " . ($_SESSION['user_type'] ?? 'NOT SET') . "\n";
?>