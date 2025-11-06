<?php
// Bridge for PHP/MySQL sessions
// Provides user information based on MySQL session authentication

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../db_config.php';

ini_set('session.cookie_httponly', '0');
ini_set('session.cookie_samesite', 'Lax');

header('Content-Type: application/json');

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

    if ($action === 'session_login' && $method === 'POST') {
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
        
        $userType = $_SESSION['user_type'] ?? null;
        $userName = $_SESSION['name'] ?? null;
        $userBio = null;
        $userPhone = null;
        $userLocation = null;
        
        try {
            $stmt = $conn->prepare("SELECT user_type, name, phone, location, bio FROM users WHERE user_id = ?");
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $userRow = $result->fetch_assoc();
                $userType = $userRow['user_type'] ?? $userType;
                $userName = $userRow['name'] ?? $userName;
                $userBio = $userRow['bio'] ?? null;
                $userPhone = $userRow['phone'] ?? null;
                $userLocation = $userRow['location'] ?? null;
                $_SESSION['user_type'] = $userType;
                $_SESSION['name'] = $userName;
            }
        } catch (Exception $e) {
            error_log('Error getting user details from database: ' . $e->getMessage());
        }
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'user' => [
                'userId' => (int)$_SESSION['user_id'],
                'email' => $_SESSION['email'] ?? '',
                'userType' => $userType ?? '',
                'name' => $userName ?? '',
                'phone' => $userPhone ?? null,
                'location' => $userLocation ?? null,
                'bio' => $userBio ?? null
            ],
            'business' => isset($_SESSION['business_id']) ? [
                'businessId' => (int)$_SESSION['business_id']
            ] : null
        ]);
        exit;
    }
    
    if ($action === 'update_user_profile' && $method === 'POST') {
        if (!isset($_SESSION['user_id'])) {
            ob_clean();
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $userType = $_SESSION['user_type'] ?? null;
        $name = $body['name'] ?? '';
        $phone = $body['phone'] ?? null;
        $location = $body['location'] ?? null;
        $bio = $body['bio'] ?? null;
        
        if ($userType === 'business') {
            $bio = null;
        }
        
        if (empty($name)) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Name is required']);
            exit;
        }
        
        try {
            $checkBio = $conn->query("SHOW COLUMNS FROM users LIKE 'bio'");
            $hasBio = $checkBio->num_rows > 0;
            
            if ($hasBio && $userType !== 'business') {
                $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, location = ?, bio = ? WHERE user_id = ?");
                $stmt->bind_param("ssssi", $name, $phone, $location, $bio, $userId);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, location = ? WHERE user_id = ?");
                $stmt->bind_param("sssi", $name, $phone, $location, $userId);
            }
            
            if ($stmt->execute()) {
                $_SESSION['name'] = $name;
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                ob_clean();
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to update profile']);
            }
            $stmt->close();
        } catch (Exception $e) {
            ob_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error updating profile: ' . $e->getMessage()]);
        }
        exit;
    }

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
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    
    if (isset($conn) && $conn && !$conn->connect_error) {
        $conn->close();
    }
}