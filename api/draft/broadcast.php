
<?php

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON."
    ]);

    exit;
}

// Render broadcast endpoint
$url = 'https://ascent-web-server.onrender.com/broadcast';

// Load shared secret from Hostinger environment
$secret = getenv('BROADCAST_SECRET');

if (!$secret) {
    error_log("BROADCAST_SECRET is missing.");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Broadcast configuration missing."
    ]);

    exit;
}

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $secret
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$curlError = curl_error($ch);

curl_close($ch);

// Log results
error_log("========== BROADCAST DEBUG ==========");
error_log("HTTP Code: " . $httpCode);
error_log("cURL Error: " . $curlError);
error_log("Response: " . $response);
error_log("=====================================");

if ($curlError) {
    http_response_code(502);

    echo json_encode([
        "success" => false,
        "message" => "Could not contact Render.",
        "error" => $curlError
    ]);

    exit;
}

if ($httpCode !== 200) {
    http_response_code(502);

    echo json_encode([
        "success" => false,
        "message" => "Render rejected the broadcast.",
        "http_code" => $httpCode,
        "response" => $response
    ]);

    exit;
}

echo json_encode([
    "success" => true,
    "message" => "Broadcast sent.",
    "data" => $data
]);

