<?php
/**
 * auth.php
 * API endpoint for user authentication
 * Handles: registration, login, logout, session checking
 */

// Set error handling first
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering immediately to catch any output
ob_start();

session_start();

// Set JSON header immediately to prevent HTML output
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit;
}

// Clear any output before requiring db_config
ob_clean();

require_once '../../db_config.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $conn = getDBConnection();
    
    if (!$conn || $conn->connect_error) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection failed', 'message' => $conn ? $conn->connect_error : 'Connection object is null']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    
    if ($action === 'register' && $method === 'POST') {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $userType = $data['userType'] ?? 'consumer';
        $phone = $data['phone'] ?? '';
        $businessName = $data['businessName'] ?? '';
        $category = $data['category'] ?? '';
        $description = $data['description'] ?? '';
        $address = $data['address'] ?? '';
        $certificationName = $data['certificationName'] ?? '';
        $certificateNumber = $data['certificateNumber'] ?? '';
        if (empty($name) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Name, email, password required']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email']);
            exit;
        }
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Email already registered']);
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, user_type, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $passwordHash, $userType, $phone);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create user account');
            }
            
            $userId = $conn->insert_id;

            if ($userType === 'business' && !empty($businessName)) {
                $stmt = $conn->prepare("INSERT INTO businesses (user_id, name, category, description, phone, email, address, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssss", $userId, $businessName, $category, $description, $phone, $email, $address, $address);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to create business profile');
                }
                
                $businessId = $conn->insert_id;
                if (!empty($certificationName) && !empty($certificateNumber)) {
                    $certStmt = $conn->prepare("INSERT INTO certifications (business_id, certification_name, certificate_number) VALUES (?, ?, ?)");
                    if (!$certStmt) {
                        error_log('Failed to prepare certification insert statement: ' . $conn->error);
                    } else {
                        $certStmt->bind_param("iss", $businessId, $certificationName, $certificateNumber);
                        
                        if (!$certStmt->execute()) {
                            error_log('Failed to save certification during registration: ' . $certStmt->error);
                            error_log('Business ID: ' . $businessId . ', Certification: ' . $certificationName . ', Number: ' . $certificateNumber);
                            $conn->rollback();
                            throw new Exception('Failed to save certification');
                        } else {
                            error_log('Successfully saved certification during registration. Business ID: ' . $businessId . ', Certification: ' . $certificationName);
                        }
                        $certStmt->close();
                    }
                }
            }

            $conn->commit();

            $_SESSION['user_id'] = $userId;
            $_SESSION['user_type'] = $userType;
            $_SESSION['email'] = $email;
            if ($userType === 'business' && isset($businessId)) {
                $_SESSION['business_id'] = $businessId;
            }

            $userData = [
                'userId' => $userId,
                'name' => $name,
                'email' => $email,
                'userType' => $userType
            ];
            
            $response = [
                'success' => true, 
                'message' => 'Registration successful',
                'user' => $userData
            ];
            
            // Add business data if business user
            if ($userType === 'business' && isset($businessId)) {
                $userData['businessId'] = $businessId;
                
                // Get business details for response
                $bizStmt = $conn->prepare("SELECT id, name, category, email, verified FROM businesses WHERE id = ?");
                $bizStmt->bind_param("i", $businessId);
                $bizStmt->execute();
                $businessData = $bizStmt->get_result()->fetch_assoc();
                $bizStmt->close();
                
                if ($businessData) {
                    $response['business'] = [
                        'businessId' => (int)$businessData['id'],
                        'businessName' => $businessData['name'],
                        'verified' => (bool)$businessData['verified']
                    ];
                }
            }
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
        exit;
    }

    if ($action === 'login' && $method === 'POST') {
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password required']);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT u.user_id, u.name, u.email, u.password_hash, u.user_type,
                   b.id as business_id, b.name as business_name, b.verified
            FROM users u
            LEFT JOIN businesses b ON u.user_id = b.user_id
            WHERE u.email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            exit;
        }

        $user = $result->fetch_assoc();

        if (!password_verify($password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            exit;
        }

        $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $updateStmt->bind_param("i", $user['user_id']);
        $updateStmt->execute();

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['email'] = $user['email'];
        
        if ($user['user_type'] === 'business' && $user['business_id']) {
            $_SESSION['business_id'] = $user['business_id'];
        }

        $response = [
            'success' => true,
            'user' => [
                'userId' => $user['user_id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'userType' => $user['user_type']
            ]
        ];

        if ($user['user_type'] === 'business' && $user['business_id']) {
            $response['user']['businessId'] = $user['business_id'];
            $response['business'] = [
                'businessId' => $user['business_id'],
                'businessName' => $user['business_name'],
                'verified' => (bool)$user['verified']
            ];
        }

        echo json_encode($response);
        exit;
    }

    if ($action === 'logout' && $method === 'POST') {
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out']);
        exit;
    }

    if ($action === 'check' && $method === 'GET') {
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'loggedIn' => true,
                'userId' => $_SESSION['user_id'],
                'userType' => $_SESSION['user_type'],
                'businessId' => $_SESSION['business_id'] ?? null
            ]);
        } else {
            echo json_encode(['loggedIn' => false]);
        }
        exit;
    }

    if ($action === 'change_password' && $method === 'POST') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        $currentPassword = $data['currentPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';
        $confirmPassword = $data['confirmPassword'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'All password fields are required']);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'New password and confirm password do not match']);
            exit;
        }

        if (strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters long']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        if (!password_verify($currentPassword, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
            exit;
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $updateStmt->bind_param("si", $newPasswordHash, $userId);

        if ($updateStmt->execute()) {
            $updateStmt->close();
            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
        } else {
            $updateStmt->close();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to update password']);
        }
        exit;
    }

    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);

} catch (Exception $e) {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    error_log('Auth error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
} catch (Error $e) {
    // Catch fatal errors too
    if (ob_get_level() > 0) {
        ob_clean();
    }
    error_log('Auth fatal error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Fatal error: ' . $e->getMessage()]);
} finally {
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    
    if (isset($conn) && $conn && !$conn->connect_error) {
        $conn->close();
    }
}
?>