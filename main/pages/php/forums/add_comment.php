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

    $post_id = intval($input['post_id']);
    $content = trim($input['content']);
    
    if (!$post_id || !$user_id || empty($content)) {
        throw new Exception('Post ID, User ID, and content are required');
    }
    
    // Verify the post exists
    $stmt = $conn->prepare("SELECT id FROM posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Post not found');
    }
    
    // Insert the comment
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $content);
    $stmt->execute();
    
    $comment_id = $conn->insert_id;
    
    // Increment comments count on the post
    $stmt = $conn->prepare("UPDATE posts SET comments_count = comments_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    
    // Get the newly created comment with user info
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
        'message' => 'Comment added successfully',
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