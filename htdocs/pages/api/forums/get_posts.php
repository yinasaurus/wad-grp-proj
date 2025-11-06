<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

try {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    require_once __DIR__ . '/../../../db_config.php';
    
    ob_clean();
    
    if (!isset($conn) || !$conn) {
        if (function_exists('getDBConnection')) {
            $conn = getDBConnection();
        }
    }
    if (!$conn || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn ? $conn->connect_error : 'Connection not set'));
    }
    
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

    $checkTable = $conn->query("SHOW TABLES LIKE 'posts'");
    if ($checkTable->num_rows === 0) {
        throw new Exception('Posts table does not exist. Please run the SQL schema to create it.');
    }
    
    $sql = "SELECT 
                p.*,
                COALESCE(NULLIF(u.name, ''), SUBSTRING_INDEX(u.email, '@', 1)) as author_name,
                u.email as author_email,
                COALESCE(like_counts.likes_count, 0) as likes_count,
                COALESCE(comment_counts.comments_count, 0) as comments_count
            FROM posts p
            LEFT JOIN users u ON p.user_id = u.user_id
            LEFT JOIN (
                SELECT post_id, COUNT(*) as likes_count 
                FROM likes 
                GROUP BY post_id
            ) as like_counts ON p.id = like_counts.post_id
            LEFT JOIN (
                SELECT post_id, COUNT(*) as comments_count 
                FROM comments 
                GROUP BY post_id
            ) as comment_counts ON p.id = comment_counts.post_id
            ORDER BY p.created_at DESC
            LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception('Failed to get result: ' . $stmt->error);
    }
    
    $posts = [];
    $post_ids = [];
    
    while ($row = $result->fetch_assoc()) {
        $post_ids[] = $row['id'];
        $posts[$row['id']] = $row;
        $posts[$row['id']]['user_liked'] = false;
        $posts[$row['id']]['likes_count'] = (int)($row['likes_count'] ?? 0);
        $posts[$row['id']]['comments_count'] = (int)($row['comments_count'] ?? 0);
    }
    
    if ($user_id && count($post_ids) > 0) {
        try {
            $placeholders = str_repeat('?,', count($post_ids) - 1) . '?';
            $likeCheckSql = "SELECT post_id FROM likes WHERE post_id IN ($placeholders) AND user_id = ?";
            $likeCheckStmt = $conn->prepare($likeCheckSql);
            if ($likeCheckStmt) {
                $params = array_merge($post_ids, [$user_id]);
                $types = str_repeat('i', count($post_ids)) . 'i';
                $likeCheckStmt->bind_param($types, ...$params);
                if ($likeCheckStmt->execute()) {
                    $likeCheckResult = $likeCheckStmt->get_result();
                    while ($likeRow = $likeCheckResult->fetch_assoc()) {
                        if (isset($posts[$likeRow['post_id']])) {
                            $posts[$likeRow['post_id']]['user_liked'] = true;
                        }
                    }
                }
                $likeCheckStmt->close();
            }
        } catch (Exception $e) {
            error_log('Error getting user_liked: ' . $e->getMessage());
        }
    }
    
    $posts = array_values($posts);
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'posts' => array_values($posts)
    ]);
    
} catch (Exception $e) {
    error_log('Forum get_posts error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load posts: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
} catch (Error $e) {
    error_log('Forum get_posts fatal error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load posts: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
} catch (Throwable $e) {
    error_log('Forum get_posts throwable error: ' . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load posts: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
} finally {
    ob_end_flush();
}
?>
