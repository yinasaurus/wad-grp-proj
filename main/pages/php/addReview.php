<?php
/**
 * addReview.php
 * API endpoint to add a new review for a business
 * Automatically tracks carbon offset when review is submitted
 */

session_start();
require_once '../../db_config.php';

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Require POST method
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

// Validate required fields
if (empty($review) || empty($rating) || empty($b_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required (review/comment, rating, b_id)']);
    exit;
}

// Validate rating range
if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Verify user exists and get name
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
    
    // Verify business exists and get sustainability score
    $businessStmt = $conn->prepare("SELECT id, sustainability_score FROM businesses WHERE id = ?");
    $businessStmt->bind_param("i", $b_id);
    $businessStmt->execute();
    $businessResult = $businessStmt->get_result();
    
    if ($businessResult->num_rows === 0) {
        $businessStmt->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Business not found']);
        exit;
    }
    
    $businessRow = $businessResult->fetch_assoc();
    
    // Insert review
    $stmt = $conn->prepare("INSERT INTO review (user_id, name, review, rating, b_id) VALUES (?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        $businessStmt->close();
        throw new Exception('Error preparing statement: ' . $conn->error);
    }
    
    $stmt->bind_param("issii", $user_id, $name, $review, $rating, $b_id);
    
    if (!$stmt->execute()) {
        $stmt->close();
        $businessStmt->close();
        throw new Exception('Error executing statement: ' . $stmt->error);
    }
    
    $review_id = $conn->insert_id;
    $stmt->close();
    
    // Auto-track carbon offset for writing a review
    $sustainabilityScore = (int)$businessRow['sustainability_score'];
    
    // Calculate offset: base (1-2 kg) + bonus based on sustainability score
    $baseOffset = ($rating >= 4) ? 2.0 : 1.0;
    $bonusOffset = min($sustainabilityScore * 0.1, 10);
    $offsetAmount = $baseOffset + $bonusOffset;
    
    if ($offsetAmount > 0) {
        $offsetStmt = $conn->prepare("
            INSERT INTO user_interactions (user_id, business_id, interaction_type, co2_offset)
            VALUES (?, ?, 'engagement', ?)
        ");
        $offsetStmt->bind_param("iid", $user_id, $b_id, $offsetAmount);
        $offsetStmt->execute();
        $offsetStmt->close();
    }
    
    $businessStmt->close();
    
    // Return success response
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
