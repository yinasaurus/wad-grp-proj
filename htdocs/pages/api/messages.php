<?php
/**
 * ==========================================
 * OLD VERSION (kept for reference)
 * ==========================================
 */
/*
<--- paste your current old code here if you want to keep it as comment --->
*/

/**
 * ==========================================
 * NEW VERSION – supports:
 *  - user ↔ business messaging
 *  - business ↔ business messaging
 * ==========================================
 */

// Test endpoint to verify file is being executed - MUST BE FIRST
if (isset($_GET['test'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode(['success' => true, 'message' => 'API file is accessible and PHP is executing', 'timestamp' => date('Y-m-d H:i:s')]);
    exit;
}

// Set error handling first
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering immediately to catch any output
ob_start();

session_start();

// Configure session cookies for InfinityFree compatibility
ini_set('session.cookie_httponly', '0');
ini_set('session.cookie_samesite', 'Lax');

// Set JSON header immediately to prevent HTML output
header('Content-Type: application/json; charset=utf-8');

$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit;
}

// Clear any output before requiring db_config
ob_clean();

require_once '../../db_config.php';

try {
    $conn = getDBConnection();
    
    if (!$conn || $conn->connect_error) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection failed', 'message' => $conn ? $conn->connect_error : 'Connection object is null']);
        exit;
    }
    
    $user_id     = $_SESSION['user_id'] ?? null;
    $user_type   = $_SESSION['user_type'] ?? null;
    $business_id = $_SESSION['business_id'] ?? null;
    
    if (!$user_id) {
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    
    // If business user but business_id not in session, fetch it from database
    if ($user_type === 'business' && !$business_id) {
        $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
        if (!$stmt) {
            error_log("Failed to prepare business_id query: " . $conn->error);
        } else {
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                error_log("Failed to execute business_id query: " . $stmt->error);
            } else {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $business_row = $result->fetch_assoc();
                    $business_id = $business_row['id'];
                    $_SESSION['business_id'] = $business_id; // Cache it in session
                } else {
                    error_log("Business ID not found for user_id: " . $user_id);
                }
            }
            $stmt->close();
        }
    }
    
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    /**
     * =====================================================
     * GET CONVERSATIONS
     * =====================================================
     */
    if ($action === 'conversations' && $method === 'GET') {
        if ($user_type === 'business') {
            // Business user: show both user↔business and business↔business
            // Note: consumer-to-business conversations use business_id column
            // business-to-business conversations use business_id_1 and business_id_2 columns
            $stmt = $conn->prepare("
                SELECT c.*, 
                       u.name AS consumer_name,
                       u.user_id AS consumer_user_id,
                       b1.name AS business_name_1,
                       b2.name AS business_name_2,
                       b1.id AS business_id_1_val,
                       b2.id AS business_id_2_val,
                       b.name AS business_name_consumer
                FROM conversations c
                LEFT JOIN users u ON c.consumer_id = u.user_id
                LEFT JOIN businesses b1 ON c.business_id_1 = b1.id
                LEFT JOIN businesses b2 ON c.business_id_2 = b2.id
                LEFT JOIN businesses b ON c.business_id = b.id
                WHERE 
                    c.business_id = ? 
                    OR c.business_id_1 = ? 
                    OR c.business_id_2 = ?
                ORDER BY c.updated_at DESC
            ");
            $stmt->bind_param("iii", $business_id, $business_id, $business_id);
        } else {
            // Consumer
            $stmt = $conn->prepare("
                SELECT c.*, b.name AS business_name
                FROM conversations c
                JOIN businesses b ON c.business_id = b.id
                WHERE c.consumer_id = ?
                ORDER BY c.updated_at DESC
            ");
            $stmt->bind_param("i", $user_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $conversations = [];
        $rows = [];
        $businessIdsToFetch = [];
        
        // First pass: collect all rows and business IDs that need names
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
            
            // Collect business IDs that need names (for business-to-business conversations)
            if ($user_type === 'business' && empty($row['consumer_id']) && empty($row['business_id']) && ($row['business_id_1'] || $row['business_id_2'])) {
                // Business-to-business conversation
                if ($row['business_id_1'] == $business_id && !empty($row['business_id_2'])) {
                    $businessIdsToFetch[(int)$row['business_id_2']] = true;
                } else if ($row['business_id_2'] == $business_id && !empty($row['business_id_1'])) {
                    $businessIdsToFetch[(int)$row['business_id_1']] = true;
                }
            }
        }
        
        // Fetch all business names in one batch query
        $businessNames = [];
        if (!empty($businessIdsToFetch)) {
            $businessIds = array_keys($businessIdsToFetch);
            error_log("Fetching business names for IDs: " . implode(', ', $businessIds));
            $placeholders = implode(',', array_fill(0, count($businessIds), '?'));
            $nameStmt = $conn->prepare("SELECT id, name FROM businesses WHERE id IN ($placeholders)");
            if ($nameStmt) {
                $types = str_repeat('i', count($businessIds));
                $nameStmt->bind_param($types, ...$businessIds);
                if ($nameStmt->execute()) {
                    $nameResult = $nameStmt->get_result();
                    while ($nameRow = $nameResult->fetch_assoc()) {
                        $businessNames[(int)$nameRow['id']] = $nameRow['name'];
                        error_log("Fetched business name: ID " . $nameRow['id'] . " = " . $nameRow['name']);
                    }
                } else {
                    error_log("Failed to execute batch name query: " . $nameStmt->error);
                }
                $nameStmt->close();
            } else {
                error_log("Failed to prepare batch name query: " . $conn->error);
            }
        } else {
            error_log("No business IDs to fetch (businessIdsToFetch is empty)");
        }
        
        // Second pass: process rows and determine names
        foreach ($rows as $row) {
            // Determine conversation name and unread count
            $conversationName = '';
            $unreadCount = 0;
            
            if ($user_type === 'business') {
                // Check for business-to-business conversation first (consumer_id and business_id are NULL)
                if (empty($row['consumer_id']) && empty($row['business_id']) && ($row['business_id_1'] || $row['business_id_2'])) {
                    // Business ↔ Business conversation
                    // First try to use JOIN results (already fetched)
                    if ($row['business_id_1'] == $business_id && !empty($row['business_name_2'])) {
                        // Current business is business_id_1, use business_name_2 from JOIN
                        $conversationName = $row['business_name_2'];
                        error_log("Using JOIN result business_name_2 for conversation {$row['id']}: $conversationName");
                    } else if ($row['business_id_2'] == $business_id && !empty($row['business_name_1'])) {
                        // Current business is business_id_2, use business_name_1 from JOIN
                        $conversationName = $row['business_name_1'];
                        error_log("Using JOIN result business_name_1 for conversation {$row['id']}: $conversationName");
                    } else {
                        // JOIN results not available, use batch query results
                        if ($row['business_id_1'] == $business_id && !empty($row['business_id_2'])) {
                            $targetBusinessId = (int)$row['business_id_2'];
                        } else if ($row['business_id_2'] == $business_id && !empty($row['business_id_1'])) {
                            $targetBusinessId = (int)$row['business_id_1'];
                        } else {
                            $targetBusinessId = null;
                        }
                        
                        if ($targetBusinessId && isset($businessNames[$targetBusinessId])) {
                            $conversationName = $businessNames[$targetBusinessId];
                            error_log("Using batch query result for conversation {$row['id']}: $conversationName");
                        } else if ($targetBusinessId) {
                            $conversationName = 'Business #' . $targetBusinessId;
                            error_log("Business name not found for ID $targetBusinessId, using fallback: $conversationName");
                        } else {
                            $conversationName = 'Business';
                            error_log("No targetBusinessId found for conversation {$row['id']}, using default: Business");
                        }
                    }
                    $unreadCount = $row['business_unread_count'] ?? 0;
                } else if ($row['consumer_id'] && $row['business_id'] == $business_id) {
                    // Consumer ↔ Business conversation
                    $conversationName = $row['consumer_name'] ?? 'Consumer';
                    $unreadCount = $row['business_unread_count'] ?? 0;
                } else if ($row['business_id'] == $business_id) {
                    // Fallback: consumer-to-business conversation (business_id matches but consumer_id might be NULL)
                    $conversationName = $row['consumer_name'] ?? 'Consumer';
                    $unreadCount = $row['business_unread_count'] ?? 0;
                }
            } else {
                // Consumer
                $conversationName = $row['business_name'] ?? 'Business';
                $unreadCount = $row['consumer_unread_count'] ?? 0;
            }
            
            // Only add conversation if we have a name (skip if error in logic)
            if (!empty($conversationName)) {
                $conversations[] = [
                    'id' => $row['id'],
                    'consumer_id' => $row['consumer_id'] ?? null,
                    'business_id' => $row['business_id'] ?? null,
                    'business_id_1' => $row['business_id_1'] ?? null,
                    'business_id_2' => $row['business_id_2'] ?? null,
                    'last_message' => $row['last_message'] ?? '',
                    'last_message_time' => $row['last_message_time'] ?? null,
                    'unread_count' => $unreadCount,
                    'business_name' => $conversationName,
                    'business_name_1' => $row['business_name_1'] ?? null,
                    'business_name_2' => $row['business_name_2'] ?? null,
                    'consumer_name' => $row['consumer_name'] ?? null,
                    'customer_name' => $row['consumer_name'] ?? null,
                    'customer_id' => $row['consumer_id'] ?? null
                ];
            }
        }
        
        echo json_encode(['success' => true, 'conversations' => $conversations]);
        exit;
    }
    
    /**
     * =====================================================
     * GET MESSAGES
     * =====================================================
     */
    if ($action === 'messages' && $method === 'GET') {
        error_log("=== GET MESSAGES REQUEST ===");
        error_log("User ID: " . ($user_id ?? 'NULL'));
        error_log("User Type: " . ($user_type ?? 'NULL'));
        error_log("Business ID (session): " . ($business_id ?? 'NULL'));
        
        // If business user but business_id not in session, fetch it from database
        if ($user_type === 'business' && !$business_id) {
            error_log("Business ID not in session, fetching from database...");
            $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        $business_row = $result->fetch_assoc();
                        $business_id = $business_row['id'];
                        $_SESSION['business_id'] = $business_id;
                        error_log("Retrieved business_id: $business_id");
                    } else {
                        error_log("WARNING: No business record found for user_id: $user_id");
                    }
                }
                $stmt->close();
            }
        }
        
        error_log("Business ID (final): " . ($business_id ?? 'NULL'));
        
        $conversation_id = intval($_GET['conversation_id'] ?? 0);
        error_log("Conversation ID: $conversation_id");
        
        if (!$conversation_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Conversation ID required']);
            exit;
        }
        
        // Verify access to conversation
        if ($user_type === 'business') {
            error_log("Checking business access to conversation $conversation_id");
            error_log("Business ID: $business_id, User ID: $user_id");
            
            // For business users, check access to both user↔business and business↔business conversations
            // For business↔business: consumer_id and business_id are NULL, use business_id_1 and business_id_2
            // For user↔business: business_id matches their business_id
            $stmt = $conn->prepare("
                SELECT * FROM conversations 
                WHERE id = ? 
                  AND (
                        business_id = ?
                     OR (consumer_id IS NULL AND business_id IS NULL AND (business_id_1 = ? OR business_id_2 = ?))
                  )
            ");
            if (!$stmt) {
                $errorMsg = 'Database error preparing access check: ' . $conn->error;
                error_log("ERROR: $errorMsg");
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $errorMsg]);
                exit;
            }
            
            $stmt->bind_param("iiii", $conversation_id, $business_id, $business_id, $business_id);
            if (!$stmt->execute()) {
                $errorMsg = 'Database error executing access check: ' . $stmt->error;
                error_log("ERROR: $errorMsg");
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $errorMsg]);
                exit;
            }
            
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                error_log("Access denied: No matching conversation found");
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Access denied']);
                exit;
            }
            
            $conversation = $result->fetch_assoc();
            error_log("Access granted. Conversation type: " . ($conversation['consumer_id'] ? 'consumer-business' : 'business-business'));
        } else {
            $stmt = $conn->prepare("
                SELECT * FROM conversations 
                WHERE id = ? AND consumer_id = ?
            ");
            if (!$stmt) {
                $errorMsg = 'Database error preparing access check: ' . $conn->error;
                error_log("ERROR: $errorMsg");
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $errorMsg]);
                exit;
            }
            
            $stmt->bind_param("ii", $conversation_id, $user_id);
            if (!$stmt->execute()) {
                $errorMsg = 'Database error executing access check: ' . $stmt->error;
                error_log("ERROR: $errorMsg");
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $errorMsg]);
                exit;
            }
            
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                error_log("Access denied: No matching conversation found");
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Access denied']);
                exit;
            }
        }
        
        // Mark messages as read (reset unread count)
        if ($user_type === 'business') {
            $stmt = $conn->prepare("UPDATE conversations SET business_unread_count = 0 WHERE id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE conversations SET consumer_unread_count = 0 WHERE id = ?");
        }
        $stmt->bind_param("i", $conversation_id);
        $stmt->execute();
        
        // Mark messages as read (set read_at timestamp for messages sent by the other party)
        // Check if read_at column exists
        $checkReadAt = $conn->query("SHOW COLUMNS FROM messages LIKE 'read_at'");
        $hasReadAt = $checkReadAt->num_rows > 0;
        
        if ($hasReadAt) {
            // Get the other party's ID (recipient)
            $conversationStmt = $conn->prepare("SELECT consumer_id, business_id, business_id_1, business_id_2 FROM conversations WHERE id = ?");
            $conversationStmt->bind_param("i", $conversation_id);
            $conversationStmt->execute();
            $convResult = $conversationStmt->get_result();
            $conversation = $convResult->fetch_assoc();
            
            // Determine recipient ID based on user type and conversation type
            if ($user_type === 'business') {
                if ($conversation['consumer_id']) {
                    // User ↔ Business conversation
                    $recipientId = $conversation['consumer_id'];
                } else {
                    // Business ↔ Business conversation
                    if ($conversation['business_id_1'] == $business_id) {
                        $recipientId = $conversation['business_id_2'];
                    } else {
                        $recipientId = $conversation['business_id_1'];
                    }
                }
            } else {
                // Consumer
                $recipientId = $conversation['business_id'];
            }
            
            // Mark messages sent TO the current user (not by them) as read
            // Note: For business-to-business, we need to check sender_id against business user_id
            // This is a simplified version - you may need to adjust based on your schema
            $markReadStmt = $conn->prepare("
                UPDATE messages 
                SET read_at = NOW() 
                WHERE conversation_id = ? 
                AND sender_id != ? 
                AND (read_at IS NULL OR read_at = '0000-00-00 00:00:00')
            ");
            $markReadStmt->bind_param("ii", $conversation_id, $user_id);
            $markReadStmt->execute();
            $markReadStmt->close();
        }
        
        // Get messages (include read_at if column exists)
        error_log("Fetching messages for conversation $conversation_id");
        $checkReadAt = $conn->query("SHOW COLUMNS FROM messages LIKE 'read_at'");
        $hasReadAt = $checkReadAt->num_rows > 0;
        
        if ($hasReadAt) {
            $stmt = $conn->prepare("
                SELECT m.*, u.name as sender_name
                FROM messages m
                JOIN users u ON m.sender_id = u.user_id
                WHERE m.conversation_id = ?
                ORDER BY m.created_at ASC
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT m.*, u.name as sender_name, NULL as read_at
                FROM messages m
                JOIN users u ON m.sender_id = u.user_id
                WHERE m.conversation_id = ?
                ORDER BY m.created_at ASC
            ");
        }
        
        if (!$stmt) {
            $errorMsg = 'Database error preparing messages query: ' . $conn->error;
            error_log("ERROR: $errorMsg");
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $errorMsg]);
            exit;
        }
        
        $stmt->bind_param("i", $conversation_id);
        if (!$stmt->execute()) {
            $errorMsg = 'Database error executing messages query: ' . $stmt->error;
            error_log("ERROR: $errorMsg");
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $errorMsg]);
            exit;
        }
        
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = [
                'id' => $row['id'],
                'text' => $row['text'],
                'sender_id' => $row['sender_id'],
                'sender_name' => $row['sender_name'],
                'timestamp' => $row['created_at'],
                'read_at' => isset($row['read_at']) && $row['read_at'] !== null && $row['read_at'] !== '0000-00-00 00:00:00' ? $row['read_at'] : null
            ];
        }
        
        error_log("Found " . count($messages) . " messages");
        echo json_encode(['success' => true, 'messages' => $messages]);
        exit;
    }
    
    /**
     * =====================================================
     * SEND MESSAGE
     * =====================================================
     */
    if ($action === 'send' && $method === 'POST') {
        // Debug: Log incoming request
        error_log("=== SEND MESSAGE REQUEST ===");
        error_log("User ID: " . ($user_id ?? 'NULL'));
        error_log("User Type: " . ($user_type ?? 'NULL'));
        error_log("Business ID (session): " . ($business_id ?? 'NULL'));
        
        $data          = json_decode(file_get_contents('php://input'), true);
        error_log("Request data: " . json_encode($data));
        
        $conversation_id = intval($data['conversation_id'] ?? 0);
        $receiver_id     = intval($data['receiver_id'] ?? 0);
        $receiver_type   = $data['receiver_type'] ?? 'user';
        $business_id_param = intval($data['business_id'] ?? 0); // For backward compatibility
        $text            = trim($data['text'] ?? '');
        
        error_log("Parsed values - conversation_id: $conversation_id, receiver_id: $receiver_id, receiver_type: $receiver_type, text length: " . strlen($text));
        
        // Backward compatibility: if business_id is provided but receiver_id is not, use business_id
        if ($business_id_param && !$receiver_id) {
            $receiver_id = $business_id_param;
            $receiver_type = 'business';
            error_log("Using business_id_param as receiver_id: $receiver_id");
        }
        
        if (!$receiver_id && !$conversation_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Receiver ID or Conversation ID required']);
            exit;
        }
        
        if (empty($text) && !$conversation_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Message text required']);
            exit;
        }
        
        // Find or create conversation
        if (!$conversation_id) {
            error_log("No conversation_id, need to find or create conversation");
            if ($user_type === 'business' && $receiver_type === 'business') {
                error_log("Business-to-business conversation");
                error_log("Current business_id: " . ($business_id ?? 'NULL'));
                error_log("Receiver ID: $receiver_id");
                
                // Verify business_id is set
                if (!$business_id) {
                    error_log("ERROR: business_id is NULL!");
                    error_log("Attempting to fetch business_id from database for user_id: $user_id");
                    
                    // Try one more time to get business_id
                    $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $user_id);
                        if ($stmt->execute()) {
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $business_row = $result->fetch_assoc();
                                $business_id = $business_row['id'];
                                $_SESSION['business_id'] = $business_id;
                                error_log("Successfully retrieved business_id: $business_id");
                            } else {
                                error_log("No business record found for user_id: $user_id");
                            }
                        } else {
                            error_log("Failed to execute query: " . $stmt->error);
                        }
                        $stmt->close();
                    } else {
                        error_log("Failed to prepare query: " . $conn->error);
                    }
                }
                
                if (!$business_id) {
                    http_response_code(500);
                    $errorMsg = "Business ID not found. User ID: $user_id, User Type: $user_type. Please ensure you are logged in as a business user and have a business profile.";
                    error_log("ERROR: $errorMsg");
                    echo json_encode([
                        'success' => false, 
                        'error' => $errorMsg,
                        'debug' => [
                            'user_id' => $user_id,
                            'user_type' => $user_type,
                            'business_id' => $business_id,
                            'receiver_id' => $receiver_id,
                            'receiver_type' => $receiver_type
                        ]
                    ]);
                    exit;
                }
                
                // Verify receiver_id is set and valid
                if (!$receiver_id || $receiver_id <= 0) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'error' => 'Invalid receiver ID. Please ensure you are sending to a valid business.'
                    ]);
                    exit;
                }
                
                // Prevent sending message to self
                if ($business_id == $receiver_id) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'error' => 'Cannot send message to yourself.'
                    ]);
                    exit;
                }
                
                // Business to business - check if columns exist first
                $checkColumns = $conn->query("SHOW COLUMNS FROM conversations LIKE 'business_id_1'");
                if ($checkColumns->num_rows === 0) {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false, 
                        'error' => 'Database schema not updated. Please run the SQL migration: ALTER TABLE conversations ADD COLUMN business_id_1 INT NULL AFTER business_id, ADD COLUMN business_id_2 INT NULL AFTER business_id_1;'
                    ]);
                    exit;
                }
                
                // Check if consumer_id and business_id can be NULL (for business-to-business conversations)
                // Note: We'll try to insert NULL first, and if it fails due to NOT NULL constraint,
                // we'll catch the error and provide instructions
                
                // Business to business
                error_log("Searching for existing conversation: business_id_1=$business_id, business_id_2=$receiver_id");
                $stmt = $conn->prepare("
                    SELECT id FROM conversations
                    WHERE (business_id_1 = ? AND business_id_2 = ?) 
                       OR (business_id_1 = ? AND business_id_2 = ?)
                ");
                if (!$stmt) {
                    $errorMsg = 'Database error preparing query: ' . $conn->error;
                    error_log("ERROR: $errorMsg");
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => $errorMsg]);
                    exit;
                }
                
                $stmt->bind_param("iiii", $business_id, $receiver_id, $receiver_id, $business_id);
                if (!$stmt->execute()) {
                    $errorMsg = 'Database error executing query: ' . $stmt->error;
                    error_log("ERROR: $errorMsg");
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => $errorMsg]);
                    exit;
                }
                $res = $stmt->get_result();
                
                if ($res->num_rows > 0) {
                    $conversation_id = $res->fetch_assoc()['id'];
                    error_log("Found existing conversation: $conversation_id");
                } else {
                    error_log("No existing conversation found, creating new one");
                    $stmt->close();
                    // For business-to-business conversations, set consumer_id and business_id to NULL
                    // to avoid foreign key constraint violations
                    $stmt = $conn->prepare("
                        INSERT INTO conversations (consumer_id, business_id, business_id_1, business_id_2, updated_at) 
                        VALUES (NULL, NULL, ?, ?, NOW())
                    ");
                    if (!$stmt) {
                        $errorMsg = 'Database error preparing INSERT: ' . $conn->error;
                        error_log("ERROR: $errorMsg");
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => $errorMsg]);
                        exit;
                    }
                    $stmt->bind_param("ii", $business_id, $receiver_id);
                    if (!$stmt->execute()) {
                        $errorMsg = 'Database error executing INSERT: ' . $stmt->error;
                        error_log("ERROR: $errorMsg");
                        
                        // Check if error is due to NOT NULL constraint or foreign key constraint
                        if (strpos($stmt->error, 'foreign key constraint') !== false) {
                            // Foreign key constraint error - likely consumer_id or business_id cannot be NULL
                            $errorMsg = 'Database schema needs update for business-to-business messaging. Please run this SQL in your database: ALTER TABLE conversations MODIFY COLUMN consumer_id INT NULL, MODIFY COLUMN business_id INT NULL;';
                        } else if (strpos($stmt->error, 'cannot be null') !== false || strpos($stmt->error, 'Column') !== false) {
                            // NOT NULL constraint error
                            $errorMsg = 'Database schema needs update. Please run: ALTER TABLE conversations MODIFY COLUMN consumer_id INT NULL, MODIFY COLUMN business_id INT NULL;';
                        }
                        
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => $errorMsg, 'debug' => ['sql_error' => $stmt->error]]);
                        exit;
                    }
                    $conversation_id = $conn->insert_id;
                    error_log("Created new conversation: $conversation_id");
                }
                $stmt->close();
            } elseif ($user_type === 'business' && $receiver_type === 'user') {
                // Business to user
                $stmt = $conn->prepare("
                    SELECT id FROM conversations 
                    WHERE consumer_id = ? AND business_id = ?
                ");
                $stmt->bind_param("ii", $receiver_id, $business_id);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if ($res->num_rows > 0) {
                    $conversation_id = $res->fetch_assoc()['id'];
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO conversations (consumer_id, business_id, updated_at) 
                        VALUES (?, ?, NOW())
                    ");
                    $stmt->bind_param("ii", $receiver_id, $business_id);
                    $stmt->execute();
                    $conversation_id = $conn->insert_id;
                }
            } else {
                // User to business (backward compatibility)
                $stmt = $conn->prepare("
                    SELECT id FROM conversations 
                    WHERE consumer_id = ? AND business_id = ?
                ");
                $stmt->bind_param("ii", $user_id, $receiver_id);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if ($res->num_rows > 0) {
                    $conversation_id = $res->fetch_assoc()['id'];
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO conversations (consumer_id, business_id, updated_at) 
                        VALUES (?, ?, NOW())
                    ");
                    $stmt->bind_param("ii", $user_id, $receiver_id);
                    $stmt->execute();
                    $conversation_id = $conn->insert_id;
                }
            }
        }
        
        // Insert message
        if (!empty($text)) {
            error_log("Inserting message - conversation_id: $conversation_id, sender_id: $user_id, text length: " . strlen($text));
            
            if (!$conversation_id) {
                $errorMsg = 'Failed to create or find conversation';
                error_log("ERROR: $errorMsg");
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $errorMsg]);
                exit;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO messages (conversation_id, sender_id, text, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            if (!$stmt) {
                $errorMsg = 'Database error preparing message INSERT: ' . $conn->error;
                error_log("ERROR: $errorMsg");
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $errorMsg]);
                exit;
            }
            $stmt->bind_param("iis", $conversation_id, $user_id, $text);
            if (!$stmt->execute()) {
                $errorMsg = 'Database error executing message INSERT: ' . $stmt->error;
                error_log("ERROR: $errorMsg");
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $errorMsg]);
                exit;
            }
            $message_id = $conn->insert_id;
            error_log("Message inserted successfully with ID: $message_id");
            $stmt->close();
            
            // Update conversation last message
            // For business-to-business, we need to determine which unread count to update
            // Check if this is a business-to-business conversation
            $checkB2B = $conn->prepare("SELECT business_id_1, business_id_2, consumer_id FROM conversations WHERE id = ?");
            $checkB2B->bind_param("i", $conversation_id);
            $checkB2B->execute();
            $convCheck = $checkB2B->get_result()->fetch_assoc();
            $checkB2B->close();
            
            // Determine unread field based on conversation type
            if ($convCheck && !$convCheck['consumer_id'] && ($convCheck['business_id_1'] || $convCheck['business_id_2'])) {
                // Business-to-business conversation - always use business_unread_count
                $unread_field = 'business_unread_count';
            } else {
                // User-to-business conversation
                $unread_field = $user_type === 'business' ? 'consumer_unread_count' : 'business_unread_count';
            }
            
            $stmt = $conn->prepare("
                UPDATE conversations 
                SET last_message = ?, last_message_time = NOW(), updated_at = NOW(), 
                    $unread_field = $unread_field + 1
                WHERE id = ?
            ");
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("si", $text, $conversation_id);
            if (!$stmt->execute()) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
                exit;
            }
            $stmt->close();
            
            // Get the created message
            $stmt = $conn->prepare("
                SELECT m.*, u.name as sender_name
                FROM messages m
                JOIN users u ON m.sender_id = u.user_id
                WHERE m.id = ?
            ");
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("i", $message_id);
            if (!$stmt->execute()) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
                exit;
            }
            $result = $stmt->get_result();
            $message = $result->fetch_assoc();
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'conversation_id' => $conversation_id,
                'message_id' => $message_id,
                'message' => $message ? [
                    'id' => $message['id'],
                    'text' => $message['text'],
                    'sender_id' => $message['sender_id'],
                    'sender_name' => $message['sender_name'],
                    'timestamp' => $message['created_at']
                ] : null
            ]);
        } else {
            // Just return conversation_id if no message text
            echo json_encode([
                'success' => true,
                'conversation_id' => $conversation_id
            ]);
        }
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    
} catch (Exception $e) {
    ob_clean();
    error_log("Messages API Error: " . $e->getMessage() . " in " . $e->getFile() . " at line " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    exit;
} catch (Error $e) {
    // Catch fatal errors (PHP 7+)
    ob_clean();
    error_log("Messages API Fatal Error: " . $e->getMessage() . " in " . $e->getFile() . " at line " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Internal server error. Please check server logs.', 'message' => $e->getMessage()]);
    exit;
} finally {
    if (isset($conn)) $conn->close();
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}

/**
 * ==========================================
 * SQL PATCH (add to your DB)
 * ==========================================
 * ALTER TABLE conversations 
 * ADD COLUMN business_id_1 INT NULL AFTER business_id,
 * ADD COLUMN business_id_2 INT NULL AFTER business_id_1;
 */

/**
 * ==========================================
 * HTML BUTTON SNIPPET (for business profile)
 * ==========================================
 * <button class="btn btn-success" 
 *         onclick="window.location.href='messages_business.html?receiver_id={{business_id}}&receiver_type=business'">
 *   <i class="fa fa-paper-plane"></i> Send Message
 * </button>
 */

?>
