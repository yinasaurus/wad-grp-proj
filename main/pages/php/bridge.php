<?php
// Bridge between Firebase Authentication and PHP/MySQL sessions
// Establishes server-side session using Firebase ID token

// Disable error display and log errors instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../db_config.php';
require_once 'firebase_auth.php';

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

    // Use the same API key as the client config
    $FIREBASE_API_KEY = 'AIzaSyCQnmAbyT_e8QzlGlI-9q00Rb74nWg81u0';

    if ($action === 'session_login' && $method === 'POST') {
        // Clear any output before starting
        ob_clean();
        
        $idToken = $body['idToken'] ?? '';
        if (!$idToken) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing ID token']);
            exit;
        }

        try {
        // Verify ID token and get Firebase user info
        $firebaseUser = verifyFirebaseIdToken($idToken, $FIREBASE_API_KEY);
        } catch (Exception $e) {
            error_log('Firebase token verification error: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Token verification failed: ' . $e->getMessage()]);
            exit;
        }

        $uid = $firebaseUser['uid'];
        $email = $firebaseUser['email'];
        $displayName = $firebaseUser['displayName'] ?? null;

        if (!$email) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email not available from Firebase']);
            exit;
        }

        // FIRST: Check if this is a business user in MySQL
        try {
            $stmt = $conn->prepare("SELECT id, name, email, user_id, category, description, phone FROM businesses WHERE email = ?");
            if (!$stmt) {
                throw new Exception('Failed to prepare business query: ' . $conn->error);
            }
        $stmt->bind_param('s', $email);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute business query: ' . $stmt->error);
            }
        $businessResult = $stmt->get_result();
        } catch (Exception $e) {
            error_log('Business query error: ' . $e->getMessage());
            // Continue to check regular users if business query fails
            $businessResult = false;
        }

        // IMPORTANT: Before processing business flow, check if user already exists as a consumer
        // If they're a consumer, they should NOT go through business flow - skip it entirely
        $existingUserCheck = null;
        try {
            $stmtCheck = $conn->prepare("SELECT user_id, user_type FROM users WHERE email = ?");
            if ($stmtCheck) {
                $stmtCheck->bind_param('s', $email);
                if ($stmtCheck->execute()) {
                    $checkResult = $stmtCheck->get_result();
                    if ($checkResult->num_rows > 0) {
                        $existingUserCheck = $checkResult->fetch_assoc();
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Error checking existing user: ' . $e->getMessage());
        }

        // If user exists and is a consumer, skip business flow entirely
        if ($existingUserCheck && $existingUserCheck['user_type'] === 'consumer') {
            // This is a consumer - skip business flow and go directly to consumer flow
            $businessResult = false; // Force skip business flow
        }

        // If business doesn't exist in MySQL, try to create it (first-time login after Firestore registration)
        if ($businessResult !== false && $businessResult->num_rows === 0 && (!$existingUserCheck || $existingUserCheck['user_type'] !== 'consumer')) {
            // This might be a business that registered via Firestore but doesn't exist in MySQL yet
            // Try to get business name from displayName or email
            $businessName = $displayName ?: explode('@', $email)[0];
            
            // Create business record in MySQL with basic info
            try {
                $stmtCreate = $conn->prepare("INSERT INTO businesses (name, email, category, description, phone) VALUES (?, ?, '', '', '')");
                if ($stmtCreate) {
                    $stmtCreate->bind_param('ss', $businessName, $email);
                    if ($stmtCreate->execute()) {
                        $newBusinessId = $conn->insert_id;
                        
                        // Re-query to get the created business
                        $stmt = $conn->prepare("SELECT id, name, email, user_id, category, description, phone FROM businesses WHERE id = ?");
                        if ($stmt) {
                            $stmt->bind_param('i', $newBusinessId);
                            if ($stmt->execute()) {
                                $businessResult = $stmt->get_result();
                                error_log("Created new business record in MySQL for: $email (ID: $newBusinessId)");
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('Error creating business record: ' . $e->getMessage());
                // Continue - business doesn't exist, treat as regular user
                $businessResult = false;
            }
        }

        if ($businessResult && $businessResult->num_rows > 0) {
            // This is a BUSINESS user
            $businessRow = $businessResult->fetch_assoc();
            $businessId = $businessRow['id'];
            $businessName = $businessRow['name'];
            $linkedUserId = $businessRow['user_id'];

            // Check if linked user exists
            if ($linkedUserId) {
                $stmt2 = $conn->prepare("SELECT user_id, name, email, user_type FROM users WHERE user_id = ?");
                $stmt2->bind_param('i', $linkedUserId);
                $stmt2->execute();
                $userResult = $stmt2->get_result();
                
                if ($userResult->num_rows > 0) {
                    $userRow = $userResult->fetch_assoc();
                } else {
                    // Linked user missing, create it
                    $linkedUserId = null;
                }
            }

            // Create user record if needed
            if (!$linkedUserId) {
                // First check if user already exists with this email
                $stmtCheckUser = $conn->prepare("SELECT user_id, name, email, user_type FROM users WHERE email = ?");
                $stmtCheckUser->bind_param('s', $email);
                $stmtCheckUser->execute();
                $userCheckResult = $stmtCheckUser->get_result();
                
                if ($userCheckResult->num_rows > 0) {
                    // User already exists, use it
                    $existingUser = $userCheckResult->fetch_assoc();
                    $linkedUserId = $existingUser['user_id'];
                    
                    // IMPORTANT: Only update user_type to business if they're not already a consumer
                    // Consumers should NOT be converted to business - they must use separate accounts
                    if ($existingUser['user_type'] === 'consumer') {
                        // Consumer account exists - don't convert to business
                        // This means they logged in with the wrong account type
                        // Continue as business but keep their consumer status
                        error_log("Warning: Business record exists for consumer email: $email (user_id: $linkedUserId). Not converting user_type.");
                        // Use existing user_type (consumer) - don't change it
                        $userRow = $existingUser;
                    } else if ($existingUser['user_type'] !== 'business') {
                        // User exists but type is not set or is something else - convert to business
                        $stmtUpdateUser = $conn->prepare("UPDATE users SET user_type = 'business' WHERE user_id = ?");
                        $stmtUpdateUser->bind_param('i', $linkedUserId);
                        $stmtUpdateUser->execute();
                        $existingUser['user_type'] = 'business';
                        $userRow = $existingUser;
                    } else {
                        // Already a business user
                        $userRow = $existingUser;
                    }
                    
                    // Link business to user
                    $stmtLink = $conn->prepare("UPDATE businesses SET user_id = ? WHERE id = ?");
                    $stmtLink->bind_param('ii', $linkedUserId, $businessId);
                    $stmtLink->execute();
                } else {
                    // User doesn't exist, create it
                $userType = 'business';
                $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $stmtCreate = $conn->prepare("INSERT INTO users (name, email, password_hash, user_type) VALUES (?, ?, ?, ?)");
                $stmtCreate->bind_param('ssss', $businessName, $email, $passwordHash, $userType);
                if (!$stmtCreate->execute()) {
                        $errorMsg = $stmtCreate->error;
                        error_log('Failed to create user record: ' . $errorMsg);
                        throw new Exception('Failed to create user record for business: ' . $errorMsg);
                }
                $linkedUserId = $conn->insert_id;

                // Link business to user
                $stmtLink = $conn->prepare("UPDATE businesses SET user_id = ? WHERE id = ?");
                $stmtLink->bind_param('ii', $linkedUserId, $businessId);
                $stmtLink->execute();

                $userRow = [
                    'user_id' => $linkedUserId,
                    'name' => $businessName,
                    'email' => $email,
                    'user_type' => 'business'
                ];
                }
            }

            // Set session for BUSINESS (but use actual user_type from database)
            $_SESSION['user_id'] = (int)$userRow['user_id'];
            $_SESSION['user_type'] = $userRow['user_type']; // Use actual user_type from database
            $_SESSION['email'] = $email;
            $_SESSION['business_id'] = (int)$businessId;
            $_SESSION['name'] = $businessName; // Store name in session too

            // Ensure session is written
            session_regenerate_id(true); // Regenerate session ID for security

            // Clear any output before sending JSON
            ob_clean();
            echo json_encode([
                'success' => true,
                'user' => [
                    'userId' => (int)$userRow['user_id'],
                    'name' => $businessName,
                    'email' => $email,
                    'userType' => $userRow['user_type'], // Use actual user_type from database
                ],
                'business' => [
                    'businessId' => (int)$businessId,
                    'businessName' => $businessName
                ]
            ]);
            exit;
        }

        // SECOND: Check if this is a regular consumer user
        try {
        $stmt = $conn->prepare("SELECT user_id, name, email, user_type FROM users WHERE email = ?");
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $conn->error);
            }
        $stmt->bind_param('s', $email);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute query: ' . $stmt->error);
            }
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Create a new consumer user record
            $name = $displayName ?: explode('@', $email)[0];
                $userType = 'consumer'; // Fix: use 'consumer' instead of 'user'
            $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $stmtCreate = $conn->prepare("INSERT INTO users (name, email, password_hash, user_type) VALUES (?, ?, ?, ?)");
                if (!$stmtCreate) {
                    throw new Exception('Failed to prepare insert statement: ' . $conn->error);
                }
            $stmtCreate->bind_param('ssss', $name, $email, $passwordHash, $userType);
            if (!$stmtCreate->execute()) {
                    throw new Exception('Failed to create user: ' . $stmtCreate->error);
            }
            $userId = $conn->insert_id;
            $userRow = [
                'user_id' => $userId,
                'name' => $name,
                'email' => $email,
                'user_type' => $userType
            ];
        } else {
            $userRow = $result->fetch_assoc();
            }
        } catch (Exception $e) {
            error_log('Database error in session_login: ' . $e->getMessage());
            throw $e;
        }

        // Set session for CONSUMER
        $_SESSION['user_id'] = (int)$userRow['user_id'];
        $_SESSION['user_type'] = $userRow['user_type'];
        $_SESSION['email'] = $userRow['email'];
        unset($_SESSION['business_id']);

        // Clear any output before sending JSON
        ob_clean();
        echo json_encode([
            'success' => true,
            'user' => [
                'userId' => (int)$userRow['user_id'],
                'name' => $userRow['name'],
                'email' => $userRow['email'],
                'userType' => $userRow['user_type'],
            ],
            'business' => null
        ]);
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

    if ($action === 'get_user_by_firebase_uid' && $method === 'GET') {
        $idToken = $_GET['idToken'] ?? '';
        $firebaseUid = $_GET['firebase_uid'] ?? '';
        
        // If we have a session, return that first
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => true,
                'user' => [
                    'userId' => (int)$_SESSION['user_id'],
                    'email' => $_SESSION['email'],
                    'userType' => $_SESSION['user_type']
                ],
                'business' => isset($_SESSION['business_id']) ? [
                    'businessId' => (int)$_SESSION['business_id']
                ] : null
            ]);
            exit;
        }

        // If we have an ID token, verify it and look up user
        if ($idToken) {
            try {
                $firebaseUser = verifyFirebaseIdToken($idToken, $FIREBASE_API_KEY);
                $email = $firebaseUser['email'];
                
                if (!$email) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Email not available from Firebase']);
                    exit;
                }

                // Check businesses table first
                $stmt = $conn->prepare("SELECT id, name, email, user_id FROM businesses WHERE email = ?");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $businessResult = $stmt->get_result();
                
                if ($businessResult->num_rows > 0) {
                    $businessRow = $businessResult->fetch_assoc();
                    $userId = $businessRow['user_id'];
                    if (!$userId) {
                        // Create user record
                        $userType = 'business';
                        $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                        $stmtCreate = $conn->prepare("INSERT INTO users (name, email, password_hash, user_type) VALUES (?, ?, ?, ?)");
                        $stmtCreate->bind_param('ssss', $businessRow['name'], $email, $passwordHash, $userType);
                        $stmtCreate->execute();
                        $userId = $conn->insert_id;
                        
                        $stmtLink = $conn->prepare("UPDATE businesses SET user_id = ? WHERE id = ?");
                        $stmtLink->bind_param('ii', $userId, $businessRow['id']);
                        $stmtLink->execute();
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'user' => [
                            'userId' => (int)$userId,
                            'email' => $email,
                            'userType' => 'business'
                        ],
                        'business' => [
                            'businessId' => (int)$businessRow['id']
                        ]
                    ]);
                    exit;
                }
                
                // Check users table
                $stmt = $conn->prepare("SELECT user_id, name, email, user_type FROM users WHERE email = ?");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $userRow = $result->fetch_assoc();
                    echo json_encode([
                        'success' => true,
                        'user' => [
                            'userId' => (int)$userRow['user_id'],
                            'email' => $userRow['email'],
                            'userType' => $userRow['user_type']
                        ],
                        'business' => null
                    ]);
                    exit;
                }
            } catch (Exception $e) {
                error_log('Error verifying token: ' . $e->getMessage());
            }
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User not found. Please log in first.']);
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