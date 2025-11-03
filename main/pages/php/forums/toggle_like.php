<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../../db_config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $post_id = intval($input['post_id']);
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    
    if (!$post_id || !$user_id) {
        throw new Exception('Post ID and User ID are required');
    }
    
    // Check if like already exists
    $stmt = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $liked = false;
    
    if ($result->num_rows > 0) {
        // Unlike - remove the like
        $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
        
        // Decrement likes count
        $stmt = $conn->prepare("UPDATE posts SET likes_count = likes_count - 1 WHERE id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        
        $liked = false;
    } else {
        // Like - add the like
        $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
        
        // Increment likes count
        $stmt = $conn->prepare("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        
        $liked = true;
    }
    
    // Get updated likes count
    $stmt = $conn->prepare("SELECT likes_count FROM posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'likes_count' => intval($post['likes_count'])
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>