<?php
session_start();
require_once '../../db_config.php';

header('Content-Type: application/json');

// For credentials to work, we need to specify the exact origin, not *
$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get database connection
function getDB() {
    return getDBConnection();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user type
function getCurrentUserType() {
    return $_SESSION['user_type'] ?? null;
}

// Get current business ID (if user is a business)
function getCurrentBusinessId() {
    return $_SESSION['business_id'] ?? null;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_events':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit();
        }
        
        $conn = getDB();
        if (!$conn) {
            error_log("Database connection failed in get_events");
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database connection failed']);
            exit();
        }
        
        try {
            error_log("Fetching events from database");
            // Check if events table exists first
            $tableCheck = $conn->query("SHOW TABLES LIKE 'events'");
            if ($tableCheck->num_rows == 0) {
                error_log("Events table does not exist");
                echo json_encode(['success' => true, 'events' => []]);
                exit();
            }
            
            // Get all events with organizer information
            $query = "SELECT 
                        e.id,
                        e.title,
                        e.description,
                        e.start_time,
                        e.end_time,
                        e.location,
                        e.organizer_id,
                        e.organizer_type,
                        u.name as organizer_name,
                        u.email as organizer_email,
                        b.name as business_name,
                        CASE 
                            WHEN e.organizer_type = 'business' THEN 1
                            ELSE 0
                        END as is_business
                      FROM events e
                      LEFT JOIN users u ON e.organizer_id = u.user_id AND e.organizer_type = 'consumer'
                      LEFT JOIN businesses b ON e.organizer_id = b.id AND e.organizer_type = 'business'
                      ORDER BY e.start_time ASC";
            
            $result = $conn->query($query);
            
            if (!$result) {
                error_log("Query error: " . $conn->error);
                error_log("Query was: " . $query);
                throw new Exception("Query failed: " . $conn->error);
            }
            
            error_log("Query executed successfully, found " . $result->num_rows . " events");
            
            $events = [];
            while ($row = $result->fetch_assoc()) {
                // Use business name if organizer is a business, otherwise use user name or email
                $organizerName = $row['organizer_name'] ?? null;
                if ($row['organizer_type'] === 'business' && !empty($row['business_name'])) {
                    $organizerName = $row['business_name'];
                } elseif (empty($organizerName) && !empty($row['organizer_email'])) {
                    // Fallback to email username if name not available
                    $organizerName = explode('@', $row['organizer_email'])[0];
                } elseif (empty($organizerName)) {
                    // Final fallback if no name or email available
                    $organizerName = 'Unknown Organizer';
                }
                
                $events[] = [
                    'id' => (int)$row['id'],
                    'title' => $row['title'] ?? '',
                    'description' => $row['description'] ?? '',
                    'start_time' => $row['start_time'] ?? '',
                    'end_time' => $row['end_time'] ?? null,
                    'location' => $row['location'] ?? null,
                    'organizer_id' => (int)$row['organizer_id'],
                    'organizer_name' => $organizerName,
                    'isBusiness' => ($row['is_business'] == 1)
                ];
            }
            
            error_log("Processed " . count($events) . " events");
            
            echo json_encode(['success' => true, 'events' => $events]);
        } catch (Exception $e) {
            error_log("Error fetching events: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to fetch events: ' . $e->getMessage()]);
        }
        break;
        
    case 'create_event':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit();
        }
        
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit();
        }
        
        $conn = getDB();
        if (!$conn) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database connection failed']);
            exit();
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception("Invalid JSON input");
            }
            
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $start_time = $input['start_time'] ?? null;
            $end_time = $input['end_time'] ?? null;
            $location = trim($input['location'] ?? '');
            
            if (empty($title) || empty($description) || empty($start_time)) {
                throw new Exception("Title, description, and start time are required");
            }
            
            $userId = getCurrentUserId();
            $userType = getCurrentUserType();
            $businessId = getCurrentBusinessId();
            
            // Determine organizer type and ID
            $organizerType = 'consumer';
            $organizerId = $userId;
            
            if ($userType === 'business' && $businessId) {
                $organizerType = 'business';
                $organizerId = $businessId;
            }
            
            // Validate date/time format
            $startDateTime = date('Y-m-d H:i:s', strtotime($start_time));
            if (!$startDateTime || $startDateTime === '1970-01-01 00:00:00') {
                throw new Exception("Invalid start time format");
            }
            
            $endDateTime = null;
            if (!empty($end_time)) {
                $endDateTime = date('Y-m-d H:i:s', strtotime($end_time));
                if (!$endDateTime || $endDateTime === '1970-01-01 00:00:00') {
                    $endDateTime = null; // If invalid, set to null
                }
            }
            
            // Insert event
            $stmt = $conn->prepare("INSERT INTO events (title, description, start_time, end_time, location, organizer_id, organizer_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("sssssis", $title, $description, $startDateTime, $endDateTime, $location, $organizerId, $organizerType);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $eventId = $conn->insert_id;
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'event_id' => $eventId,
                'message' => 'Event created successfully'
            ]);
        } catch (Exception $e) {
            error_log("Error creating event: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'delete_event':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit();
        }
        
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit();
        }
        
        $conn = getDB();
        if (!$conn) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database connection failed']);
            exit();
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['event_id'])) {
                throw new Exception("Event ID is required");
            }
            
            $eventId = intval($input['event_id']);
            $userId = getCurrentUserId();
            $userType = getCurrentUserType();
            $businessId = getCurrentBusinessId();
            
            // Check if event exists and user has permission to delete
            $query = "SELECT organizer_id, organizer_type FROM events WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $eventId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Event not found");
            }
            
            $event = $result->fetch_assoc();
            $stmt->close();
            
            // Check permission: user must be the organizer or a business partner
            $canDelete = false;
            if ($userType === 'business' && $businessId) {
                // Business partners can delete any event
                $canDelete = true;
            } elseif ($event['organizer_type'] === 'consumer' && $event['organizer_id'] == $userId) {
                $canDelete = true;
            } elseif ($event['organizer_type'] === 'business' && $event['organizer_id'] == $businessId) {
                $canDelete = true;
            }
            
            if (!$canDelete) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permission denied']);
                exit();
            }
            
            // Delete event
            $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
            $stmt->bind_param("i", $eventId);
            
            if (!$stmt->execute()) {
                throw new Exception("Delete failed: " . $stmt->error);
            }
            
            $stmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
        } catch (Exception $e) {
            error_log("Error deleting event: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'register_event':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit();
        }
        
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit();
        }
        
        $conn = getDB();
        if (!$conn) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database connection failed']);
            exit();
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['event_id'])) {
                throw new Exception("Event ID is required");
            }
            
            $eventId = intval($input['event_id']);
            $userId = getCurrentUserId();
            $userType = getCurrentUserType();
            $businessId = getCurrentBusinessId();
            
            // Check if event exists
            $checkEvent = $conn->prepare("SELECT id FROM events WHERE id = ?");
            $checkEvent->bind_param("i", $eventId);
            $checkEvent->execute();
            $eventResult = $checkEvent->get_result();
            
            if ($eventResult->num_rows === 0) {
                throw new Exception("Event not found");
            }
            $checkEvent->close();
            
            // Check if already registered
            $checkReg = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ? AND user_type = ?");
            $checkReg->bind_param("iis", $eventId, $userId, $userType);
            $checkReg->execute();
            $regResult = $checkReg->get_result();
            
            if ($regResult->num_rows > 0) {
                throw new Exception("You are already registered for this event");
            }
            $checkReg->close();
            
            // Insert registration
            $stmt = $conn->prepare("INSERT INTO event_registrations (event_id, user_id, user_type, business_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $eventId, $userId, $userType, $businessId);
            
            if (!$stmt->execute()) {
                throw new Exception("Registration failed: " . $stmt->error);
            }
            
            $stmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Successfully registered for event']);
        } catch (Exception $e) {
            error_log("Error registering for event: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'get_my_registrations':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit();
        }
        
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit();
        }
        
        $conn = getDB();
        if (!$conn) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database connection failed']);
            exit();
        }
        
        try {
            $userId = getCurrentUserId();
            $userType = getCurrentUserType();
            
            // Get all registrations for this user with event details
            $query = "SELECT 
                        er.id as registration_id,
                        er.registered_at,
                        e.id as event_id,
                        e.title,
                        e.description,
                        e.start_time,
                        e.end_time,
                        e.location,
                        e.organizer_id,
                        e.organizer_type,
                        u.name as organizer_name,
                        u.email as organizer_email,
                        b.name as business_name,
                        CASE 
                            WHEN e.organizer_type = 'business' THEN 1
                            ELSE 0
                        END as is_business
                      FROM event_registrations er
                      INNER JOIN events e ON er.event_id = e.id
                      LEFT JOIN users u ON e.organizer_id = u.user_id AND e.organizer_type = 'consumer'
                      LEFT JOIN businesses b ON e.organizer_id = b.id AND e.organizer_type = 'business'
                      WHERE er.user_id = ? AND er.user_type = ?
                      ORDER BY e.start_time ASC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $userId, $userType);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $registrations = [];
            while ($row = $result->fetch_assoc()) {
                // Use business name if organizer is a business, otherwise use user name or email
                $organizerName = $row['organizer_name'] ?? null;
                if ($row['organizer_type'] === 'business' && !empty($row['business_name'])) {
                    $organizerName = $row['business_name'];
                } elseif (empty($organizerName) && !empty($row['organizer_email'])) {
                    $organizerName = explode('@', $row['organizer_email'])[0];
                } elseif (empty($organizerName)) {
                    $organizerName = 'Unknown Organizer';
                }
                
                $registrations[] = [
                    'registration_id' => (int)$row['registration_id'],
                    'event_id' => (int)$row['event_id'],
                    'title' => $row['title'] ?? '',
                    'description' => $row['description'] ?? '',
                    'start_time' => $row['start_time'] ?? '',
                    'end_time' => $row['end_time'] ?? null,
                    'location' => $row['location'] ?? null,
                    'organizer_id' => (int)$row['organizer_id'],
                    'organizer_name' => $organizerName,
                    'isBusiness' => ($row['is_business'] == 1),
                    'registered_at' => $row['registered_at'] ?? ''
                ];
            }
            
            $stmt->close();
            
            echo json_encode(['success' => true, 'registrations' => $registrations]);
        } catch (Exception $e) {
            error_log("Error fetching registrations: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to fetch registrations: ' . $e->getMessage()]);
        }
        break;
        
    case 'cancel_registration':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit();
        }
        
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit();
        }
        
        $conn = getDB();
        if (!$conn) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database connection failed']);
            exit();
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['registration_id'])) {
                throw new Exception("Registration ID is required");
            }
            
            $registrationId = intval($input['registration_id']);
            $userId = getCurrentUserId();
            $userType = getCurrentUserType();
            
            // Verify ownership
            $checkReg = $conn->prepare("SELECT id FROM event_registrations WHERE id = ? AND user_id = ? AND user_type = ?");
            $checkReg->bind_param("iis", $registrationId, $userId, $userType);
            $checkReg->execute();
            $regResult = $checkReg->get_result();
            
            if ($regResult->num_rows === 0) {
                throw new Exception("Registration not found or you don't have permission to cancel it");
            }
            $checkReg->close();
            
            // Delete registration
            $stmt = $conn->prepare("DELETE FROM event_registrations WHERE id = ?");
            $stmt->bind_param("i", $registrationId);
            
            if (!$stmt->execute()) {
                throw new Exception("Cancellation failed: " . $stmt->error);
            }
            
            $stmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Registration cancelled successfully']);
        } catch (Exception $e) {
            error_log("Error cancelling registration: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'check_registration':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit();
        }
        
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit();
        }
        
        $conn = getDB();
        if (!$conn) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database connection failed']);
            exit();
        }
        
        try {
            $eventId = intval($_GET['event_id'] ?? 0);
            if (!$eventId) {
                throw new Exception("Event ID is required");
            }
            
            $userId = getCurrentUserId();
            $userType = getCurrentUserType();
            
            $stmt = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ? AND user_type = ?");
            $stmt->bind_param("iis", $eventId, $userId, $userType);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $isRegistered = $result->num_rows > 0;
            $stmt->close();
            
            echo json_encode(['success' => true, 'isRegistered' => $isRegistered]);
        } catch (Exception $e) {
            error_log("Error checking registration: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

