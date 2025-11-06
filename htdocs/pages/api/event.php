<?php
// Set error handling first
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering immediately to catch any output
ob_start();

session_start();

// Configure session cookies for InfinityFree compatibility
ini_set('session.cookie_httponly', '0');
ini_set('session.cookie_samesite', 'Lax');

// Set JSON header immediately to prevent HTML output
header('Content-Type: application/json; charset=utf-8');

$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function getDB() {
    return getDBConnection();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserType() {
    return $_SESSION['user_type'] ?? null;
}

function getCurrentBusinessId() {
    // First check session
    if (isset($_SESSION['business_id'])) {
        return $_SESSION['business_id'];
    }
    
    // If not in session but user is a business, fetch from database
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'business') {
        $conn = getDB();
        if ($conn) {
            $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ? LIMIT 1");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $businessId = (int)$row['id'];
                $_SESSION['business_id'] = $businessId; // Cache in session
                $stmt->close();
                return $businessId;
            }
            $stmt->close();
        }
    }
    
    return null;
}

// Check if db_config.php exists and require it
if (!file_exists('../../db_config.php')) {
    ob_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Database configuration file not found']);
    exit;
}

require_once '../../db_config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Wrap entire switch in try-catch for fatal errors
try {
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
            $tableCheck = $conn->query("SHOW TABLES LIKE 'events'");
            if ($tableCheck->num_rows == 0) {
                error_log("Events table does not exist");
                echo json_encode(['success' => true, 'events' => []]);
                exit();
            }
            
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
                // Determine organizer name based on organizer type
                $organizerName = null;
                
                if ($row['organizer_type'] === 'business') {
                    // For business events, always query businesses table directly
                    $businessId = (int)$row['organizer_id'];
                    if ($businessId > 0) {
                        $businessStmt = $conn->prepare("SELECT name FROM businesses WHERE id = ?");
                        if ($businessStmt) {
                            $businessStmt->bind_param("i", $businessId);
                            $businessStmt->execute();
                            $businessResult = $businessStmt->get_result();
                            if ($businessResult->num_rows > 0) {
                                $businessRow = $businessResult->fetch_assoc();
                                $organizerName = $businessRow['name'];
                            }
                            $businessStmt->close();
                        }
                    }
                    
                    // Fallback to business_name from JOIN if direct query didn't work
                    if (empty($organizerName) && !empty($row['business_name'])) {
                    $organizerName = $row['business_name'];
                    }
                    
                    // If still not found, use fallback
                    if (empty($organizerName)) {
                        error_log("Business name not found for business_id: " . $businessId . ", organizer_id: " . $row['organizer_id']);
                        // Try one more time with a different query
                        $fallbackStmt = $conn->prepare("SELECT name FROM businesses WHERE id = ? LIMIT 1");
                        if ($fallbackStmt) {
                            $fallbackStmt->bind_param("i", $businessId);
                            $fallbackStmt->execute();
                            $fallbackResult = $fallbackStmt->get_result();
                            if ($fallbackResult->num_rows > 0) {
                                $fallbackRow = $fallbackResult->fetch_assoc();
                                $organizerName = $fallbackRow['name'];
                            }
                            $fallbackStmt->close();
                        }
                        
                        if (empty($organizerName)) {
                            $organizerName = 'Unknown Business';
                        }
                    }
                } else {
                    // For consumer events, query users table directly
                    $userId = (int)$row['organizer_id'];
                    if ($userId > 0) {
                        $userStmt = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
                        if ($userStmt) {
                            $userStmt->bind_param("i", $userId);
                            if ($userStmt->execute()) {
                                $userResult = $userStmt->get_result();
                                if ($userResult->num_rows > 0) {
                                    $userRow = $userResult->fetch_assoc();
                                    // Use name if available, otherwise use email username
                                    if (!empty($userRow['name'])) {
                                        $organizerName = $userRow['name'];
                                    } elseif (!empty($userRow['email'])) {
                                        $organizerName = explode('@', $userRow['email'])[0];
                                    }
                                } else {
                                    error_log("No user found for user_id: " . $userId . ", organizer_id: " . $row['organizer_id']);
                                }
                            } else {
                                error_log("Error executing user query: " . $userStmt->error);
                            }
                            $userStmt->close();
                        } else {
                            error_log("Error preparing user query: " . $conn->error);
                        }
                    }
                    
                    // Fallback to organizer_name from JOIN if direct query didn't work
                    if (empty($organizerName) && !empty($row['organizer_name'])) {
                        $organizerName = $row['organizer_name'];
                    } elseif (empty($organizerName) && !empty($row['organizer_email'])) {
                        $organizerName = explode('@', $row['organizer_email'])[0];
                    }
                    
                    // If still not found, use fallback
                    if (empty($organizerName)) {
                        error_log("Consumer name not found for user_id: " . $userId . ", organizer_id: " . $row['organizer_id'] . ", organizer_type: " . $row['organizer_type']);
                        // Try one more time with a simpler query
                        $fallbackStmt = $conn->prepare("SELECT name, email FROM users WHERE user_id = ? LIMIT 1");
                        if ($fallbackStmt) {
                            $fallbackStmt->bind_param("i", $userId);
                            if ($fallbackStmt->execute()) {
                                $fallbackResult = $fallbackStmt->get_result();
                                if ($fallbackResult->num_rows > 0) {
                                    $fallbackRow = $fallbackResult->fetch_assoc();
                                    if (!empty($fallbackRow['name'])) {
                                        $organizerName = $fallbackRow['name'];
                                    } elseif (!empty($fallbackRow['email'])) {
                                        $organizerName = explode('@', $fallbackRow['email'])[0];
                                    }
                                }
                            }
                            $fallbackStmt->close();
                        }
                        
                        // Final fallback
                        if (empty($organizerName)) {
                            $organizerName = 'Unknown Organizer';
                        }
                    }
                }
                
                $events[] = [
                    'id' => (int)$row['id'],
                    'title' => $row['title'] ?? '',
                    'description' => $row['description'] ?? '',
                    'start_time' => $row['start_time'] ?? '',
                    'end_time' => $row['end_time'] ?? null,
                    'location' => $row['location'] ?? null,
                    'organizer_id' => (int)$row['organizer_id'],
                    'organizer_type' => $row['organizer_type'] ?? 'consumer',
                    'organizer_name' => $organizerName,
                    'isBusiness' => ($row['is_business'] == 1)
                ];
            }
            
            error_log("Processed " . count($events) . " events");
            
            ob_clean();
            echo json_encode(['success' => true, 'events' => $events]);
            exit();
        } catch (Exception $e) {
            error_log("Error fetching events: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            ob_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to fetch events: ' . $e->getMessage()]);
            exit();
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
            
            $organizerType = 'consumer';
            $organizerId = $userId;
            
            if ($userType === 'business') {
                // Ensure business_id is fetched if not in session
                if (!$businessId) {
                    $businessId = getCurrentBusinessId();
                }
                
                if ($businessId) {
                $organizerType = 'business';
                    $organizerId = (int)$businessId;
                } else {
                    error_log("Warning: Business user creating event but business_id not found. User ID: " . $userId);
                }
            }
            $startDateTime = date('Y-m-d H:i:s', strtotime($start_time));
            if (!$startDateTime || $startDateTime === '1970-01-01 00:00:00') {
                throw new Exception("Invalid start time format");
            }
            $startTimestamp = strtotime($startDateTime);
            $currentTimestamp = time();
            
            // Events can only be created one week (7 days) later
            $oneWeekLater = $currentTimestamp + (7 * 24 * 60 * 60); // 7 days in seconds
            
            if ($startTimestamp < $oneWeekLater) {
                throw new Exception("Event start time must be at least one week (7 days) from today. Please select a date at least 7 days in the future.");
            }
            
            $endDateTime = null;
            if (!empty($end_time)) {
                $endDateTime = date('Y-m-d H:i:s', strtotime($end_time));
                if (!$endDateTime || $endDateTime === '1970-01-01 00:00:00') {
                    $endDateTime = null; 
                } else {
                    $endTimestamp = strtotime($endDateTime);
                    if ($endTimestamp <= $startTimestamp) {
                        throw new Exception("Event end time must be after the start time.");
                    }
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO events (title, description, start_time, end_time, location, organizer_id, organizer_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            // organizer_type is a string (ENUM), not an integer
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
        
    case 'update_event':
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
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $start_time = $input['start_time'] ?? '';
            $end_time = $input['end_time'] ?? null;
            $location = trim($input['location'] ?? '') ?: null;
            $carbon_offset = isset($input['carbon_offset']) && $input['carbon_offset'] !== null && $input['carbon_offset'] !== '' ? floatval($input['carbon_offset']) : null;
            
            if (empty($title) || empty($description) || empty($start_time)) {
                throw new Exception("Title, description, and start time are required");
            }
            
            $userId = getCurrentUserId();
            $userType = getCurrentUserType();
            $businessId = getCurrentBusinessId();
            
            // Check if user owns this event
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
            
            $canEdit = false;
            if ($event['organizer_type'] === 'consumer' && $event['organizer_id'] == $userId && $userType === 'consumer') {
                $canEdit = true;
            } elseif ($event['organizer_type'] === 'business' && $event['organizer_id'] == $businessId && $userType === 'business' && $businessId) {
                $canEdit = true;
            }
            
            if (!$canEdit) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permission denied']);
                exit();
            }
            
            $startDateTime = date('Y-m-d H:i:s', strtotime($start_time));
            if (!$startDateTime || $startDateTime === '1970-01-01 00:00:00') {
                throw new Exception("Invalid start time format");
            }
            $startTimestamp = strtotime($startDateTime);
            $currentTimestamp = time();
            
            // Events can only be scheduled at least one week (7 days) from today
            $oneWeekLater = $currentTimestamp + (7 * 24 * 60 * 60); // 7 days in seconds
            
            if ($startTimestamp < $oneWeekLater) {
                throw new Exception("Event start time must be at least one week (7 days) from today. Please select a date at least 7 days in the future.");
            }
            
            $endDateTime = null;
            if (!empty($end_time)) {
                $endDateTime = date('Y-m-d H:i:s', strtotime($end_time));
                if (!$endDateTime || $endDateTime === '1970-01-01 00:00:00') {
                    $endDateTime = null; 
                } else {
                    $endTimestamp = strtotime($endDateTime);
                    if ($endTimestamp <= $startTimestamp) {
                        throw new Exception("Event end time must be after the start time.");
                    }
                }
            }
            
            $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, start_time = ?, end_time = ?, location = ? WHERE id = ?");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("sssssi", $title, $description, $startDateTime, $endDateTime, $location, $eventId);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Event updated successfully'
            ]);
        } catch (Exception $e) {
            error_log("Error updating event: " . $e->getMessage());
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
            
            $canDelete = false;
            if ($event['organizer_type'] === 'consumer' && $event['organizer_id'] == $userId && $userType === 'consumer') {
                $canDelete = true;
            } elseif ($event['organizer_type'] === 'business' && $event['organizer_id'] == $businessId && $userType === 'business' && $businessId) {
                $canDelete = true;
            }
            
            if (!$canDelete) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Permission denied']);
                exit();
            }
            
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
            
            // Check if event exists and get organizer info
            $checkEvent = $conn->prepare("SELECT id, organizer_id, organizer_type FROM events WHERE id = ?");
            $checkEvent->bind_param("i", $eventId);
            $checkEvent->execute();
            $eventResult = $checkEvent->get_result();
            
            if ($eventResult->num_rows === 0) {
                $checkEvent->close();
                throw new Exception("Event not found");
            }
            
            $eventData = $eventResult->fetch_assoc();
            $checkEvent->close();
            
            // Prevent users from signing up for their own events
            // For consumer events
            if ($eventData['organizer_type'] === 'consumer' && $eventData['organizer_id'] == $userId && $userType === 'consumer') {
                throw new Exception("You cannot sign up for your own event");
            }
            
            // For business events, check if the business_id matches
            if ($eventData['organizer_type'] === 'business') {
                // If business_id is not in session, try to fetch it
                if (!$businessId && $userType === 'business') {
                    $businessId = getCurrentBusinessId();
                }
                
                // Compare organizer_id with business_id
                $organizerBusinessId = (int)$eventData['organizer_id'];
                $currentBusinessId = $businessId ? (int)$businessId : 0;
                
                if ($organizerBusinessId == $currentBusinessId && $userType === 'business' && $currentBusinessId > 0) {
                    throw new Exception("You cannot sign up for your own event");
                }
            }
            
            // Check if event_registrations table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'event_registrations'");
            if ($tableCheck->num_rows == 0) {
                // Create event_registrations table if it doesn't exist
                $createTableQuery = "CREATE TABLE IF NOT EXISTS event_registrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    event_id INT NOT NULL,
                    user_id INT NOT NULL,
                    user_type ENUM('consumer', 'business') NOT NULL DEFAULT 'consumer',
                    business_id INT DEFAULT NULL,
                    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_registration (event_id, user_id, user_type, business_id),
                    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                    INDEX idx_event_id (event_id),
                    INDEX idx_user_id (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $conn->query($createTableQuery);
            } else {
                // Check if id column exists
                $columnCheck = $conn->query("SHOW COLUMNS FROM event_registrations LIKE 'id'");
                if ($columnCheck->num_rows == 0) {
                    // Check if there's already a primary key
                    $pkCheck = $conn->query("SHOW KEYS FROM event_registrations WHERE Key_name = 'PRIMARY'");
                    if ($pkCheck->num_rows == 0) {
                        // Add id column if it doesn't exist and there's no primary key
                        $alterResult = $conn->query("ALTER TABLE event_registrations ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST");
                        if (!$alterResult) {
                            error_log("Failed to add id column: " . $conn->error);
                        }
                    } else {
                        // If there's already a primary key, just add id as a regular column
                        $alterResult = $conn->query("ALTER TABLE event_registrations ADD COLUMN id INT AUTO_INCREMENT UNIQUE FIRST");
                        if (!$alterResult) {
                            error_log("Failed to add id column: " . $conn->error);
                        }
                    }
                }
            }
            
            // Check for existing registration - use a query that doesn't require id column
            // First, get all columns to see what we can use
            $columnsResult = $conn->query("SHOW COLUMNS FROM event_registrations");
            $hasIdColumn = false;
            $hasEventIdColumn = false;
            $hasUserIdColumn = false;
            $hasUserTypeColumn = false;
            $columnNames = [];
            if ($columnsResult) {
                while ($col = $columnsResult->fetch_assoc()) {
                    $columnNames[] = $col['Field'];
                    if ($col['Field'] === 'id') {
                        $hasIdColumn = true;
                    }
                    if ($col['Field'] === 'event_id') {
                        $hasEventIdColumn = true;
                    }
                    if ($col['Field'] === 'user_id') {
                        $hasUserIdColumn = true;
                    }
                    if ($col['Field'] === 'user_type') {
                        $hasUserTypeColumn = true;
                    }
                }
            }
            
            // Check for existing registration based on available columns
            if (!$hasUserIdColumn || !$hasUserTypeColumn) {
                throw new Exception("event_registrations table structure is invalid: missing required columns (user_id, user_type). Available columns: " . implode(', ', $columnNames));
            }
            
            if ($hasEventIdColumn) {
                // event_id column exists - use it to check for existing registration
                // Don't use id column in SELECT, just check if row exists
                $checkReg = $conn->prepare("SELECT event_id FROM event_registrations WHERE event_id = ? AND user_id = ? AND user_type = ?");
            } else {
                // event_id column doesn't exist - can't check for existing registration
                throw new Exception("event_registrations table structure is invalid: missing event_id column. Available columns: " . implode(', ', $columnNames));
            }
            
            if (!$checkReg) {
                $errorMsg = $conn->error;
                // If prepare fails with "Unknown column", provide helpful error
                if (strpos($errorMsg, "Unknown column") !== false) {
                    throw new Exception("Column check failed during prepare: " . $errorMsg . ". Available columns: " . implode(', ', $columnNames));
                }
                throw new Exception("Failed to prepare check registration query: " . $errorMsg);
            }
            $checkReg->bind_param("iis", $eventId, $userId, $userType);
            if (!$checkReg->execute()) {
                $errorMsg = $checkReg->error;
                $checkReg->close();
                // If execute fails with "Unknown column", provide helpful error
                if (strpos($errorMsg, "Unknown column") !== false) {
                    throw new Exception("Column check failed during execute: " . $errorMsg . ". Available columns: " . implode(', ', $columnNames));
                }
                throw new Exception("Failed to execute check registration query: " . $errorMsg);
            }
            $regResult = $checkReg->get_result();
            
            if ($regResult->num_rows > 0) {
                $checkReg->close();
                throw new Exception("You are already registered for this event");
            }
            $checkReg->close();
            
            // Prepare business_id - set to NULL if not available
            $businessIdForInsert = ($userType === 'business' && $businessId) ? $businessId : null;
            
            $stmt = $conn->prepare("INSERT INTO event_registrations (event_id, user_id, user_type, business_id) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Failed to prepare insert statement: " . $conn->error);
            }
            
            $stmt->bind_param("iisi", $eventId, $userId, $userType, $businessIdForInsert);
            
            if (!$stmt->execute()) {
                throw new Exception("Registration failed: " . $stmt->error);
            }
            
            $stmt->close();
            
            // Create immediate notification for event registration
            // First, get event details
            $eventStmt = $conn->prepare("SELECT title, start_time, location FROM events WHERE id = ?");
            $eventStmt->bind_param("i", $eventId);
            $eventStmt->execute();
            $eventResult = $eventStmt->get_result();
            
            if ($eventResult->num_rows > 0) {
                $event = $eventResult->fetch_assoc();
                $eventTitle = $event['title'];
                $eventStartTime = $event['start_time'];
                $eventLocation = $event['location'] ?? 'Location TBD';
                
                // Create immediate notification
                $notificationTitle = "Event Registration Confirmed: {$eventTitle}";
                $notificationMessage = "You've successfully registered for '{$eventTitle}' on " . date('F j, Y', strtotime($eventStartTime)) . " at " . date('g:i A', strtotime($eventStartTime)) . ". Location: {$eventLocation}";
                
                // Check if notifications table exists, if not create it
                $tableCheck = $conn->query("SHOW TABLES LIKE 'notifications'");
                if ($tableCheck->num_rows == 0) {
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
                }
                
                // Check if immediate notification already exists (to prevent duplicates)
                $checkNotifStmt = $conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND user_type = ? AND event_id = ? AND title LIKE 'Event Registration Confirmed:%'");
                $checkNotifStmt->bind_param("isi", $userId, $userType, $eventId);
                $checkNotifStmt->execute();
                $checkNotifResult = $checkNotifStmt->get_result();
                
                if ($checkNotifResult->num_rows === 0) {
                    // Create immediate notification
                    $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, event_id, title, message) VALUES (?, ?, ?, ?, ?)");
                    $notifStmt->bind_param("isiss", $userId, $userType, $eventId, $notificationTitle, $notificationMessage);
                    $notifStmt->execute();
                    $notifStmt->close();
                }
                $checkNotifStmt->close();
            }
            $eventStmt->close();
            
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
            
            // Check what columns exist in event_registrations table
            $columnsResult = $conn->query("SHOW COLUMNS FROM event_registrations");
            $hasIdColumn = false;
            $hasEventIdColumn = false;
            if ($columnsResult) {
                while ($col = $columnsResult->fetch_assoc()) {
                    if ($col['Field'] === 'id') {
                        $hasIdColumn = true;
                    }
                    if ($col['Field'] === 'event_id') {
                        $hasEventIdColumn = true;
                    }
                }
            }
            
            // Build query based on what columns exist
            if ($hasIdColumn && $hasEventIdColumn) {
                // Both id and event_id exist - use id as registration_id
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
            } elseif ($hasIdColumn && !$hasEventIdColumn) {
                // Only id exists - need to find another way to join with events
                // This shouldn't happen, but handle it
                throw new Exception("event_registrations table has id but no event_id column - cannot join with events table");
            } elseif (!$hasIdColumn && $hasEventIdColumn) {
                // Only event_id exists - use event_id as registration_id
                $query = "SELECT 
                            er.event_id as registration_id,
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
            } else {
                // Neither id nor event_id exists - this shouldn't happen
                throw new Exception("event_registrations table structure is invalid: missing both id and event_id columns");
            }
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $userId, $userType);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $registrations = [];
            while ($row = $result->fetch_assoc()) {
                // Determine organizer name based on organizer type
                $organizerName = null;
              
                if ($row['organizer_type'] === 'business') {
                    // For business events, use business_name first
                    if (!empty($row['business_name'])) {
                    $organizerName = $row['business_name'];
                    } else {
                        // If business_name is not found, try to get it directly from businesses table
                        $businessId = (int)$row['organizer_id'];
                        $businessStmt = $conn->prepare("SELECT name FROM businesses WHERE id = ?");
                        if ($businessStmt) {
                            $businessStmt->bind_param("i", $businessId);
                            $businessStmt->execute();
                            $businessResult = $businessStmt->get_result();
                            if ($businessResult->num_rows > 0) {
                                $businessRow = $businessResult->fetch_assoc();
                                $organizerName = $businessRow['name'];
                            }
                            $businessStmt->close();
                        }
                        
                        // If still not found, use fallback
                        if (empty($organizerName)) {
                            $organizerName = 'Unknown Business';
                        }
                    }
                } else {
                    // For consumer events, query users table directly
                    $userId = (int)$row['organizer_id'];
                    if ($userId > 0) {
                        $userStmt = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
                        if ($userStmt) {
                            $userStmt->bind_param("i", $userId);
                            if ($userStmt->execute()) {
                                $userResult = $userStmt->get_result();
                                if ($userResult->num_rows > 0) {
                                    $userRow = $userResult->fetch_assoc();
                                    // Use name if available, otherwise use email username
                                    if (!empty($userRow['name'])) {
                                        $organizerName = $userRow['name'];
                                    } elseif (!empty($userRow['email'])) {
                                        $organizerName = explode('@', $userRow['email'])[0];
                                    }
                                } else {
                                    error_log("No user found for user_id: " . $userId . ", organizer_id: " . $row['organizer_id']);
                                }
                            } else {
                                error_log("Error executing user query: " . $userStmt->error);
                            }
                            $userStmt->close();
                        } else {
                            error_log("Error preparing user query: " . $conn->error);
                        }
                    }
                    
                    // Fallback to organizer_name from JOIN if direct query didn't work
                    if (empty($organizerName) && !empty($row['organizer_name'])) {
                        $organizerName = $row['organizer_name'];
                    } elseif (empty($organizerName) && !empty($row['organizer_email'])) {
                        $organizerName = explode('@', $row['organizer_email'])[0];
                    }
                    
                    // If still not found, use fallback
                    if (empty($organizerName)) {
                        error_log("Consumer name not found for user_id: " . $userId . ", organizer_id: " . $row['organizer_id'] . ", organizer_type: " . $row['organizer_type']);
                        // Try one more time with a simpler query
                        $fallbackStmt = $conn->prepare("SELECT name, email FROM users WHERE user_id = ? LIMIT 1");
                        if ($fallbackStmt) {
                            $fallbackStmt->bind_param("i", $userId);
                            if ($fallbackStmt->execute()) {
                                $fallbackResult = $fallbackStmt->get_result();
                                if ($fallbackResult->num_rows > 0) {
                                    $fallbackRow = $fallbackResult->fetch_assoc();
                                    if (!empty($fallbackRow['name'])) {
                                        $organizerName = $fallbackRow['name'];
                                    } elseif (!empty($fallbackRow['email'])) {
                                        $organizerName = explode('@', $fallbackRow['email'])[0];
                                    }
                                }
                            }
                            $fallbackStmt->close();
                        }
                        
                        // Final fallback
                        if (empty($organizerName)) {
                            $organizerName = 'Unknown Organizer';
                        }
                    }
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
            
            ob_clean();
            echo json_encode(['success' => true, 'registrations' => $registrations]);
            exit();
        } catch (Exception $e) {
            error_log("Error fetching registrations: " . $e->getMessage());
            ob_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to fetch registrations: ' . $e->getMessage()]);
            exit();
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
            
            // Check what columns exist in event_registrations table
            $columnsResult = $conn->query("SHOW COLUMNS FROM event_registrations");
            $hasIdColumn = false;
            $hasEventIdColumn = false;
            $hasUserIdColumn = false;
            $hasUserTypeColumn = false;
            $columnNames = [];
            if ($columnsResult) {
                while ($col = $columnsResult->fetch_assoc()) {
                    $columnNames[] = $col['Field'];
                    if ($col['Field'] === 'id') {
                        $hasIdColumn = true;
                    }
                    if ($col['Field'] === 'event_id') {
                        $hasEventIdColumn = true;
                    }
                    if ($col['Field'] === 'user_id') {
                        $hasUserIdColumn = true;
                    }
                    if ($col['Field'] === 'user_type') {
                        $hasUserTypeColumn = true;
                    }
                }
            }
            
            // Log available columns for debugging
            error_log("event_registrations columns: " . implode(', ', $columnNames));
            
            // Get event_id from input (frontend should provide it)
            $eventId = $input['event_id'] ?? null;
            
            // Get registration details and delete based on available columns
            if ($hasIdColumn) {
                // If id column exists, use it
                if ($hasEventIdColumn) {
                    // Both id and event_id exist - use id to find and delete
                    $checkReg = $conn->prepare("SELECT id, event_id FROM event_registrations WHERE id = ? AND user_id = ? AND user_type = ?");
                    if (!$checkReg) {
                        throw new Exception("Failed to prepare check registration query: " . $conn->error);
                    }
                    $checkReg->bind_param("iis", $registrationId, $userId, $userType);
                    if (!$checkReg->execute()) {
                        throw new Exception("Failed to execute check registration query: " . $checkReg->error);
                    }
                    $regResult = $checkReg->get_result();
                    
                    if ($regResult->num_rows === 0) {
                        $checkReg->close();
                        throw new Exception("Registration not found or you don't have permission to cancel it");
                    }
                    
                    $regData = $regResult->fetch_assoc();
                    $eventId = $regData['event_id'] ?? $eventId; // Use from query result if available
                    $checkReg->close();
                    
                    // Delete the registration using id
                    $stmt = $conn->prepare("DELETE FROM event_registrations WHERE id = ?");
                    if (!$stmt) {
                        throw new Exception("Failed to prepare delete statement: " . $conn->error);
                    }
                    $stmt->bind_param("i", $registrationId);
                } else {
                    // id exists but event_id doesn't - use id only
            $checkReg = $conn->prepare("SELECT id FROM event_registrations WHERE id = ? AND user_id = ? AND user_type = ?");
                    if (!$checkReg) {
                        throw new Exception("Failed to prepare check registration query: " . $conn->error);
                    }
            $checkReg->bind_param("iis", $registrationId, $userId, $userType);
                    if (!$checkReg->execute()) {
                        throw new Exception("Failed to execute check registration query: " . $checkReg->error);
                    }
            $regResult = $checkReg->get_result();
            
            if ($regResult->num_rows === 0) {
                        $checkReg->close();
                throw new Exception("Registration not found or you don't have permission to cancel it");
            }
                    
            $checkReg->close();
            
                    // Delete the registration using id
            $stmt = $conn->prepare("DELETE FROM event_registrations WHERE id = ?");
                    if (!$stmt) {
                        throw new Exception("Failed to prepare delete statement: " . $conn->error);
                    }
            $stmt->bind_param("i", $registrationId);
                }
            } else {
                // id column doesn't exist - must use user_id and user_type
                if (!$hasUserIdColumn || !$hasUserTypeColumn) {
                    throw new Exception("event_registrations table structure is invalid: missing required columns. Available columns: " . implode(', ', $columnNames));
                }
                
                // Use event_id if available, otherwise use registration_id as event_id
                if (!$eventId) {
                    $eventId = $registrationId; // Assume registration_id is event_id
                }
                
                // If event_id column exists, use it
                if ($hasEventIdColumn) {
                    // Verify the registration exists using event_id, user_id, and user_type
                    // But first, double-check that event_id column actually exists by trying to prepare
                    try {
                        $checkReg = $conn->prepare("SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ? AND user_type = ?");
                        if (!$checkReg) {
                            // If prepare fails, event_id column probably doesn't exist despite the check
                            throw new Exception("Failed to prepare check registration query (event_id column may not exist): " . $conn->error);
                        }
                        $checkReg->bind_param("iis", $eventId, $userId, $userType);
                        if (!$checkReg->execute()) {
                            $errorMsg = $checkReg->error;
                            $checkReg->close();
                            // If execute fails with "Unknown column", event_id doesn't exist
                            if (strpos($errorMsg, "Unknown column") !== false) {
                                throw new Exception("event_id column does not exist in event_registrations table. Available columns: " . implode(', ', $columnNames));
                            }
                            throw new Exception("Failed to execute check registration query: " . $errorMsg);
                        }
                        $regResult = $checkReg->get_result();
                        
                        if ($regResult->num_rows === 0) {
                            $checkReg->close();
                            throw new Exception("Registration not found or you don't have permission to cancel it");
                        }
                        
                        $checkReg->close();
                        
                        // Delete the registration using event_id, user_id, and user_type
                        $stmt = $conn->prepare("DELETE FROM event_registrations WHERE event_id = ? AND user_id = ? AND user_type = ?");
                        if (!$stmt) {
                            // If prepare fails, event_id column probably doesn't exist
                            if (strpos($conn->error, "Unknown column") !== false) {
                                throw new Exception("event_id column does not exist in event_registrations table. Available columns: " . implode(', ', $columnNames));
                            }
                            throw new Exception("Failed to prepare delete statement: " . $conn->error);
                        }
                        $stmt->bind_param("iis", $eventId, $userId, $userType);
                        if (!$stmt->execute()) {
                            $errorMsg = $stmt->error;
                            $stmt->close();
                            // If execute fails with "Unknown column", event_id doesn't exist
                            if (strpos($errorMsg, "Unknown column") !== false) {
                                throw new Exception("event_id column does not exist in event_registrations table. Available columns: " . implode(', ', $columnNames));
                            }
                            throw new Exception("Failed to execute delete statement: " . $errorMsg);
                        }
                    } catch (Exception $e) {
                        // If event_id column doesn't actually exist, check if we can use only user_id and user_type
                        // But we can't uniquely identify which registration to delete without event_id
                        // So we'll throw an error asking the user to provide event_id or fix the table structure
                        if (strpos($e->getMessage(), "Unknown column") !== false || strpos($e->getMessage(), "event_id column") !== false) {
                            throw new Exception("Cannot cancel registration: event_id column does not exist in event_registrations table. Available columns: " . implode(', ', $columnNames) . ". Please ensure the table has an event_id column or use the id column.");
                        }
                        throw $e;
                    }
                } else {
                    // Neither id nor event_id exists - can only delete by user_id and user_type
                    // But we need to identify which registration to delete
                    // Since we don't have event_id, we'll need to use registration_id as a way to identify
                    // This is a fallback - ideally the table should have event_id
                    throw new Exception("event_registrations table structure is invalid: missing both id and event_id columns. Cannot uniquely identify registration. Available columns: " . implode(', ', $columnNames));
                }
            }
            
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
            
            // Check what columns exist in event_registrations table
            $columnsResult = $conn->query("SHOW COLUMNS FROM event_registrations");
            $hasIdColumn = false;
            $hasEventIdColumn = false;
            $hasUserIdColumn = false;
            $hasUserTypeColumn = false;
            $columnNames = [];
            if ($columnsResult) {
                while ($col = $columnsResult->fetch_assoc()) {
                    $columnNames[] = $col['Field'];
                    if ($col['Field'] === 'id') {
                        $hasIdColumn = true;
                    }
                    if ($col['Field'] === 'event_id') {
                        $hasEventIdColumn = true;
                    }
                    if ($col['Field'] === 'user_id') {
                        $hasUserIdColumn = true;
                    }
                    if ($col['Field'] === 'user_type') {
                        $hasUserTypeColumn = true;
                    }
                }
            }
            
            // Check for existing registration based on available columns
            if (!$hasUserIdColumn || !$hasUserTypeColumn) {
                throw new Exception("event_registrations table structure is invalid: missing required columns (user_id, user_type). Available columns: " . implode(', ', $columnNames));
            }
            
            if ($hasEventIdColumn) {
                // event_id column exists - use it to check for existing registration
                if ($hasIdColumn) {
            $stmt = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ? AND user_type = ?");
                } else {
                    $stmt = $conn->prepare("SELECT event_id FROM event_registrations WHERE event_id = ? AND user_id = ? AND user_type = ?");
                }
            } else {
                // event_id column doesn't exist - can't check for existing registration
                throw new Exception("event_registrations table structure is invalid: missing event_id column. Available columns: " . implode(', ', $columnNames));
            }
            
            if (!$stmt) {
                throw new Exception("Failed to prepare check registration query: " . $conn->error);
            }
            $stmt->bind_param("iis", $eventId, $userId, $userType);
            if (!$stmt->execute()) {
                $errorMsg = $stmt->error;
                $stmt->close();
                // If execute fails with "Unknown column", provide helpful error
                if (strpos($errorMsg, "Unknown column") !== false) {
                    throw new Exception("Column check failed: " . $errorMsg . ". Available columns: " . implode(', ', $columnNames));
                }
                throw new Exception("Failed to execute check registration query: " . $errorMsg);
            }
            $result = $stmt->get_result();
            
            $isRegistered = $result->num_rows > 0;
            $stmt->close();
            
            echo json_encode(['success' => true, 'isRegistered' => $isRegistered]);
        } catch (Exception $e) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            error_log("Error checking registration: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        break;
        
    default:
        ob_clean();
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
    }
} catch (Error $e) {
    // Catch fatal errors (PHP 7+)
    if (ob_get_level() > 0) {
        ob_clean();
    }
    error_log("Event API fatal error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Fatal error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    // Catch any other uncaught exceptions
    if (ob_get_level() > 0) {
        ob_clean();
    }
    error_log("Event API uncaught exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Unexpected error: ' . $e->getMessage()]);
    exit;
}

// Clean up output buffer if still active
if (ob_get_level() > 0) {
    ob_end_flush();
}


