<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../../db_config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

    if (!$user_id) {
        throw new Exception('You must be logged in to perform this action');
    }
    
    $comment_id = intval($input['comment_id']);
    $content = trim($input['content']);
    
    if (!$comment_id || !$user_id || empty($content)) {
        throw new Exception('Comment ID, User ID, and content are required');
    }
    
    // Verify the comment belongs to the user
    $stmt = $conn->prepare("SELECT id FROM comments WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $comment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Comment not found or you do not have permission to edit it');
    }
    
    // Update the comment
    $stmt = $conn->prepare("UPDATE comments SET content = ?, edited = TRUE WHERE id = ?");
    $stmt->bind_param("si", $content, $comment_id);
    $stmt->execute();
    
    // Get the updated comment with user info
    $stmt = $conn->prepare("SELECT c.*, u.name as author_name, u.email as author_email 
                            FROM comments c 
                            JOIN users u ON c.user_id = u.user_id 
                            WHERE c.id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comment = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Comment updated successfully',
        'comment' => $comment
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>