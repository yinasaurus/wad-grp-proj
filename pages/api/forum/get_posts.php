<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../../db_config.php';

try {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

    
    $sql = "SELECT 
                p.*,
                u.name as author_name,
                u.email as author_email";
    
    if ($user_id) {
        $sql .= ", (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked";
    }
    
    $sql .= " FROM posts p
              JOIN users u ON p.user_id = u.user_id
              ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if ($user_id) {
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $row['user_liked'] = isset($row['user_liked']) ? ($row['user_liked'] > 0) : false;
        $posts[] = $row;
    }
    
    $posts = array_values($posts);
    
    echo json_encode([
        'success' => true,
        'posts' => $posts
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>