<?php
/**
 * API Endpoint to serve public API keys
 * This endpoint is safe to expose as it only serves public client-side keys
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '*'));
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../../config.php';

$googleMapsApiKey = '';

$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (preg_match('/^GOOGLE_MAPS_API_KEY\s*=\s*["\']?([^"\'\r\n]+)["\']?/im', $envContent, $matches)) {
        $googleMapsApiKey = trim($matches[1]);
    }
}
if (empty($googleMapsApiKey)) {
    $googleMapsApiKey = env('GOOGLE_MAPS_API_KEY', '');
}
if (empty($googleMapsApiKey) && isset($_ENV['GOOGLE_MAPS_API_KEY'])) {
    $googleMapsApiKey = $_ENV['GOOGLE_MAPS_API_KEY'];
}
$config = [
    'googleMapsApiKey' => $googleMapsApiKey
];

echo json_encode($config);

