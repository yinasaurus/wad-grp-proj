<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../../db_config.php';

try {
    // Check if user is logged in
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

    if (!$user_id) {
        throw new Exception('You must be logged in to create a post');
    }

    // Get form data (not JSON because of file upload)
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    
    if (empty($category) || empty($title) || empty($content)) {
        throw new Exception('Category, title, and content are required');
    }
    
    // Handle image upload
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Get the file info
        $file = $_FILES['image'];
        $file_size = $file['size'];
        $max_size = 5 * 1024 * 1024; // 5MB limit
        
        // Check file size
        if ($file_size > $max_size) {
            throw new Exception('Image size too large. Maximum size is 5MB');
        }
        
        // Determine upload directory relative to main/pages/
        $upload_dir = __DIR__ . '/../../../uploads/posts/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed');
        }
        
        // Validate image file (check if it's actually an image)
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            throw new Exception('Invalid image file. Please upload a valid image.');
        }
        
        // Generate unique filename
        $filename = uniqid('post_', true) . '_' . time() . '.' . $file_extension;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Return relative path from main/pages/ directory
            $image_url = 'uploads/posts/' . $filename;
        } else {
            throw new Exception('Failed to upload image. Please check directory permissions.');
        }
    }
    
    // Insert the post
    $stmt = $conn->prepare("INSERT INTO posts (user_id, category, title, content, image_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $category, $title, $content, $image_url);
    $stmt->execute();
    
    $post_id = $conn->insert_id;
    
    // Get the newly created post with user info
    $stmt = $conn->prepare("SELECT p.*, u.name as author_name, u.email as author_email 
                            FROM posts p 
                            JOIN users u ON p.user_id = u.user_id 
                            WHERE p.id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Post created successfully',
        'post' => $post
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>