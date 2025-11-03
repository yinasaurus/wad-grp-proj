<?php
// Lightweight Firebase ID token verification via Google REST API
// Uses accounts:lookup to validate the ID token and retrieve user info

function verifyFirebaseIdToken(string $idToken, string $apiKey): array
{
    $endpoint = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . urlencode($apiKey);

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['idToken' => $idToken]));

    // Disable SSL verification for local development (WAMP/XAMPP)
    // WARNING: Only use this in local development! Remove in production.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Firebase verify request failed: ' . $err);
    }
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($status !== 200) {
        $message = $data['error']['message'] ?? 'Unknown error';
        throw new Exception('Firebase verify error: ' . $message);
    }

    if (!isset($data['users'][0])) {
        throw new Exception('Invalid token: user not found');
    }

    $user = $data['users'][0];
    return [
        'uid' => $user['localId'] ?? null,
        'email' => $user['email'] ?? null,
        'emailVerified' => (bool)($user['emailVerified'] ?? false),
        'displayName' => $user['displayName'] ?? null,
        'photoUrl' => $user['photoUrl'] ?? null,
        'provider' => $user['providerUserInfo'][0]['providerId'] ?? null,
    ];
}