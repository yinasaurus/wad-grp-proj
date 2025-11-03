<?php
// Enable error reporting temporarily to debug
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering - MUST be first
ob_start();

try {
    // Set headers first
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    
    // Require db_config - fix path to go up 3 levels (forum -> api -> pages -> main)
    require_once __DIR__ . '/../../../db_config.php';
    
    // Now clear any output from db_config or require
    ob_clean();
    
    // Check if connection was created
    if (!isset($conn) || !$conn) {
        // Try to get connection using function
        if (function_exists('getDBConnection')) {
            $conn = getDBConnection();
        }
    }
    
    // Check database connection
    if (!$conn || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn ? $conn->connect_error : 'Connection not set'));
    }
    
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

    // Simple query - get posts with author info
    // Check if posts table exists first
    $checkTable = $conn->query("SHOW TABLES LIKE 'posts'");
    if ($checkTable->num_rows === 0) {
        throw new Exception('Posts table does not exist. Please run the SQL schema to create it.');
    }
    
    $sql = "SELECT 
                p.*,
                u.name as author_name,
                u.email as author_email
            FROM posts p
            LEFT JOIN users u ON p.user_id = u.user_id
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
    while ($row = $result->fetch_assoc()) {
        // Get user_liked status if user is logged in
        $row['user_liked'] = false;
        if ($user_id) {
            try {
                $likeStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM likes WHERE post_id = ? AND user_id = ?");
                if ($likeStmt) {
                    $likeStmt->bind_param("ii", $row['id'], $user_id);
                    if ($likeStmt->execute()) {
                        $likeResult = $likeStmt->get_result();
                        if ($likeResult && $likeRow = $likeResult->fetch_assoc()) {
                            $row['user_liked'] = ($likeRow['cnt'] > 0);
                        }
                    }
                    $likeStmt->close();
                }
            } catch (Exception $e) {
                // Table might not exist, use default false
                error_log('Error getting user_liked: ' . $e->getMessage());
            }
        }
        
        // Get likes count - wrap in try-catch
        $row['likes_count'] = isset($row['likes_count']) ? (int)$row['likes_count'] : 0;
        try {
            $likeCountStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM likes WHERE post_id = ?");
            if ($likeCountStmt) {
                $likeCountStmt->bind_param("i", $row['id']);
                if ($likeCountStmt->execute()) {
                    $likeCountResult = $likeCountStmt->get_result();
                    if ($likeCountResult && $likeCountRow = $likeCountResult->fetch_assoc()) {
                        $row['likes_count'] = (int)$likeCountRow['cnt'];
                    }
                }
                $likeCountStmt->close();
            }
        } catch (Exception $e) {
            // Table might not exist, keep default 0
            error_log('Error getting likes_count: ' . $e->getMessage());
        }
        
        // Get comments count - wrap in try-catch
        $row['comments_count'] = isset($row['comments_count']) ? (int)$row['comments_count'] : 0;
        try {
            $commentCountStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM comments WHERE post_id = ?");
            if ($commentCountStmt) {
                $commentCountStmt->bind_param("i", $row['id']);
                if ($commentCountStmt->execute()) {
                    $commentCountResult = $commentCountStmt->get_result();
                    if ($commentCountResult && $commentCountRow = $commentCountResult->fetch_assoc()) {
                        $row['comments_count'] = (int)$commentCountRow['cnt'];
                    }
                }
                $commentCountStmt->close();
            }
        } catch (Exception $e) {
            // Table might not exist, keep default 0
            error_log('Error getting comments_count: ' . $e->getMessage());
        }
        
        $posts[] = $row;
    }
    
    // Clear output and send JSON
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
