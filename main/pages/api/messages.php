<?php
/**
 * Messages API - PHP/MySQL based messaging system
 * Replaces Firebase Firestore messaging
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../db_config.php';

try {
    $conn = getDBConnection();
    
    // Check if user is logged in
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    $user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
    
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Get conversations list
    if ($action === 'conversations' && $method === 'GET') {
        if ($user_type === 'business') {
            // For business: get conversations where business_id matches
            $stmt = $conn->prepare("
                SELECT c.*, 
                       u.name as customer_name,
                       u.user_id as customer_id,
                       b.name as business_name
                FROM conversations c
                JOIN users u ON c.consumer_id = u.user_id
                JOIN businesses b ON c.business_id = b.id
                WHERE c.business_id = (
                    SELECT id FROM businesses WHERE user_id = ?
                )
                ORDER BY c.updated_at DESC
            ");
            $stmt->bind_param("i", $user_id);
        } else {
            // For consumer: get conversations where consumer_id matches
            $stmt = $conn->prepare("
                SELECT c.*, 
                       b.name as business_name,
                       b.id as business_id
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
        
        while ($row = $result->fetch_assoc()) {
            $conversations[] = [
                'id' => $row['id'],
                'consumer_id' => $row['consumer_id'],
                'business_id' => $row['business_id'],
                'last_message' => $row['last_message'],
                'last_message_time' => $row['last_message_time'],
                'unread_count' => $user_type === 'business' ? $row['business_unread_count'] : $row['consumer_unread_count'],
                'business_name' => $row['business_name'] ?? '',
                'customer_name' => $row['customer_name'] ?? '',
                'customer_id' => $row['customer_id'] ?? null
            ];
        }
        
        echo json_encode(['success' => true, 'conversations' => $conversations]);
        exit;
    }
    
    // Get messages for a conversation
    if ($action === 'messages' && $method === 'GET') {
        $conversation_id = intval($_GET['conversation_id'] ?? 0);
        
        if (!$conversation_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Conversation ID required']);
            exit;
        }
        
        // Verify user has access to this conversation
        $stmt = $conn->prepare("
            SELECT * FROM conversations 
            WHERE id = ? AND (consumer_id = ? OR business_id = (
                SELECT id FROM businesses WHERE user_id = ?
            ))
        ");
        $stmt->bind_param("iii", $conversation_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        // Mark messages as read (reset unread count)
        if ($user_type === 'business') {
            $stmt = $conn->prepare("UPDATE conversations SET business_unread_count = 0 WHERE id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE conversations SET consumer_unread_count = 0 WHERE id = ?");
        }
        $stmt->bind_param("i", $conversation_id);
        $stmt->execute();
        
        // Get messages
        $stmt = $conn->prepare("
            SELECT m.*, u.name as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.user_id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->bind_param("i", $conversation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = [
                'id' => $row['id'],
                'text' => $row['text'],
                'sender_id' => $row['sender_id'],
                'sender_name' => $row['sender_name'],
                'timestamp' => $row['created_at']
            ];
        }
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        exit;
    }
    
    // Send a message
    if ($action === 'send' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $conversation_id = intval($data['conversation_id'] ?? 0);
        $business_id = intval($data['business_id'] ?? 0);
        $text = trim($data['text'] ?? '');
        
        // Allow empty text for conversation creation only
        $isCreatingConversation = !$conversation_id && $business_id;
        
        if (empty($text) && !$isCreatingConversation) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Message text required']);
            exit;
        }
        
        // If conversation_id is provided, use it; otherwise create/find conversation
        if ($conversation_id) {
            // Verify access
            $stmt = $conn->prepare("
                SELECT * FROM conversations 
                WHERE id = ? AND (consumer_id = ? OR business_id = (
                    SELECT id FROM businesses WHERE user_id = ?
                ))
            ");
            $stmt->bind_param("iii", $conversation_id, $user_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Access denied']);
                exit;
            }
        } else {
            // Create or find conversation
            if ($user_type === 'business') {
                // Business sending to consumer - need consumer_id
                $consumer_id = intval($data['consumer_id'] ?? 0);
                if (!$consumer_id) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Consumer ID required']);
                    exit;
                }
                
                // Get business_id from user_id
                $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $business_row = $result->fetch_assoc();
                $business_id = $business_row['id'];
            } else {
                // Consumer sending to business
                if (!$business_id) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Business ID required']);
                    exit;
                }
            }
            
            // Find or create conversation
            if ($user_type === 'business') {
                $stmt = $conn->prepare("
                    SELECT id FROM conversations 
                    WHERE consumer_id = ? AND business_id = (
                        SELECT id FROM businesses WHERE user_id = ?
                    )
                ");
                $stmt->bind_param("ii", $consumer_id, $user_id);
            } else {
                $stmt = $conn->prepare("
                    SELECT id FROM conversations 
                    WHERE consumer_id = ? AND business_id = ?
                ");
                $stmt->bind_param("ii", $user_id, $business_id);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $conv_row = $result->fetch_assoc();
                $conversation_id = $conv_row['id'];
            } else {
                // Create new conversation
                if ($user_type === 'business') {
                    $stmt = $conn->prepare("
                        INSERT INTO conversations (consumer_id, business_id) 
                        VALUES (?, (SELECT id FROM businesses WHERE user_id = ?))
                    ");
                    $stmt->bind_param("ii", $consumer_id, $user_id);
                } else {
                    $stmt = $conn->prepare("INSERT INTO conversations (consumer_id, business_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $user_id, $business_id);
                }
                
                $stmt->execute();
                $conversation_id = $conn->insert_id;
            }
        }
        
        // Insert message (only if text is not empty)
        $message_id = null;
        if (!empty($text)) {
            $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, text) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $conversation_id, $user_id, $text);
            $stmt->execute();
            $message_id = $conn->insert_id;
            
            // Update conversation
            $unread_field = $user_type === 'business' ? 'consumer_unread_count' : 'business_unread_count';
            $stmt = $conn->prepare("
                UPDATE conversations 
                SET last_message = ?, 
                    last_message_time = NOW(), 
                    $unread_field = $unread_field + 1,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("si", $text, $conversation_id);
            $stmt->execute();
        } else {
            // Just update the conversation timestamp
            $stmt = $conn->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $conversation_id);
            $stmt->execute();
        }
        
        // Get the created message if one was created
        $message = null;
        if ($message_id) {
            $stmt = $conn->prepare("
                SELECT m.*, u.name as sender_name
                FROM messages m
                JOIN users u ON m.sender_id = u.user_id
                WHERE m.id = ?
            ");
            $stmt->bind_param("i", $message_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $message = $result->fetch_assoc();
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message ? [
                'id' => $message['id'],
                'text' => $message['text'],
                'sender_id' => $message['sender_id'],
                'sender_name' => $message['sender_name'],
                'timestamp' => $message['created_at']
            ] : null,
            'conversation_id' => $conversation_id
        ]);
        exit;
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
