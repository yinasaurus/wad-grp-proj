<?php
/**
 * notifications.php
 * API endpoint for event notifications
 * Handles: creating notifications, getting notifications, marking as read
 */

session_start();
require_once '../../db_config.php';

ini_set('session.cookie_httponly', '0');
ini_set('session.cookie_samesite', 'Lax');

header('Content-Type: application/json');

$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $conn = getDBConnection();
    if (!$conn || $conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }
    
    // Create notifications table if it doesn't exist
    $createTableQuery = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type ENUM('consumer', 'business') NOT NULL,
        event_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        INDEX idx_user_unread (user_id, user_type, is_read),
        INDEX idx_event (event_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->query($createTableQuery);
    
    if ($action === 'check_upcoming_events' && $method === 'GET') {
        // Check for events happening in 1 day and create notifications
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $userType = $_SESSION['user_type'] ?? 'consumer';
        
        // Get events that start in exactly 1 day (24 hours from now)
        $oneDayFromNow = date('Y-m-d H:i:s', strtotime('+1 day'));
        $oneDayFromNowEnd = date('Y-m-d H:i:s', strtotime('+1 day +1 hour'));
        
        // Get user's registered events
        $query = "SELECT e.id, e.title, e.start_time, e.location, e.organizer_id, e.organizer_type
                  FROM events e
                  INNER JOIN event_registrations er ON e.id = er.event_id
                  WHERE er.user_id = ? 
                  AND er.user_type = ?
                  AND e.start_time >= ? 
                  AND e.start_time < ?
                  AND e.start_time > NOW()";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isss", $userId, $userType, $oneDayFromNow, $oneDayFromNowEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notificationsCreated = 0;
        
        while ($row = $result->fetch_assoc()) {
            $eventId = $row['id'];
            $eventTitle = $row['title'];
            $eventStartTime = $row['start_time'];
            $eventLocation = $row['location'] ?? 'Location TBD';
            
            // Check if 24-hour reminder notification already exists for this event and user
            // We check for the reminder title specifically to avoid duplicates
            $checkStmt = $conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND user_type = ? AND event_id = ? AND title LIKE 'Event Reminder:%'");
            $checkStmt->bind_param("isi", $userId, $userType, $eventId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                // Create 24-hour reminder notification
                $notificationTitle = "Event Reminder: {$eventTitle}";
                $notificationMessage = "Don't forget! Your event '{$eventTitle}' is happening tomorrow at " . date('g:i A', strtotime($eventStartTime)) . ". Location: {$eventLocation}";
                
                $insertStmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, event_id, title, message) VALUES (?, ?, ?, ?, ?)");
                $insertStmt->bind_param("isiss", $userId, $userType, $eventId, $notificationTitle, $notificationMessage);
                $insertStmt->execute();
                $insertStmt->close();
                
                $notificationsCreated++;
            }
            
            $checkStmt->close();
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'notifications_created' => $notificationsCreated,
            'message' => "Checked for upcoming events"
        ]);
        exit;
    }
    
    if ($action === 'get_notifications' && $method === 'GET') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $userType = $_SESSION['user_type'] ?? 'consumer';
        
        $query = "SELECT n.*, e.start_time, e.location
                  FROM notifications n
                  INNER JOIN events e ON n.event_id = e.id
                  WHERE n.user_id = ? AND n.user_type = ?
                  ORDER BY n.created_at DESC
                  LIMIT 50";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $userId, $userType);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => (int)$row['id'],
                'event_id' => (int)$row['event_id'],
                'title' => $row['title'],
                'message' => $row['message'],
                'is_read' => (bool)$row['is_read'],
                'created_at' => $row['created_at'],
                'event_start_time' => $row['start_time'],
                'event_location' => $row['location']
            ];
        }
        
        $stmt->close();
        
        // Count unread notifications
        $countStmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND user_type = ? AND is_read = 0");
        $countStmt->bind_param("is", $userId, $userType);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $unreadCount = $countResult->fetch_assoc()['unread_count'] ?? 0;
        $countStmt->close();
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => (int)$unreadCount
        ]);
        exit;
    }
    
    if ($action === 'mark_read' && $method === 'POST') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $userType = $_SESSION['user_type'] ?? 'consumer';
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $notificationId = $data['notification_id'] ?? null;
        
        if ($notificationId) {
            // Mark specific notification as read
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND user_type = ?");
            $stmt->bind_param("iis", $notificationId, $userId, $userType);
            $stmt->execute();
            $stmt->close();
        } else {
            // Mark all notifications as read
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND user_type = ? AND is_read = 0");
            $stmt->bind_param("is", $userId, $userType);
            $stmt->execute();
            $stmt->close();
        }
        
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
        exit;
    }
    
    // Default: return error
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    
} catch (Exception $e) {
    error_log("Notification API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

