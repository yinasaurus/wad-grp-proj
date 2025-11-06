<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../../db_config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $comment_id = intval($input['comment_id']);
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

    if (!$user_id) {
        throw new Exception('You must be logged in to perform this action');
    }
    
    if (!$comment_id || !$user_id) {
        throw new Exception('Comment ID and User ID are required');
    }
    
    $stmt = $conn->prepare("SELECT post_id FROM comments WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $comment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Comment not found or you do not have permission to delete it');
    }
    
    $comment = $result->fetch_assoc();
    $post_id = $comment['post_id'];
    
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    
    $stmt = $conn->prepare("UPDATE posts SET comments_count = comments_count - 1 WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Comment deleted successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>