<?php
session_start();
// addreview.php - API endpoint to add a new review
require_once '../../db_config.php';

// Set JSON header
header('Content-Type: application/json');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// CHECK IF USER IS LOGGED IN
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please log in to submit a review.'
    ]);
    exit;
}

// Extract data
$user_id = $_SESSION['user_id'] ?? null;

// Get JSON input or form data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$review = $input['review'] ?? $input['comment'] ?? null;
$rating = $input['rating'] ?? null;
$b_id = $input['b_id'] ?? null;


// Validation
if (empty($user_id) || empty($review) || empty($rating) || empty($b_id)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required (user_id, name, review/comment, rating, b_id)'
    ]);
    exit;
}

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Rating must be between 1 and 5'
    ]);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Verify user exists
    $checkUser = $conn->prepare("SELECT user_id, name FROM users WHERE user_id = ?");
    $checkUser->bind_param("i", $user_id);
    $checkUser->execute();
    $userResult = $checkUser->get_result();
    
    
    if ($userResult->num_rows === 0) {
        $checkUser->close();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    } 
    // After verifying user exists, get their name:
    $userData = $userResult->fetch_assoc();
    $name = $userData['name']; // Use the name from database instead of input
    
    $checkUser->close();
    
    // Verify business exists
    $checkBusiness = $conn->prepare("SELECT id FROM businesses WHERE id = ?");
    $checkBusiness->bind_param("i", $b_id);
    $checkBusiness->execute();
    $businessResult = $checkBusiness->get_result();
    
    if ($businessResult->num_rows === 0) {
        $checkBusiness->close();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Business not found'
        ]);
        exit;
    }
    $checkBusiness->close();
    
  
    // Prepare statement
    $sql = "INSERT INTO review (user_id, name, review, rating, b_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Error preparing statement: ' . $conn->error);
    }
    
    $stmt->bind_param("issii", $user_id, $name, $review, $rating, $b_id);
    
    if ($stmt->execute()) {
        $review_id = $conn->insert_id;
        $stmt->close();
        
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
    } else {
        throw new Exception('Error executing statement: ' . $stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error adding review: ' . $e->getMessage()
    ]);
}
?>