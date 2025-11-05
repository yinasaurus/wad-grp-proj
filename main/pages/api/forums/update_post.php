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
        throw new Exception('You must be logged in to update a post');
    }
    
    // Get form data (not JSON because of file upload)
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    
    if (!$post_id || empty($category) || empty($title) || empty($content)) {
        throw new Exception('Post ID, category, title, and content are required');
    }
    
    // Verify the post belongs to the user
    $stmt = $conn->prepare("SELECT id, image_url FROM posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Post not found or you do not have permission to edit it');
    }
    
    $existing_post = $result->fetch_assoc();
    $image_url = $existing_post['image_url'];
    
    // Handle image removal (if user wants to remove existing image)
    if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
        // Delete old image if exists
        if ($image_url && file_exists(__DIR__ . '/../../../' . $image_url)) {
            @unlink(__DIR__ . '/../../../' . $image_url);
        }
        $image_url = null; // Set to null to remove image from post
    }
    // Handle image upload (if new image provided)
    else if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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
        
        // Validate image file
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            throw new Exception('Invalid image file. Please upload a valid image.');
        }
        
        // Delete old image if exists
        if ($image_url && file_exists(__DIR__ . '/../../../' . $image_url)) {
            @unlink(__DIR__ . '/../../../' . $image_url);
        }
        
        // Generate unique filename
        $filename = uniqid('post_', true) . '_' . time() . '.' . $file_extension;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $image_url = 'uploads/posts/' . $filename;
        } else {
            throw new Exception('Failed to upload image. Please check directory permissions.');
        }
    }
    
    // Update the post
    // Handle null image_url separately
    if ($image_url === null) {
        $stmt = $conn->prepare("UPDATE posts SET category = ?, title = ?, content = ?, image_url = '', edited = TRUE WHERE id = ?");
        $stmt->bind_param("sssi", $category, $title, $content, $post_id);
        $stmt->execute();
        $image_url = ''; // Set to empty string for response
    } else {
        $stmt = $conn->prepare("UPDATE posts SET category = ?, title = ?, content = ?, image_url = ?, edited = TRUE WHERE id = ?");
        $stmt->bind_param("ssssi", $category, $title, $content, $image_url, $post_id);
        $stmt->execute();
    }
    
    // Get the updated post with user info
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
        'message' => 'Post updated successfully',
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