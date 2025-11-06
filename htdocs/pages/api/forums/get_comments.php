<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../../db_config.php';

try {
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    
    if (!$post_id) {
        throw new Exception('Post ID is required');
    }
    
    $sql = "SELECT 
                c.*,
                COALESCE(NULLIF(u.name, ''), SUBSTRING_INDEX(u.email, '@', 1)) as author_name,
                u.email as author_email
            FROM comments c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'comments' => $comments
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>