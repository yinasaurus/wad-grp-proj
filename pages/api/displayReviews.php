<?php
session_start();
// getreviews.php - API endpoint to get reviews
require_once '../../db_config.php';

// Set JSON header
header('Content-Type: application/json');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use GET.'
    ]);
    exit;
}

// Get parameters
$b_id = $_GET['b_id'] ?? $_GET['id'] ?? null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    $conn = getDBConnection();
    
    if ($b_id) {
        // Get reviews for specific business
        $sql = "SELECT r.review_id, r.user_id, r.name, r.review, r.rating, r.b_id, r.created_at 
                FROM review r 
                WHERE r.b_id = ? 
                ORDER BY r.created_at DESC 
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $b_id, $limit, $offset);
    } else {
        // Get all reviews
        $sql = "SELECT r.review_id, r.user_id, r.name, r.review, r.rating, r.b_id, r.created_at 
                FROM review r 
                ORDER BY r.created_at DESC 
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
    }
    
    if (!$stmt) {
        throw new Exception('Error preparing statement: ' . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = [];
    
    while ($row = $result->fetch_assoc()) {
        // Calculate time ago
        $timeAgo = getTimeAgo($row['created_at']);
        
        // Format for frontend compatibility
        $reviews[] = [
            'review_id' => (int)$row['review_id'],
            'user_id' => (int)$row['user_id'],
            'userName' => $row['name'],
            'name' => $row['name'],
            'comment' => $row['review'],
            'review' => $row['review'],
            'rating' => (int)$row['rating'],
            'b_id' => (int)$row['b_id'],
            'created_at' => $row['created_at'],
            'date' => $timeAgo
        ];
    }
    
    $stmt->close();
    
    // Calculate average rating
    $avgRating = 0;
    if (count($reviews) > 0) {
        $sum = array_sum(array_column($reviews, 'rating'));
        $avgRating = round($sum / count($reviews), 1);
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'count' => count($reviews),
        'averageRating' => $avgRating
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching reviews: ' . $e->getMessage(),
        'reviews' => [],
        'count' => 0
    ]);
}

// Helper function to calculate time ago
function getTimeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}
?>