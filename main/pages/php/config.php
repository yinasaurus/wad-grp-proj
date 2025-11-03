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

// Only return specific keys that are safe to expose
$config = [
    'googleMapsApiKey' => env('GOOGLE_MAPS_API_KEY', ''),
    'firebase' => [
        'apiKey' => env('FIREBASE_API_KEY', ''),
        'authDomain' => env('FIREBASE_AUTH_DOMAIN', ''),
        'projectId' => env('FIREBASE_PROJECT_ID', ''),
        'storageBucket' => env('FIREBASE_STORAGE_BUCKET', ''),
        'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID', ''),
        'appId' => env('FIREBASE_APP_ID', ''),
        'measurementId' => env('FIREBASE_MEASUREMENT_ID', '')
    ]
];

echo json_encode($config);

