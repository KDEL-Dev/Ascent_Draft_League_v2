<?php

require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON"
    ]);
    exit;
}

$host = '127.0.0.1';
$port = 8080;

$socket = @fsockopen(
    $host,
    $port,
    $errno,
    $errstr,
    2
);

if (!$socket) {

    error_log("WebSocket connection failed: {$errstr} ({$errno})");

    echo json_encode([
        "success" => false,
        "message" => "Could not connect to WebSocket server."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| WebSocket handshake
|--------------------------------------------------------------------------
*/

$key = base64_encode(random_bytes(16));

$headers =
    "GET / HTTP/1.1\r\n" .
    "Host: {$host}:{$port}\r\n" .
    "Upgrade: websocket\r\n" .
    "Connection: Upgrade\r\n" .
    "Sec-WebSocket-Key: {$key}\r\n" .
    "Sec-WebSocket-Version: 13\r\n" .
    "\r\n";

fwrite($socket, $headers);


/*
|--------------------------------------------------------------------------
| Read handshake response
|--------------------------------------------------------------------------
*/

$response = '';

while (!feof($socket)) {

    $line = fgets($socket);

    if ($line === false) {
        break;
    }

    $response .= $line;

    if (rtrim($line) === '') {
        break;
    }
}

if (strpos($response, '101') === false) {

    fclose($socket);

    error_log("WebSocket handshake failed: " . $response);

    echo json_encode([
        "success" => false,
        "message" => "WebSocket handshake failed."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Encode message
|--------------------------------------------------------------------------
*/

$message = json_encode($data);

if ($message === false) {

    fclose($socket);

    echo json_encode([
        "success" => false,
        "message" => "Could not encode message."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Create MASKED WebSocket frame
|--------------------------------------------------------------------------
|
| Client -> server WebSocket frames MUST be masked.
|
*/

$length = strlen($message);

$mask = random_bytes(4);

$maskedMessage = '';

for ($i = 0; $i < $length; $i++) {
    $maskedMessage .= $message[$i] ^ $mask[$i % 4];
}


if ($length <= 125) {

    $frame =
        chr(0x81) .
        chr(0x80 | $length) .
        $mask .
        $maskedMessage;

}
elseif ($length <= 65535) {

    $frame =
        chr(0x81) .
        chr(0x80 | 126) .
        pack('n', $length) .
        $mask .
        $maskedMessage;

}
else {

    $frame =
        chr(0x81) .
        chr(0x80 | 127) .
        pack('J', $length) .
        $mask .
        $maskedMessage;
}


/*
|--------------------------------------------------------------------------
| Send frame
|--------------------------------------------------------------------------
*/

$result = fwrite($socket, $frame);

if ($result === false) {

    fclose($socket);

    echo json_encode([
        "success" => false,
        "message" => "Failed to send WebSocket message."
    ]);

    exit;
}

fclose($socket);


/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "message" => "Broadcast sent.",
    "data" => $data
]);
