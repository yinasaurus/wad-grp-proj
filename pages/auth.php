<?php
session_start();
require_once '../../db_config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $conn = getDBConnection();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    // REGISTER
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

        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Email already registered']);
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, user_type, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $passwordHash, $userType, $phone);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create user account');
            }
            
            $userId = $conn->insert_id;

            // If business, create business profile in merged businesses table
            if ($userType === 'business' && !empty($businessName)) {
                $stmt = $conn->prepare("INSERT INTO businesses (user_id, name, category, description, phone, email, address, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssss", $userId, $businessName, $category, $description, $phone, $email, $address, $address);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to create business profile');
                }
                
                $businessId = $conn->insert_id;
            }

            $conn->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Registration successful',
                'userId' => $userId,
                'businessId' => $businessId ?? null
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
        exit;
    }

    // LOGIN
    if ($action === 'login' && $method === 'POST') {
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password required']);
            exit;
        }

        // Join with businesses table to get business info if applicable
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

        // Update last login
        $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $updateStmt->bind_param("i", $user['user_id']);
        $updateStmt->execute();

        // Set session
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

        // Add business info if business user
        if ($user['user_type'] === 'business' && $user['business_id']) {
            $response['business'] = [
                'businessId' => $user['business_id'],
                'businessName' => $user['business_name'],
                'verified' => (bool)$user['verified']
            ];
        }

        echo json_encode($response);
        exit;
    }

    // LOGOUT
    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out']);
        exit;
    }

    // CHECK SESSION
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

    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>