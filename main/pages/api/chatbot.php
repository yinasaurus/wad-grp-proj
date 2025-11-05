<?php
header('Content-Type: text/plain');

$userMessage = $_POST['message'] ?? '';

if (!$userMessage) {
    echo "Please enter a message.";
    exit;
}

$apiKey = "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";
$url = "https://openrouter.ai/api/v1/chat/completions";

$headers = [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json",
];

$data = [
    "model" => "deepseek/deepseek-r1",
    "messages" => [
        ["role" => "user", "content" => $userMessage]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
echo $result['choices'][0]['message']['content'] ?? "Error getting response.";
?>

