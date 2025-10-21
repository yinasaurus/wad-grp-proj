<?php
header('Content-Type: application/json');

try {
    // Connect to the business database
    $conn = new mysqli('localhost', 'root', '', 'green_directory');
    
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }

    $query = "
        SELECT DISTINCT 
            b.id,
            b.name,
            b.category,
            b.address,
            b.lat,
            b.lng,
            b.description,
            b.sustainability_score,
            GROUP_CONCAT(c.certification_name SEPARATOR ',') as certifications
        FROM businesses b
        LEFT JOIN certifications c ON b.id = c.business_id
        GROUP BY b.id
        ORDER BY b.sustainability_score DESC
    ";

    $result = $conn->query($query);

    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed: ' . $conn->error]);
        exit;
    }

    $businesses = [];
    while ($row = $result->fetch_assoc()) {
        $businesses[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'category' => $row['category'],
            'location' => $row['address'],
            'lat' => floatval($row['lat']),
            'lng' => floatval($row['lng']),
            'description' => $row['description'],
            'sustainabilityScore' => intval($row['sustainability_score']),
            'certifications' => $row['certifications'] ? explode(',', $row['certifications']) : []
        ];
    }

    echo json_encode([
        'success' => true,
        'businesses' => $businesses,
        'count' => count($businesses)
    ]);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>