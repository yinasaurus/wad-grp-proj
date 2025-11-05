<?php
/**
 * data.php
 * Main API endpoint for business data operations
 * Handles: business CRUD, favorites, carbon offset tracking
 */

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
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    $conn = getDBConnection();
    
    // ============================================
// USER OPERATIONS (for messaging)
// ============================================

// REGISTER CONSUMER (customer registration)
if ($action === 'register_consumer' && $method === 'POST') {
    // Start output buffering
    ob_start();
    
    try {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Name, email, and password are required']);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid email address']);
            exit;
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        $stmt->bind_param("s", $email);
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute query: ' . $stmt->error);
        }
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email already registered']);
            exit;
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert consumer user
        $userType = 'consumer';
        $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, user_type) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        $stmt->bind_param("ssss", $name, $email, $passwordHash, $userType);
        if (!$stmt->execute()) {
            throw new Exception('Failed to create user: ' . $stmt->error);
        }
        
        $userId = $conn->insert_id;
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Consumer registered successfully',
            'userId' => $userId
        ]);
        exit;
        
    } catch (Exception $e) {
        ob_clean();
        error_log('register_consumer error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()]);
        exit;
    }
}

// GET USER NAME BY USER_ID (for messaging system)
if ($action === 'get_user_name' && $method === 'GET') {
    $userId = $_GET['user_id'] ?? '';
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing user_id']);
        exit;
    }
    
    // Check if it's a regular user
    $stmt = $conn->prepare("SELECT name, user_type FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // If it's a business user, try to get the business name
        if ($user['user_type'] === 'business') {
            $bizStmt = $conn->prepare("SELECT name FROM businesses WHERE user_id = ?");
            $bizStmt->bind_param("i", $userId);
            $bizStmt->execute();
            $bizResult = $bizStmt->get_result();
            
            if ($bizResult->num_rows > 0) {
                $business = $bizResult->fetch_assoc();
                echo json_encode(['success' => true, 'name' => $business['name']]);
                exit;
            }
        }
        
        // Return user name
        echo json_encode(['success' => true, 'name' => $user['name']]);
        exit;
    }
    
    // If not found in users table, try businesses table directly
    $stmt = $conn->prepare("SELECT name FROM businesses WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $business = $result->fetch_assoc();
        echo json_encode(['success' => true, 'name' => $business['name']]);
        exit;
    }
    
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// GET USER NAMES BATCH (for messaging - optimized)
if ($action === 'get_user_names_batch' && $method === 'POST') {
    ob_start();
    try {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }
        
        $userIds = $data['user_ids'] ?? [];
        
        if (empty($userIds) || !is_array($userIds)) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Missing or invalid user_ids']);
            exit;
        }
        
        // Convert to integers and filter out invalid values
        $userIds = array_map('intval', array_filter($userIds, function($id) {
            return is_numeric($id) && intval($id) > 0;
        }));
        
        if (empty($userIds)) {
            ob_clean();
            echo json_encode(['success' => true, 'names' => []]);
            exit;
        }
        
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        
        // Get user names from users table
        $stmt = $conn->prepare("SELECT user_id, name, user_type FROM users WHERE user_id IN ($placeholders)");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        $stmt->bind_param(str_repeat('i', count($userIds)), ...$userIds);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $names = [];
        $businessUserIds = [];
        
        while ($row = $result->fetch_assoc()) {
            $userId = (string)$row['user_id'];
            if ($row['user_type'] === 'business') {
                // For business users, we'll get business name
                $businessUserIds[] = $row['user_id'];
                // Temporary: use user name, will be replaced with business name
                $names[$userId] = $row['name'];
            } else {
                $names[$userId] = $row['name'];
            }
        }
        
        // Get business names for business users
        if (!empty($businessUserIds)) {
            $bizPlaceholders = implode(',', array_fill(0, count($businessUserIds), '?'));
            $bizStmt = $conn->prepare("SELECT user_id, name FROM businesses WHERE user_id IN ($bizPlaceholders)");
            if ($bizStmt) {
                $bizStmt->bind_param(str_repeat('i', count($businessUserIds)), ...$businessUserIds);
                $bizStmt->execute();
                $bizResult = $bizStmt->get_result();
                while ($bizRow = $bizResult->fetch_assoc()) {
                    $userId = (string)$bizRow['user_id'];
                    $names[$userId] = $bizRow['name'];
                }
            }
        }
        
        ob_clean();
        echo json_encode(['success' => true, 'names' => $names]);
        exit;
    } catch (Exception $e) {
        error_log('get_user_names_batch error: ' . $e->getMessage());
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
        exit;
    } catch (Error $e) {
        error_log('get_user_names_batch fatal error: ' . $e->getMessage());
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error']);
        exit;
    } finally {
        ob_end_flush();
    }
}

// REGISTER BUSINESS (sync from Firestore registration to MySQL)
if ($action === 'register_business' && $method === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';
        $category = $data['category'] ?? '';
        $description = $data['description'] ?? '';
        
        if (empty($name) || empty($email)) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Name and email required']);
            exit;
        }
        
        // Check if business already exists
        $stmt = $conn->prepare("SELECT id FROM businesses WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Business exists, update it with registration data
            $business = $result->fetch_assoc();
            $businessId = $business['id'];
            
            $stmtUpdate = $conn->prepare("UPDATE businesses SET name = ?, category = ?, description = ?, phone = ? WHERE id = ?");
            $stmtUpdate->bind_param('ssssi', $name, $category, $description, $phone, $businessId);
            
            if ($stmtUpdate->execute()) {
                ob_clean();
                echo json_encode(['success' => true, 'businessId' => $businessId, 'message' => 'Business updated']);
            } else {
                throw new Exception('Failed to update business: ' . $stmtUpdate->error);
            }
        } else {
            // Create new business record
            $stmtInsert = $conn->prepare("INSERT INTO businesses (name, email, category, description, phone) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->bind_param('sssss', $name, $email, $category, $description, $phone);
            
            if ($stmtInsert->execute()) {
                $businessId = $conn->insert_id;
                ob_clean();
                echo json_encode(['success' => true, 'businessId' => $businessId, 'message' => 'Business created']);
            } else {
                throw new Exception('Failed to create business: ' . $stmtInsert->error);
            }
        }
    } catch (Exception $e) {
        error_log('Register business error: ' . $e->getMessage());
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

// GET BUSINESS USER ID FROM BUSINESS ID (for messaging)
if ($action === 'get_business_user_id' && $method === 'GET') {
    // Start output buffering
    ob_start();
    
    try {
        $businessId = $_GET['business_id'] ?? '';
        
        if (!$businessId) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing business_id']);
            exit;
        }
        
        // Check database connection
        if (!$conn || $conn->connect_error) {
            throw new Exception('Database connection failed');
        }
        
        $stmt = $conn->prepare("SELECT user_id FROM businesses WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $businessId);
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute query: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $business = $result->fetch_assoc();
            $userId = $business['user_id'];
            
            // If business doesn't have a linked user_id, create one
            if (!$userId) {
                // Get business info
                $bizStmt = $conn->prepare("SELECT name, email FROM businesses WHERE id = ?");
                if (!$bizStmt) {
                    throw new Exception('Failed to prepare business statement: ' . $conn->error);
                }
                
                $bizStmt->bind_param("i", $businessId);
                if (!$bizStmt->execute()) {
                    throw new Exception('Failed to execute business query: ' . $bizStmt->error);
                }
                
                $bizResult = $bizStmt->get_result();
                
                if ($bizResult->num_rows > 0) {
                    $bizRow = $bizResult->fetch_assoc();
                    
                    // Ensure we have required fields - handle both NULL and empty string
                    $businessName = isset($bizRow['name']) && $bizRow['name'] !== null && trim($bizRow['name']) !== '' 
                        ? $bizRow['name'] 
                        : 'Business ' . $businessId;
                    
                    $businessEmail = isset($bizRow['email']) && $bizRow['email'] !== null && trim($bizRow['email']) !== '' 
                        ? $bizRow['email'] 
                        : 'business' . $businessId . '@example.com';
                    
                    // Final safety check - ensure email is never null or empty
                    if (empty($businessEmail) || is_null($businessEmail) || trim($businessEmail) === '') {
                        $businessEmail = 'business' . $businessId . '@example.com';
                    }
                    
                    // Trim values
                    $businessName = trim($businessName);
                    $businessEmail = trim($businessEmail);
                    
                    // Create user record
                    $userType = 'business';
                    $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                    $stmtCreate = $conn->prepare("INSERT INTO users (name, email, password_hash, user_type) VALUES (?, ?, ?, ?)");
                    if (!$stmtCreate) {
                        throw new Exception('Failed to prepare create user statement: ' . $conn->error);
                    }
                    
                    $stmtCreate->bind_param('ssss', $businessName, $businessEmail, $passwordHash, $userType);
                    if (!$stmtCreate->execute()) {
                        throw new Exception('Failed to create user: ' . $stmtCreate->error);
                    }
                    
                    $userId = $conn->insert_id;
                    
                    // Link business to user
                    $stmtLink = $conn->prepare("UPDATE businesses SET user_id = ? WHERE id = ?");
                    if (!$stmtLink) {
                        throw new Exception('Failed to prepare link statement: ' . $conn->error);
                    }
                    
                    $stmtLink->bind_param('ii', $userId, $businessId);
                    if (!$stmtLink->execute()) {
                        throw new Exception('Failed to link business: ' . $stmtLink->error);
                    }
                } else {
                    throw new Exception('Business info not found');
                }
            }
            
            ob_clean();
            echo json_encode(['success' => true, 'user_id' => (int)$userId]);
            exit;
        }
        
        ob_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Business not found']);
        exit;
        
    } catch (Exception $e) {
        error_log('get_business_user_id error: ' . $e->getMessage());
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
        exit;
    } catch (Error $e) {
        error_log('get_business_user_id fatal error: ' . $e->getMessage());
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error']);
        exit;
    } finally {
        ob_end_flush();
    }
}

// GET BUSINESS NAME (single business - for messaging)
if ($action === 'get_business_name' && $method === 'GET') {
    $businessId = intval($_GET['business_id'] ?? 0);
    
    if (!$businessId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing business_id']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT name FROM businesses WHERE id = ?");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['success' => true, 'name' => $row['name']]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Business not found']);
    }
    exit;
}

// GET BUSINESS NAMES BATCH (for messaging - optimized)
if ($action === 'get_business_names_batch' && $method === 'POST') {
    $businessIds = $data['business_ids'] ?? [];
    
    if (empty($businessIds) || !is_array($businessIds)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing or invalid business_ids']);
        exit;
    }
    
    // Convert to integers
    $businessIds = array_map('intval', $businessIds);
    $placeholders = implode(',', array_fill(0, count($businessIds), '?'));
    
    // Get business names
    $stmt = $conn->prepare("SELECT id, name FROM businesses WHERE id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($businessIds)), ...$businessIds);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $names = [];
    while ($row = $result->fetch_assoc()) {
        $names[(string)$row['id']] = $row['name'];
    }
    
    echo json_encode(['success' => true, 'names' => $names]);
    exit;
}

    // ============================================
    // BUSINESS OPERATIONS
    // ============================================

    // GET ALL BUSINESSES (for directory listing)
    if ($action === 'all_businesses' && $method === 'GET') {
        ob_start(); // Start output buffering
        try {
            error_log("Fetching all businesses for directory");
            // Show all businesses that have at least a name (not empty)
            // IMPORTANT: Only show businesses linked to users with user_type = 'business'
            // This prevents consumers from appearing in the directory
            // If business has no user_id, show it (might be unlinked business)
            // If business has user_id, only show if user_type = 'business'
            // Email is optional - removed requirement for email to show business
        $stmt = $conn->prepare("
            SELECT 
                b.id, 
                b.name, 
                b.category, 
                b.address, 
                b.email, 
                b.phone, 
                b.website, 
                b.description, 
                b.longDescription,
                b.sustainability_score, 
                b.verified, 
                b.lat, 
                b.lng
            FROM businesses b
            LEFT JOIN users u ON b.user_id = u.user_id
            WHERE b.name IS NOT NULL 
                AND b.name != ''
                AND (b.user_id IS NULL OR u.user_type = 'business' OR u.user_type IS NULL)
            ORDER BY b.name ASC
        ");
            
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $conn->error);
            }
            
            if (!$stmt->execute()) {
                error_log("Query execution failed: " . $stmt->error);
                throw new Exception('Failed to execute query: ' . $stmt->error);
            }
            
        $result = $stmt->get_result();
            if (!$result) {
                error_log("Failed to get result set");
                throw new Exception('Failed to get result: ' . $stmt->error);
            }
            
            error_log("Query executed successfully, found " . $result->num_rows . " businesses");
            
        $businesses = [];

        while ($row = $result->fetch_assoc()) {
                try {
            $certStmt = $conn->prepare("SELECT certification_name FROM certifications WHERE business_id = ?");
                    if ($certStmt) {
            $certStmt->bind_param("i", $row['id']);
            $certStmt->execute();
            $certResult = $certStmt->get_result();
            $certifications = [];
            while ($cert = $certResult->fetch_assoc()) {
                $certifications[] = $cert['certification_name'];
                        }
                        $certStmt->close();
                    }
                } catch (Exception $e) {
                    error_log('Error getting certifications for business ' . $row['id'] . ': ' . $e->getMessage());
                    $certifications = [];
            }

            $businesses[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                    'category' => $row['category'] ?? '',
                    'address' => $row['address'] ?? '',
                    'description' => $row['description'] ?? '',
                    'longDescription' => $row['longDescription'] ?? '',
                    'sustainabilityScore' => (int)($row['sustainability_score'] ?? 0),
                'certifications' => $certifications,
                    'phone' => $row['phone'] ?? '',
                    'email' => $row['email'] ?? '',
                    'website' => $row['website'] ?? '',
                    'verified' => (bool)($row['verified'] ?? false),
                'lat' => $row['lat'] ? (float)$row['lat'] : null,
                'lng' => $row['lng'] ? (float)$row['lng'] : null
            ];
        }

            error_log("Returning " . count($businesses) . " businesses to frontend");
            
            ob_clean();
        echo json_encode([
            'success' => true,
            'businesses' => $businesses
        ]);
        exit;
        } catch (Exception $e) {
            error_log('all_businesses error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            ob_clean();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load businesses: ' . $e->getMessage()
            ]);
            exit;
        } catch (Error $e) {
            error_log('all_businesses fatal error: ' . $e->getMessage());
            ob_clean();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load businesses'
            ]);
            exit;
        } finally {
            ob_end_flush();
        }
    }

    // GET SINGLE BUSINESS (for public profile viewing)
    if ($action === 'get_business' && $method === 'GET') {
        $businessId = $_GET['id'] ?? null;
        
        if (!$businessId) {
            http_response_code(400);
            echo json_encode(['error' => 'Business ID required']);
            exit;
        }
        
        $stmt = $conn->prepare("
            SELECT 
                b.id, 
                b.name, 
                b.category, 
                b.address, 
                b.email, 
                b.phone, 
                b.website, 
                b.description, 
                b.longDescription,
                b.sustainability_score, 
                b.verified, 
                b.lat, 
                b.lng
            FROM businesses b
            WHERE b.id = ?
        ");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Business not found']);
            exit;
        }
        
        $row = $result->fetch_assoc();
        
        // Get certifications
        $certStmt = $conn->prepare("SELECT certification_name FROM certifications WHERE business_id = ?");
        $certStmt->bind_param("i", $businessId);
        $certStmt->execute();
        $certResult = $certStmt->get_result();
        $certifications = [];
        while ($cert = $certResult->fetch_assoc()) {
            $certifications[] = $cert['certification_name'];
        }
        
        // Get practices
        $practStmt = $conn->prepare("SELECT practice_title as title, practice_description as description FROM business_practices WHERE business_id = ?");
        $practStmt->bind_param("i", $businessId);
        $practStmt->execute();
        $practResult = $practStmt->get_result();
        $practices = [];
        while ($pract = $practResult->fetch_assoc()) {
            $practices[] = $pract;
        }
        
        // Get products
        $prodStmt = $conn->prepare("SELECT pid as id, productname as name, descript as description, pvalue as price, available FROM greenpns WHERE bid = ?");
        $prodStmt->bind_param("i", $businessId);
        $prodStmt->execute();
        $prodResult = $prodStmt->get_result();
        $products = [];
        while ($prod = $prodResult->fetch_assoc()) {
            $products[] = [
                'name' => $prod['name'],
                'description' => $prod['description'],
                'price' => '$' . $prod['price'],
                'available' => (bool)$prod['available']
            ];
        }
        
        // Get locations
        $locStmt = $conn->prepare("SELECT location_name as name, address, operating_hours as hours FROM business_locations WHERE business_id = ?");
        $locStmt->bind_param("i", $businessId);
        $locStmt->execute();
        $locResult = $locStmt->get_result();
        $locations = [];
        while ($loc = $locResult->fetch_assoc()) {
            $locations[] = [
                'name' => $loc['name'],
                'address' => $loc['address'],
                'hours' => $loc['hours'],
                'mapLink' => '#'
            ];
        }
        
        // Get updates
        $updStmt = $conn->prepare("SELECT title, content as description, created_at as time FROM business_updates WHERE business_id = ? ORDER BY created_at DESC");
        $updStmt->bind_param("i", $businessId);
        $updStmt->execute();
        $updResult = $updStmt->get_result();
        $updates = [];
        while ($upd = $updResult->fetch_assoc()) {
            $updates[] = $upd;
        }
        
        echo json_encode([
            'success' => true,
            'business' => [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'category' => $row['category'],
                'address' => $row['address'],
                'description' => $row['description'],
                'longDescription' => $row['longDescription'],
                'sustainabilityScore' => (int)$row['sustainability_score'],
                'certifications' => $certifications,
                'phone' => $row['phone'],
                'email' => $row['email'],
                'website' => $row['website'],
                'verified' => (bool)$row['verified'],
                'lat' => $row['lat'] ? (float)$row['lat'] : null,
                'lng' => $row['lng'] ? (float)$row['lng'] : null,
                'ecoPractices' => $practices,
                'products' => $products,
                'locations' => $locations,
                'stats' => [
                    'carbonReduced' => '125',
                    'wasteRecycled' => '95%',
                    'energySaved' => '450 MWh',
                    'waterSaved' => '2.5M L'
                ],
                'fullCertifications' => array_map(function($cert) {
                    return [
                        'name' => $cert,
                        'issued' => '2023',
                        'expiry' => '2026'
                    ];
                }, $certifications),
                'contact' => [
                    'phone' => $row['phone'] ?: '+65 0000 0000',
                    'email' => $row['email'] ?: 'info@business.sg',
                    'website' => $row['website'] ?: 'www.business.sg'
                ]
            ]
        ]);
        exit;
    }

    // GET BUSINESS PROFILE (for partner dashboard)
    if ($action === 'business_profile' && $method === 'GET') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated as business']);
            exit;
        }

        $userId = $_SESSION['user_id'];

        $stmt = $conn->prepare("SELECT * FROM businesses WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $business = $stmt->get_result()->fetch_assoc();

        if (!$business) {
            http_response_code(404);
            echo json_encode(['error' => 'Business not found']);
            exit;
        }

        $businessId = $business['id'];

        // Get certifications (from 'certifications' table)
        $stmt = $conn->prepare("SELECT id as cert_id, certification_name as name, certificate_number as code, issue_date as issued, expiry_date as expiry FROM certifications WHERE business_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $result = $stmt->get_result();
        $certifications = [];
        while ($row = $result->fetch_assoc()) {
            // Ensure all fields are properly set, even if NULL
            $certifications[] = [
                'cert_id' => (int)$row['cert_id'],
                'name' => $row['name'] ?? '',
                'code' => $row['code'] ?? '',
                'issued' => $row['issued'] ?? null,
                'expiry' => $row['expiry'] ?? null
            ];
        }

        // Get locations
        $stmt = $conn->prepare("SELECT location_id, location_name as name, address, operating_hours as hours FROM business_locations WHERE business_id = ?");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $locations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get practices
        $stmt = $conn->prepare("SELECT practice_id, practice_title as title, practice_description as description FROM business_practices WHERE business_id = ?");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $practices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get products (from 'greenpns' table)
        $stmt = $conn->prepare("SELECT pid as product_id, productname as name, descript as description, pvalue as price, available FROM greenpns WHERE bid = ?");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get updates
        $stmt = $conn->prepare("SELECT update_id, title, content as description, created_at as time FROM business_updates WHERE business_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $updates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'business' => [
                'id' => $businessId, // Include business ID for public profile link
                'business_name' => $business['name'],
                'category' => $business['category'] ?? '',
                'description' => $business['description'] ?? '',
                'longDescription' => $business['longDescription'] ?? '',
                'phone' => $business['phone'] ?? '',
                'email' => $business['email'] ?? '',
                'website' => $business['website'] ?? '',
                'address' => $business['address'] ?? '',
                'lat' => $business['lat'] ?? null,
                'lng' => $business['lng'] ?? null
            ],
            'businessId' => $businessId, // Also include at top level for easy access
            'locations' => $locations,
            'certifications' => $certifications,
            'practices' => $practices,
            'products' => $products,
            'updates' => $updates,
            'stats' => [
                'certifications' => count($certifications),
                'locations' => count($locations),
                'profileViews' => 0,
                'sustainabilityScore' => $business['sustainability_score'] ?? 0
            ]
        ]);
        exit;
    }

    // UPDATE BUSINESS PROFILE
    if ($action === 'update_business_profile' && $method === 'POST') {
        ob_start(); // Start output buffering
        try {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
                ob_clean();
            http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        $userId = $_SESSION['user_id'];
            $data = json_decode(file_get_contents('php://input'), true) ?? [];

            // Handle optional fields
            $name = $data['name'] ?? '';
            $category = $data['category'] ?? '';
            $description = $data['description'] ?? '';
            $phone = $data['phone'] ?? '';
            $email = $data['email'] ?? '';
            $website = $data['website'] ?? '';
            $address = $data['address'] ?? '';
            
            // Handle lat/lng - can be null or empty string
            $lat = null;
            if (isset($data['lat']) && $data['lat'] !== '' && $data['lat'] !== null) {
                $lat = (float)$data['lat'];
                // Validate lat is between -90 and 90 (Singapore is around 1.35)
                if ($lat == 0 || $lat < -90 || $lat > 90) {
                    $lat = null;
                } else {
                    // Round to 6 decimal places for consistency (about 10cm precision)
                    $lat = round($lat, 6);
                }
            }
            
            $lng = null;
            if (isset($data['lng']) && $data['lng'] !== '' && $data['lng'] !== null) {
                $lng = (float)$data['lng'];
                // Validate lng is between -180 and 180 (Singapore is around 103.82)
                if ($lng == 0 || $lng < -180 || $lng > 180) {
                    $lng = null;
                } else {
                    // Round to 6 decimal places for consistency (about 10cm precision)
                    $lng = round($lng, 6);
                }
            }
            
            $longDescription = $data['long_description'] ?? $data['longDescription'] ?? '';

            // Ensure name is not empty (required for directory)
            if (empty($name) || trim($name) === '') {
                ob_clean();
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Business name is required']);
                exit;
            }

            // Build UPDATE query dynamically based on whether lat/lng are provided
            // For COALESCE to work with NULL, we need to pass the values differently
            if ($lat !== null && $lng !== null) {
                // Both lat and lng are provided - update them
                $stmt = $conn->prepare("UPDATE businesses SET name = ?, category = ?, description = ?, longDescription = ?, phone = ?, email = ?, website = ?, address = ?, lat = ?, lng = ? WHERE user_id = ?");
                if (!$stmt) {
                    throw new Exception('Failed to prepare statement: ' . $conn->error);
                }
                $stmt->bind_param("ssssssssddi", $name, $category, $description, $longDescription, $phone, $email, $website, $address, $lat, $lng, $userId);
        } else {
                // Lat or lng is null - update other fields, keep existing lat/lng
                $stmt = $conn->prepare("UPDATE businesses SET name = ?, category = ?, description = ?, longDescription = ?, phone = ?, email = ?, website = ?, address = ? WHERE user_id = ?");
                if (!$stmt) {
                    throw new Exception('Failed to prepare statement: ' . $conn->error);
                }
                $stmt->bind_param("ssssssssi", $name, $category, $description, $longDescription, $phone, $email, $website, $address, $userId);
            }

            if (!$stmt->execute()) {
                $errorMsg = $stmt->error ?: $conn->error;
                error_log('Update business profile error: ' . $errorMsg);
                ob_clean();
            http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to update profile: ' . $errorMsg]);
                exit;
        }

            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        exit;
        } catch (Exception $e) {
            error_log('Update business profile exception: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            ob_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
            exit;
        } catch (Error $e) {
            error_log('Update business profile fatal error: ' . $e->getMessage());
            ob_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Server error']);
            exit;
        } finally {
            ob_end_flush();
        }
    }

    // ADD LOCATION
    if ($action === 'add_location' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $biz = $stmt->get_result()->fetch_assoc();

        if (!$biz) {
            http_response_code(404);
            echo json_encode(['error' => 'Business not found']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO business_locations (business_id, location_name, address, operating_hours) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $biz['id'], $data['name'], $data['address'], $data['hours']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'locationId' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add location']);
        }
        exit;
    }

    // REMOVE LOCATION
    if ($action === 'remove_location' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM business_locations WHERE location_id = ?");
        $stmt->bind_param("i", $data['locationId']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove location']);
        }
        exit;
    }

    // ADD CERTIFICATION
    if ($action === 'add_certification' && $method === 'POST') {
        // Debug session
        error_log('Add cert - Session ID: ' . session_id());
        error_log('Add cert - user_id: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'));
        
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode([
                'error' => 'Not authenticated',
                'debug' => [
                    'session_id' => session_id(),
                    'has_user_id' => isset($_SESSION['user_id']),
                    'user_type' => $_SESSION['user_type'] ?? null
                ]
            ]);
            exit;
        }

        $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $biz = $stmt->get_result()->fetch_assoc();

        if (!$biz || !isset($biz['id'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Business not found. Please complete your business registration first.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO certifications (business_id, certification_name, certificate_number, issue_date, expiry_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $biz['id'], $data['name'], $data['code'], $data['issued'], $data['expiry']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'certId' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add certification: ' . $conn->error]);
        }
        exit;
    }

    // REMOVE CERTIFICATION
    if ($action === 'remove_certification' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM certifications WHERE id = ?");
        $stmt->bind_param("i", $data['certId']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove certification']);
        }
        exit;
    }

    // ADD PRACTICE
    if ($action === 'add_practice' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode([
                'error' => 'Not authenticated',
                'debug' => [
                    'session_id' => session_id(),
                    'has_user_id' => isset($_SESSION['user_id']),
                    'user_type' => $_SESSION['user_type'] ?? null
                ]
            ]);
            exit;
        }

        $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $biz = $stmt->get_result()->fetch_assoc();

        if (!$biz || !isset($biz['id'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Business not found. Please complete your business registration first.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO business_practices (business_id, practice_title, practice_description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $biz['id'], $data['title'], $data['description']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'practiceId' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add practice: ' . $conn->error]);
        }
        exit;
    }

    // REMOVE PRACTICE
    if ($action === 'remove_practice' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM business_practices WHERE practice_id = ?");
        $stmt->bind_param("i", $data['practiceId']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove practice']);
        }
        exit;
    }

    // ADD PRODUCT
    if ($action === 'add_product' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode([
                'error' => 'Not authenticated',
                'debug' => [
                    'session_id' => session_id(),
                    'has_user_id' => isset($_SESSION['user_id']),
                    'user_type' => $_SESSION['user_type'] ?? null,
                    'session_data' => $_SESSION
                ]
            ]);
            exit;
        }

        $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $biz = $stmt->get_result()->fetch_assoc();

        if (!$biz || !isset($biz['id'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Business not found. Please complete your business registration first.']);
            exit;
        }

        $available = isset($data['available']) && $data['available'] ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO greenpns (productname, descript, pvalue, bid, available) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $data['name'], $data['description'], $data['price'], $biz['id'], $available);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'productId' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add product: ' . $conn->error]);
        }
        exit;
    }

    // REMOVE PRODUCT
    if ($action === 'remove_product' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM greenpns WHERE pid = ?");
        $stmt->bind_param("i", $data['productId']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove product']);
        }
        exit;
    }

    // ADD UPDATE
    if ($action === 'add_update' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $biz = $stmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare("INSERT INTO business_updates (business_id, title, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $biz['id'], $data['title'], $data['description']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'updateId' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add update']);
        }
        exit;
    }

    // REMOVE UPDATE
    if ($action === 'remove_update' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM business_updates WHERE update_id = ?");
        $stmt->bind_param("i", $data['updateId']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove update']);
        }
        exit;
    }

    // ============================================
    // FAVORITES OPERATIONS
    // ============================================
    
    // SAVE BUSINESS TO FAVORITES
    if ($action === 'save_business' && $method === 'POST') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $businessId = $data['business_id'] ?? null;
        $userId = $_SESSION['user_id'];

        if (!$businessId) {
            http_response_code(400);
            echo json_encode(['error' => 'Business ID required']);
            exit;
        }

        // Check if already saved
        $stmt = $conn->prepare("SELECT id FROM saved_companies WHERE user_id = ? AND business_id = ?");
        $stmt->bind_param("ii", $userId, $businessId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Business already saved']);
            exit;
        }

        // Save to favorites
        $stmt = $conn->prepare("INSERT INTO saved_companies (user_id, business_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $businessId);
        
        if ($stmt->execute()) {
            // Auto-track carbon offset for saving a green business
            // Calculate offset based on business sustainability score (1 point = 0.5 kg CO2)
            $businessStmt = $conn->prepare("SELECT sustainability_score FROM businesses WHERE id = ?");
            $businessStmt->bind_param("i", $businessId);
            $businessStmt->execute();
            $businessResult = $businessStmt->get_result();
            
            if ($businessRow = $businessResult->fetch_assoc()) {
                $sustainabilityScore = (int)$businessRow['sustainability_score'];
                // Award 0.5 kg CO2 per sustainability score point (max 50 kg)
                $offsetAmount = min($sustainabilityScore * 0.5, 50);
                
                if ($offsetAmount > 0) {
                    // Record in user_interactions
                    $offsetStmt = $conn->prepare("
                        INSERT INTO user_interactions (user_id, business_id, interaction_type, co2_offset)
                        VALUES (?, ?, 'engagement', ?)
                    ");
                    $offsetStmt->bind_param("iid", $userId, $businessId, $offsetAmount);
                    $offsetStmt->execute();
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Business saved to favorites']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save business']);
        }
        exit;
    }

    // REMOVE BUSINESS FROM FAVORITES
    if ($action === 'remove_business' && $method === 'POST') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $businessId = $data['business_id'] ?? null;
        $userId = $_SESSION['user_id'];

        if (!$businessId) {
            http_response_code(400);
            echo json_encode(['error' => 'Business ID required']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM saved_companies WHERE user_id = ? AND business_id = ?");
        $stmt->bind_param("ii", $userId, $businessId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Business removed from favorites']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove business']);
        }
        exit;
    }

    // CHECK IF BUSINESS IS SAVED
    if ($action === 'check_saved' && $method === 'GET') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['saved' => false]);
            exit;
        }

        $businessId = $_GET['business_id'] ?? null;
        $userId = $_SESSION['user_id'];

        if (!$businessId) {
            echo json_encode(['saved' => false]);
            exit;
        }

        $stmt = $conn->prepare("SELECT id FROM saved_companies WHERE user_id = ? AND business_id = ?");
        $stmt->bind_param("ii", $userId, $businessId);
        $stmt->execute();
        $result = $stmt->get_result();

        echo json_encode(['saved' => $result->num_rows > 0]);
        exit;
    }

    // GET SAVED BUSINESSES
    if ($action === 'get_saved_businesses' && $method === 'GET') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $userId = $_SESSION['user_id'];

        $stmt = $conn->prepare("
            SELECT b.id, b.name, b.category, b.address, b.description, 
                   b.sustainability_score, sc.saved_at
            FROM saved_companies sc
            JOIN businesses b ON sc.business_id = b.id
            WHERE sc.user_id = ?
            ORDER BY sc.saved_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $savedBusinesses = [];
        while ($row = $result->fetch_assoc()) {
            // Get certifications
            $certStmt = $conn->prepare("SELECT certification_name FROM certifications WHERE business_id = ?");
            $certStmt->bind_param("i", $row['id']);
            $certStmt->execute();
            $certResult = $certStmt->get_result();
            $certifications = [];
            while ($cert = $certResult->fetch_assoc()) {
                $certifications[] = $cert['certification_name'];
            }

            $savedBusinesses[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'category' => $row['category'],
                'description' => $row['description'],
                'location' => $row['address'],
                'sustainabilityScore' => (int)$row['sustainability_score'],
                'savedDate' => $row['saved_at'],
                'categories' => [$row['category']],
                'topics' => $certifications
            ];
        }

        echo json_encode([
            'success' => true,
            'savedCompanies' => $savedBusinesses
        ]);
        exit;
    }

    // ============================================
    // CARBON OFFSET OPERATIONS
    // ============================================
    
    // GET CARBON OFFSET STATS
    if ($action === 'get_carbon_offset_stats' && $method === 'GET') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $userId = $_SESSION['user_id'];

        // Get total from user_carbon_offsets table
        $offsetStmt = $conn->prepare("
            SELECT COALESCE(SUM(amount_kg), 0) as total_offset
            FROM user_carbon_offsets
            WHERE user_id = ?
        ");
        $offsetStmt->bind_param("i", $userId);
        $offsetStmt->execute();
        $offsetResult = $offsetStmt->get_result();
        $offsetRow = $offsetResult->fetch_assoc();
        $totalOffset = (float)$offsetRow['total_offset'];

        // Also get from user_interactions table
        $interactionStmt = $conn->prepare("
            SELECT COALESCE(SUM(co2_offset), 0) as total_from_interactions
            FROM user_interactions
            WHERE user_id = ?
        ");
        $interactionStmt->bind_param("i", $userId);
        $interactionStmt->execute();
        $interactionResult = $interactionStmt->get_result();
        $interactionRow = $interactionResult->fetch_assoc();
        $totalFromInteractions = (float)$interactionRow['total_from_interactions'];

        // Combine both sources
        $totalOffset = $totalOffset + $totalFromInteractions;

        // Calculate equivalents
        // 1 tree absorbs ~21 kg CO2 per year (average)
        $treesEquivalent = round($totalOffset / 21);
        // Average car emits ~400 g CO2 per km = 0.4 kg/km, so offset = km not driven
        $kmNotDriven = round($totalOffset / 0.4);

        echo json_encode([
            'success' => true,
            'totalOffset' => round($totalOffset, 2),
            'treesEquivalent' => $treesEquivalent,
            'kmNotDriven' => $kmNotDriven
        ]);
        exit;
    }

    // GET CARBON OFFSET HISTORY
    if ($action === 'get_carbon_offset_history' && $method === 'GET') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $userId = $_SESSION['user_id'];

        // Get from user_carbon_offsets
        $offsetStmt = $conn->prepare("
            SELECT 
                uco.offset_id,
                uco.amount_kg,
                uco.source,
                uco.business_id,
                uco.product_name,
                uco.created_at,
                b.name as business_name
            FROM user_carbon_offsets uco
            LEFT JOIN businesses b ON uco.business_id = b.id
            WHERE uco.user_id = ?
            ORDER BY uco.created_at DESC
            LIMIT 50
        ");
        $offsetStmt->bind_param("i", $userId);
        $offsetStmt->execute();
        $offsetResult = $offsetStmt->get_result();

        $offsetHistory = [];
        while ($row = $offsetResult->fetch_assoc()) {
            $offsetHistory[] = [
                'id' => (int)$row['offset_id'],
                'amount_kg' => (float)$row['amount_kg'],
                'source' => $row['source'],
                'business_id' => $row['business_id'] ? (int)$row['business_id'] : null,
                'business_name' => $row['business_name'],
                'product_name' => $row['product_name'],
                'created_at' => $row['created_at'],
                'type' => 'offset'
            ];
        }

        // Get from user_interactions
        $interactionStmt = $conn->prepare("
            SELECT 
                ui.id,
                ui.co2_offset,
                ui.interaction_type,
                ui.business_id,
                ui.created_at,
                b.name as business_name
            FROM user_interactions ui
            LEFT JOIN businesses b ON ui.business_id = b.id
            WHERE ui.user_id = ? AND ui.co2_offset > 0
            ORDER BY ui.created_at DESC
            LIMIT 50
        ");
        $interactionStmt->bind_param("i", $userId);
        $interactionStmt->execute();
        $interactionResult = $interactionStmt->get_result();

        $interactionHistory = [];
        while ($row = $interactionResult->fetch_assoc()) {
            $interactionHistory[] = [
                'id' => (int)$row['id'],
                'amount_kg' => (float)$row['co2_offset'],
                'source' => ucfirst($row['interaction_type']),
                'business_id' => (int)$row['business_id'],
                'business_name' => $row['business_name'],
                'product_name' => null,
                'created_at' => $row['created_at'],
                'type' => 'interaction'
            ];
        }

        // Merge and sort by date
        $allHistory = array_merge($offsetHistory, $interactionHistory);
        usort($allHistory, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        echo json_encode([
            'success' => true,
            'history' => array_slice($allHistory, 0, 50)
        ]);
        exit;
    }

    // RECORD CARBON OFFSET (from interaction)
    if ($action === 'record_carbon_offset' && $method === 'POST') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $businessId = $data['business_id'] ?? null;
        $amountKg = $data['amount_kg'] ?? 0;
        $source = $data['source'] ?? 'interaction';
        $interactionType = $data['interaction_type'] ?? 'engagement';
        $productName = $data['product_name'] ?? null;

        if (!$businessId || $amountKg <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'business_id and amount_kg (positive) required']);
            exit;
        }

        // Record in user_interactions
        $stmt = $conn->prepare("
            INSERT INTO user_interactions (user_id, business_id, interaction_type, co2_offset)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iisd", $userId, $businessId, $interactionType, $amountKg);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Carbon offset recorded'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to record offset']);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);

} catch (Exception $e) {
    // Clear any output before sending error
    if (ob_get_level() > 0) {
        ob_clean();
    }
    error_log('Data API error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Error $e) {
    // Catch fatal errors too
    if (ob_get_level() > 0) {
        ob_clean();
    }
    error_log('Data API fatal error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Fatal error: ' . $e->getMessage()]);
} finally {
    // End output buffering if it was started
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    
    if (isset($conn) && $conn && !$conn->connect_error) {
        $conn->close();
    }
}
?>