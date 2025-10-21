<?php
session_start();
require_once '../../db_config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $conn = getDBConnection();

    // GET ALL BUSINESSES (for directory listing)
    if ($action === 'all_businesses' && $method === 'GET') {
        $stmt = $conn->prepare("
            SELECT 
                b.id, 
                b.name, 
                b.category, 
                b.address, 
                b.email, 
                b.phone, 
                b.website, 
                b.description, 
                b.sustainability_score, 
                b.verified, 
                b.lat, 
                b.lng
            FROM businesses b
            ORDER BY b.name ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $businesses = [];

        while ($row = $result->fetch_assoc()) {
            // Get certifications for this business separately
            $certStmt = $conn->prepare("SELECT certification_name FROM certifications WHERE business_id = ?");
            $certStmt->bind_param("i", $row['id']);
            $certStmt->execute();
            $certResult = $certStmt->get_result();
            $certifications = [];
            while ($cert = $certResult->fetch_assoc()) {
                $certifications[] = $cert['certification_name'];
            }

            $businesses[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'category' => $row['category'],
                'address' => $row['address'],
                'description' => $row['description'],
                'sustainabilityScore' => (int)$row['sustainability_score'],
                'certifications' => $certifications,
                'phone' => $row['phone'],
                'email' => $row['email'],
                'website' => $row['website'],
                'verified' => (bool)$row['verified'],
                'lat' => $row['lat'] ? (float)$row['lat'] : null,
                'lng' => $row['lng'] ? (float)$row['lng'] : null
            ];
        }

        echo json_encode([
            'success' => true,
            'businesses' => $businesses
        ]);
        exit;
    }

    // GET SINGLE BUSINESS (for public profile viewing)
    if ($action === 'get_business' && $method === 'GET') {
        $businessId = $_GET['id'] ?? null;
        
        if (!$businessId) {
            http_response_code(400);
            echo json_encode(['error' => 'Business ID required']);
            exit;
        }
        
        $stmt = $conn->prepare("
            SELECT 
                b.id, 
                b.name, 
                b.category, 
                b.address, 
                b.email, 
                b.phone, 
                b.website, 
                b.description, 
                b.sustainability_score, 
                b.verified, 
                b.lat, 
                b.lng
            FROM businesses b
            WHERE b.id = ?
        ");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Business not found']);
            exit;
        }
        
        $row = $result->fetch_assoc();
        
        // Get certifications
        $certStmt = $conn->prepare("SELECT certification_name FROM certifications WHERE business_id = ?");
        $certStmt->bind_param("i", $businessId);
        $certStmt->execute();
        $certResult = $certStmt->get_result();
        $certifications = [];
        while ($cert = $certResult->fetch_assoc()) {
            $certifications[] = $cert['certification_name'];
        }
        
        // Get practices
        $practStmt = $conn->prepare("SELECT practice_title as title, practice_description as description FROM business_practices WHERE business_id = ?");
        $practStmt->bind_param("i", $businessId);
        $practStmt->execute();
        $practResult = $practStmt->get_result();
        $practices = [];
        while ($pract = $practResult->fetch_assoc()) {
            $practices[] = $pract;
        }
        
        // Get products
        $prodStmt = $conn->prepare("SELECT pid as id, productname as name, descript as description, pvalue as price, available FROM greenpns WHERE bid = ?");
        $prodStmt->bind_param("i", $businessId);
        $prodStmt->execute();
        $prodResult = $prodStmt->get_result();
        $products = [];
        while ($prod = $prodResult->fetch_assoc()) {
            $products[] = [
                'name' => $prod['name'],
                'description' => $prod['description'],
                'price' => '$' . $prod['price'],
                'available' => (bool)$prod['available']
            ];
        }
        
        // Get locations
        $locStmt = $conn->prepare("SELECT location_name as name, address, operating_hours as hours FROM business_locations WHERE business_id = ?");
        $locStmt->bind_param("i", $businessId);
        $locStmt->execute();
        $locResult = $locStmt->get_result();
        $locations = [];
        while ($loc = $locResult->fetch_assoc()) {
            $locations[] = [
                'name' => $loc['name'],
                'address' => $loc['address'],
                'hours' => $loc['hours'],
                'mapLink' => '#'
            ];
        }
        
        // Get updates
        $updStmt = $conn->prepare("SELECT title, content as description, created_at as time FROM business_updates WHERE business_id = ? ORDER BY created_at DESC");
        $updStmt->bind_param("i", $businessId);
        $updStmt->execute();
        $updResult = $updStmt->get_result();
        $updates = [];
        while ($upd = $updResult->fetch_assoc()) {
            $updates[] = $upd;
        }
        
        echo json_encode([
            'success' => true,
            'business' => [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'category' => $row['category'],
                'address' => $row['address'],
                'description' => $row['description'],
                'longDescription' => $row['description'],
                'sustainabilityScore' => (int)$row['sustainability_score'],
                'certifications' => $certifications,
                'phone' => $row['phone'],
                'email' => $row['email'],
                'website' => $row['website'],
                'verified' => (bool)$row['verified'],
                'lat' => $row['lat'] ? (float)$row['lat'] : null,
                'lng' => $row['lng'] ? (float)$row['lng'] : null,
                'ecoPractices' => $practices,
                'products' => $products,
                'locations' => $locations,
                'stats' => [
                    'carbonReduced' => '125',
                    'wasteRecycled' => '95%',
                    'energySaved' => '450 MWh',
                    'waterSaved' => '2.5M L'
                ],
                'fullCertifications' => array_map(function($cert) {
                    return [
                        'name' => $cert,
                        'issued' => '2023',
                        'expiry' => '2026'
                    ];
                }, $certifications),
                'contact' => [
                    'phone' => $row['phone'] ?: '+65 0000 0000',
                    'email' => $row['email'] ?: 'info@business.sg',
                    'website' => $row['website'] ?: 'www.business.sg'
                ]
            ]
        ]);
        exit;
    }

    // GET BUSINESS PROFILE (for partner dashboard)
    if ($action === 'business_profile' && $method === 'GET') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated as business']);
            exit;
        }

        $userId = $_SESSION['user_id'];

        $stmt = $conn->prepare("SELECT * FROM businesses WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $business = $stmt->get_result()->fetch_assoc();

        if (!$business) {
            http_response_code(404);
            echo json_encode(['error' => 'Business not found']);
            exit;
        }

        $businessId = $business['id'];

        // Get certifications (from 'certifications' table)
        $stmt = $conn->prepare("SELECT id as cert_id, certification_name as name, certificate_number as code, issue_date as issued, expiry_date as expiry FROM certifications WHERE business_id = ?");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $certifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get locations
        $stmt = $conn->prepare("SELECT location_id, location_name as name, address, operating_hours as hours FROM business_locations WHERE business_id = ?");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $locations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get practices
        $stmt = $conn->prepare("SELECT practice_id, practice_title as title, practice_description as description FROM business_practices WHERE business_id = ?");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $practices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get products (from 'greenpns' table)
        $stmt = $conn->prepare("SELECT pid as product_id, productname as name, descript as description, pvalue as price, available FROM greenpns WHERE bid = ?");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get updates
        $stmt = $conn->prepare("SELECT update_id, title, content as description, created_at as time FROM business_updates WHERE business_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $updates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'business' => [
                'business_name' => $business['name'],
                'category' => $business['category'] ?? '',
                'description' => $business['description'] ?? '',
                'phone' => $business['phone'] ?? '',
                'email' => $business['email'] ?? '',
                'website' => $business['website'] ?? ''
            ],
            'locations' => $locations,
            'certifications' => $certifications,
            'practices' => $practices,
            'products' => $products,
            'updates' => $updates,
            'stats' => [
                'certifications' => count($certifications),
                'locations' => count($locations),
                'profileViews' => 0,
                'sustainabilityScore' => $business['sustainability_score'] ?? 0
            ]
        ]);
        exit;
    }

    // UPDATE BUSINESS PROFILE
    if ($action === 'update_business_profile' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $_SESSION['user_id'];

        $stmt = $conn->prepare("UPDATE businesses SET name = ?, category = ?, description = ?, phone = ?, email = ?, website = ?, address = ?, lat = ?, lng = ? WHERE user_id = ?");
        $stmt->bind_param("sssssssddi", $data['name'], $data['category'], $data['description'], $data['phone'], $data['email'], $data['website'], $data['address'], $data['lat'], $data['lng'], $userId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update profile']);
        }
        exit;
    }

    // ADD LOCATION
    if ($action === 'add_location' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $biz = $stmt->get_result()->fetch_assoc();

        if (!$biz) {
            http_response_code(404);
            echo json_encode(['error' => 'Business not found']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO business_locations (business_id, location_name, address, operating_hours) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $biz['id'], $data['name'], $data['address'], $data['hours']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'locationId' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add location']);
        }
        exit;
    }

    // REMOVE LOCATION
    if ($action === 'remove_location' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("DELETE FROM business_locations WHERE location_id = ?");
        $stmt->bind_param("i", $data['locationId']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove location']);
        }
        exit;
    }

    // ADD CERTIFICATION
    if ($action === 'add_certification' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $biz = $stmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare("INSERT INTO certifications (business_id, certification_name, certificate_number, issue_date, expiry_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $biz['id'], $data['name'], $data['code'], $data['issued'], $data['expiry']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'certId' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add certification']);
        }
        exit;
    }

    // REMOVE CERTIFICATION
    if ($action === 'remove_certification' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("DELETE FROM certifications WHERE id = ?");
        $stmt->bind_param("i", $data['certId']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove certification']);
        }
        exit;
    }

    // ADD PRACTICE
    if ($action === 'add_practice' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $biz = $stmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare("INSERT INTO business_practices (business_id, practice_title, practice_description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $biz['id'], $data['title'], $data['description']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'practiceId' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add practice']);
        }
        exit;
    }

    // REMOVE PRACTICE
    if ($action === 'remove_practice' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("DELETE FROM business_practices WHERE practice_id = ?");
        $stmt->bind_param("i", $data['practiceId']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove practice']);
        }
        exit;
    }

    // ADD PRODUCT
    if ($action === 'add_product' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $biz = $stmt->get_result()->fetch_assoc();

        $available = $data['available'] ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO greenpns (productname, descript, pvalue, bid, available) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $data['name'], $data['description'], $data['price'], $biz['id'], $available);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'productId' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add product']);
        }
        exit;
    }

    // REMOVE PRODUCT
    if ($action === 'remove_product' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("DELETE FROM greenpns WHERE pid = ?");
        $stmt->bind_param("i", $data['productId']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove product']);
        }
        exit;
    }

    // ADD UPDATE
    if ($action === 'add_update' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conn->prepare("SELECT id FROM businesses WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $biz = $stmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare("INSERT INTO business_updates (business_id, title, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $biz['id'], $data['title'], $data['description']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'updateId' => $conn->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add update']);
        }
        exit;
    }

    // REMOVE UPDATE
    if ($action === 'remove_update' && $method === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business') {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("DELETE FROM business_updates WHERE update_id = ?");
        $stmt->bind_param("i", $data['updateId']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove update']);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>