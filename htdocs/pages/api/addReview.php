<?php
/**
 * addReview.php
 * API endpoint to add a new review for a business
 * Automatically tracks carbon offset when review is submitted
 */

session_start();

// Configure session cookies for InfinityFree compatibility
ini_set('session.cookie_httponly', '0');
ini_set('session.cookie_samesite', 'Lax');

require_once '../../db_config.php';

header('Content-Type: application/json');
$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in to submit a review.']);
    exit;
}

// Get and validate input
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$user_id = $_SESSION['user_id'];
$review = $input['review'] ?? $input['comment'] ?? null;
$rating = $input['rating'] ?? null;
$b_id = $input['b_id'] ?? null;

if (empty($review) || empty($rating) || empty($b_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required (review/comment, rating, b_id)']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}

try {
    $conn = getDBConnection();

    $userStmt = $conn->prepare("SELECT user_id, name FROM users WHERE user_id = ?");
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    
    if ($userResult->num_rows === 0) {
        $userStmt->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $userData = $userResult->fetch_assoc();
    $name = $userData['name'];
    $userStmt->close();

    // Check if user is a business and prevent them from reviewing their own company
    $userType = $_SESSION['user_type'] ?? null;
    if ($userType === 'business') {
        $userBusinessStmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
        $userBusinessStmt->bind_param("i", $user_id);
        $userBusinessStmt->execute();
        $userBusinessResult = $userBusinessStmt->get_result();
        
        if ($userBusinessResult->num_rows > 0) {
            $userBusinessRow = $userBusinessResult->fetch_assoc();
            $userBusinessId = $userBusinessRow['id'];
            
            if ($userBusinessId == $b_id) {
                $userBusinessStmt->close();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'You cannot write a review for your own business']);
                exit;
            }
        }
        $userBusinessStmt->close();
    }

    $businessStmt = $conn->prepare("SELECT id FROM businesses WHERE id = ?");
    $businessStmt->bind_param("i", $b_id);
    $businessStmt->execute();
    $businessResult = $businessStmt->get_result();
    
    if ($businessResult->num_rows === 0) {
        $businessStmt->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Business not found']);
        exit;
    }
    
    $businessStmt->close();
    $stmt = $conn->prepare("INSERT INTO review (user_id, name, review, rating, b_id) VALUES (?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception('Error preparing statement: ' . $conn->error);
    }
    
    $stmt->bind_param("issii", $user_id, $name, $review, $rating, $b_id);
    
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('Error executing statement: ' . $stmt->error);
    }
    
    $review_id = $conn->insert_id;
    $stmt->close();

    // Calculate carbon offset based on rating only
    $offsetAmount = ($rating >= 4) ? 2.0 : 1.0;
    
    if ($offsetAmount > 0) {
        $offsetStmt = $conn->prepare("
            INSERT INTO user_interactions (user_id, business_id, interaction_type, co2_offset)
            VALUES (?, ?, 'engagement', ?)
        ");
        $offsetStmt->bind_param("iid", $user_id, $b_id, $offsetAmount);
        $offsetStmt->execute();
        $offsetStmt->close();
    }

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Review added successfully',
        'review_id' => $review_id,
        'review' => [
            'review_id' => $review_id,
            'user_id' => $user_id,
            'userName' => $name,
            'name' => $name,
            'review' => $review,
            'comment' => $review,
            'rating' => (int)$rating,
            'b_id' => (int)$b_id,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error adding review: ' . $e->getMessage()
    ]);
}
?>
