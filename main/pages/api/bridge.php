<?php
// Bridge for PHP/MySQL sessions
// Provides user information based on MySQL session authentication

// Disable error display and log errors instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../db_config.php';

// Configure session cookie to be accessible
ini_set('session.cookie_httponly', '0');
ini_set('session.cookie_samesite', 'Lax');

header('Content-Type: application/json');

// For credentials to work, we need to specify the exact origin, not *
$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Start output buffering to catch any unwanted output
    if (ob_get_level() == 0) {
        ob_start();
    }
    
    $conn = getDBConnection();
    if (!$conn || $conn->connect_error) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }
    
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    // Firebase authentication removed - using MySQL sessions only
    // session_login action removed - use api/auth.php?action=login instead

    if ($action === 'session_login' && $method === 'POST') {
        // This action is deprecated - use api/auth.php?action=login instead
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Firebase authentication removed. Use api/auth.php?action=login instead.']);
        exit;
    }

    if ($action === 'get_user' && $method === 'GET') {
        if (!isset($_SESSION['user_id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            exit;
        }
        
        // Get user info from database if session doesn't have user_type
        $userType = $_SESSION['user_type'] ?? null;
        $userName = $_SESSION['name'] ?? null;
        
        if (!$userType) {
            // Try to get from database
            try {
                $stmt = $conn->prepare("SELECT user_type, name FROM users WHERE user_id = ?");
                $stmt->bind_param('i', $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $userRow = $result->fetch_assoc();
                    $userType = $userRow['user_type'];
                    $userName = $userRow['name'];
                    // Update session
                    $_SESSION['user_type'] = $userType;
                    $_SESSION['name'] = $userName;
                }
            } catch (Exception $e) {
                error_log('Error getting user type from database: ' . $e->getMessage());
            }
        }
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'user' => [
                'userId' => (int)$_SESSION['user_id'],
                'email' => $_SESSION['email'] ?? '',
                'userType' => $userType ?? '',
                'name' => $userName ?? ''
            ],
            'business' => isset($_SESSION['business_id']) ? [
                'businessId' => (int)$_SESSION['business_id']
            ] : null
        ]);
        exit;
    }

    // Firebase-related actions removed - use MySQL sessions only
    if ($action === 'get_user_by_firebase_uid' && $method === 'GET') {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Firebase authentication removed. Use api/auth.php?action=check and api/bridge.php?action=get_user instead.']);
        exit;
    }

    if ($action === 'logout' && $method === 'POST') {
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);

} catch (Exception $e) {
    // Clear any output before sending error
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
    error_log('Bridge error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    
    // Return more detailed error for debugging (remove in production)
    $errorMessage = $e->getMessage();
    if ($action === 'session_login') {
        $errorMessage .= ' (File: ' . $e->getFile() . ', Line: ' . $e->getLine() . ')';
    }
    
    echo json_encode([
        'success' => false, 
        'error' => $errorMessage,
        'action' => $action ?? 'unknown'
    ]);
} catch (Error $e) {
    // Catch fatal errors too
    if (ob_get_level() > 0) {
        ob_clean();
    }
    error_log('Bridge fatal error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage(),
        'action' => $action ?? 'unknown'
    ]);
} finally {
    // End output buffering if it was started
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    
    if (isset($conn) && $conn && !$conn->connect_error) {
        $conn->close();
    }
}