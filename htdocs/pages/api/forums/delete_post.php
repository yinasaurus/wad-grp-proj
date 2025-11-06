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
    
    if (!$user_id) {
        throw new Exception('You must be logged in to delete a post');
    }
    
    if (!$post_id) {
        throw new Exception('Post ID is required');
    }

    $stmt = $conn->prepare("SELECT id, image_url FROM posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Post not found or you do not have permission to delete it');
    }
    
    $post = $result->fetch_assoc();
    $image_url = $post['image_url'];
    
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    
    if ($image_url && file_exists(__DIR__ . '/../../../' . $image_url)) {
        @unlink(__DIR__ . '/../../../' . $image_url);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Post deleted successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
