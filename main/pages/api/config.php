<?php
/**
 * API Endpoint to serve public API keys
 * This endpoint is safe to expose as it only serves public client-side keys
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '*'));
header('Access-Control-Allow-Credentials: true');

// Load config from parent directory
require_once __DIR__ . '/../../config.php';

// Get Google Maps API key - try multiple sources
$googleMapsApiKey = '';

// 1. Try direct read from .env file first (most reliable for InfinityFree)
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (preg_match('/^GOOGLE_MAPS_API_KEY\s*=\s*["\']?([^"\'\r\n]+)["\']?/im', $envContent, $matches)) {
        $googleMapsApiKey = trim($matches[1]);
    }
}

// 2. Try from env() function (if .env was loaded)
if (empty($googleMapsApiKey)) {
    $googleMapsApiKey = env('GOOGLE_MAPS_API_KEY', '');
}

// 3. Also check $_ENV array directly
if (empty($googleMapsApiKey) && isset($_ENV['GOOGLE_MAPS_API_KEY'])) {
    $googleMapsApiKey = $_ENV['GOOGLE_MAPS_API_KEY'];
}

// 4. If still not found, try direct setting (for InfinityFree compatibility)
// PUT YOUR GOOGLE MAPS API KEY HERE:
if (empty($googleMapsApiKey)) {
    // $googleMapsApiKey = 'YOUR_GOOGLE_MAPS_API_KEY_HERE';
}

// Only return specific keys that are safe to expose
$config = [
    'googleMapsApiKey' => $googleMapsApiKey
    // Firebase completely removed - using PHP/MySQL for everything
];

echo json_encode($config);

