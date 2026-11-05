<?php

    // Load environment variables
    require_once __DIR__ . '/../../vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();







    // require_once __DIR__ . '/../../includes/connection.php';
    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json');

    // -------------
    // GET SEASON ID
    // -------------

    // $seasonId = 1;

    // Get season ID from POST
    $seasonId = filter_input(
        INPUT_POST,
        'seasonId',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    // Validate season ID
    if ($seasonId === false || $seasonId === null) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid season ID."
        ]);

        exit;
    }

    // Check that season exists
    $seasonSql ="SELECT id
                 FROM seasons
                 WHERE id = ?
                 LIMIT 1
    ";

    $seasonStmt = $conn->prepare($seasonSql);

    if (!$seasonStmt) {
        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Failed to prepare season query."
        ]);

        exit;
    }

    $seasonStmt->bind_param("i", $seasonId);
    $seasonStmt->execute();

    $seasonResult = $seasonStmt->get_result();
    $season = $seasonResult->fetch_assoc();

    $seasonStmt->close();

    if (!$season) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Season not found."
        ]);

        exit;
    }

    
    // =====================================================
    // BROADCAST WEBSOCKET EVENT - LOCAL
    // =====================================================

    // function broadcastDraftEvent(array $data): bool
    // {
    //     $host = '127.0.0.1';
    //     $port = 8080;

    //     $socket = @fsockopen(
    //         $host,
    //         $port,
    //         $errno,
    //         $errstr,
    //         2
    //     );

    //     if (!$socket) {

    //         error_log(
    //             "WebSocket connection failed: {$errstr} ({$errno})"
    //         );

    //         return false;
    //     }


    //     // ---------------------------------------------
    //     // WebSocket handshake
    //     // ---------------------------------------------

    //     $key = base64_encode(random_bytes(16));

    //     $headers =
    //         "GET / HTTP/1.1\r\n" .
    //         "Host: {$host}:{$port}\r\n" .
    //         "Upgrade: websocket\r\n" .
    //         "Connection: Upgrade\r\n" .
    //         "Sec-WebSocket-Key: {$key}\r\n" .
    //         "Sec-WebSocket-Version: 13\r\n" .
    //         "\r\n";

    //     fwrite($socket, $headers);


    //     // ---------------------------------------------
    //     // Read handshake response
    //     // ---------------------------------------------

    //     $response = '';

    //     while (!feof($socket)) {

    //         $line = fgets($socket);

    //         if ($line === false) {
    //             break;
    //         }

    //         $response .= $line;

    //         if (rtrim($line) === '') {
    //             break;
    //         }
    //     }


    //     if (strpos($response, '101') === false) {

    //         fclose($socket);

    //         error_log(
    //             "WebSocket handshake failed: " . $response
    //         );

    //         return false;
    //     }


    //     // ---------------------------------------------
    //     // Encode message
    //     // ---------------------------------------------

    //     $message = json_encode($data);

    //     if ($message === false) {

    //         fclose($socket);

    //         return false;
    //     }


    //     // ---------------------------------------------
    //     // Create masked WebSocket frame
    //     // ---------------------------------------------

    //     $length = strlen($message);

    //     $mask = random_bytes(4);

    //     $maskedMessage = '';

    //     for ($i = 0; $i < $length; $i++) {

    //         $maskedMessage .=
    //             $message[$i] ^
    //             $mask[$i % 4];
    //     }


    //     if ($length <= 125) {

    //         $frame =
    //             chr(0x81) .
    //             chr(0x80 | $length) .
    //             $mask .
    //             $maskedMessage;

    //     } elseif ($length <= 65535) {

    //         $frame =
    //             chr(0x81) .
    //             chr(0x80 | 126) .
    //             pack('n', $length) .
    //             $mask .
    //             $maskedMessage;

    //     } else {

    //         $frame =
    //             chr(0x81) .
    //             chr(0x80 | 127) .
    //             pack('J', $length) .
    //             $mask .
    //             $maskedMessage;
    //     }


    //     // ---------------------------------------------
    //     // Send frame
    //     // ---------------------------------------------

    //     $result = fwrite($socket, $frame);

    //     fclose($socket);

    //     return $result !== false;
    // }


    // =====================================================
    // BROADCAST WEBSOCKET EVENT (HTTP cURL to Render)
    // =====================================================

    function broadcastDraftEvent(array $data): array    {
        $url = 'https://ascent-web-server.onrender.com/broadcast';

        $secret = $_ENV['BROADCAST_SECRET'];
        // $secret = '610d982f13ac70e4dede0260f003160adea30b4de0252866f49a9546a2e79739';
        
        error_log("Secret loaded: " . (!empty($secret) ? "YES" : "NO"));

        if (!$secret) {
        error_log("BROADCAST_SECRET is missing.");

        return [
            "success" => false,
            "error" => "Missing broadcast secret."
        ];
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
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20
        ]);

        // ADDED for testing
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);


        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $curlError = curl_error($ch);

        $curlErrno = curl_errno($ch);

        curl_close($ch);

        $debug = [
            "url" => $url,
            "secret_loaded" => !empty($secret),
            "curl_errno" => $curlErrno,
            "curl_error" => $curlError,
            "http_code" => $httpCode,
            "render_response" => $response
        ];

        error_log("BROADCAST DEBUG: " . json_encode($debug));

        return [
            "success" => !$curlError && $httpCode === 200,
            "debug" => $debug
        ];

    }

    // -------------------------
    // GET CURRENT DRAFT STATE
    // -------------------------

    $sql = "SELECT
                status,
                timer_remaining
            FROM draft_state
            WHERE season_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Prepare failed."
        ]);
        exit;
    }

    $stmt->bind_param("i", $seasonId);
    $stmt->execute();

    $result = $stmt->get_result();

    $draftState = $result->fetch_assoc();

    if (!$draftState) {
        echo json_encode([
            "success" => false,
            "message" => "Draft state not found."
        ]);
        exit;
    }



    // =====================================================
    // START NEW DRAFT
    // =====================================================

    if ($draftState['status'] === 'pending')
    {
        $sql = "
            UPDATE draft_state
            SET
                is_active = 1,
                status = 'active',
                started_at = NOW(),
                pick_started_at = NOW(),
                timer_remaining = NULL
            WHERE season_id = ?
            AND status = 'pending'
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            echo json_encode([
                "success" => false,
                "message" => "Prepare failed when starting draft."
            ]);

            exit;
        }

        $stmt->bind_param("i", $seasonId);

        if (!$stmt->execute()) {

            echo json_encode([
                "success" => false,
                "message" => "Failed to start draft."
            ]);

            exit;
        }


        // =================================================
        // BROADCAST TO ALL CONNECTED PARTICIPANTS
        // =================================================

        $broadcasted = broadcastDraftEvent([
            "type" => "draft_started",
            "season_id" => $seasonId,
            "status" => "active"
        ]);


        echo json_encode([
            "success" => true,
            "message" => "Draft started successfully.",
            "broadcasted" => $broadcasted
        ]);

        exit;
    }

    

    // -------------------------
    // RESUME PAUSED DRAFT
    // -------------------------

    if ($draftState['status'] === 'paused')
    {
        $timerRemaining = (int)$draftState['timer_remaining'];

        /*
        * We want the timer to resume with the
        * number of seconds that were remaining.
        *
        * Example:
        *
        * timer_remaining = 20
        *
        * NOW() - 20 seconds
        *        ↓
        * pick_started_at
        *
        * JavaScript then calculates:
        *
        * 60 - 0 = 60
        *
        * after 1 second:
        *
        * 60 - 1 = 59
        *
        * ...
        *
        * after 40 seconds:
        *
        * 60 - 40 = 20
        *
        * So instead we need pick_started_at
        * to represent 60 - timerRemaining seconds ago.
        */

        $elapsedBeforePause = 60 - $timerRemaining;

        $sql = "UPDATE draft_state
                SET
                    is_active = 1,
                    status = 'active',
                    pick_started_at = DATE_SUB(NOW(), INTERVAL ? SECOND),
                    paused_at = NULL,
                    timer_remaining = NULL
                WHERE season_id = ?
                AND status = 'paused'
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            echo json_encode([
                "success" => false,
                "message" => "Prepare failed when resuming draft."
            ]);

            exit;
        }

        $stmt->bind_param(
            "ii",
            $elapsedBeforePause,
            $seasonId
        );

        if (!$stmt->execute()) {

            echo json_encode([
                "success" => false,
                "message" => "Failed to resume draft."
            ]);

            exit;
        }


        // =================================================
        // BROADCAST RESUME
        // =================================================

        $broadcasted = broadcastDraftEvent([
            "type" => "draft_resumed",
            "season_id" => $seasonId,
            "status" => "active",
            "timer_remaining" => $timerRemaining
        ]);


        echo json_encode([
            "success" => true,
            "message" => "Draft resumed successfully.",
            "timer_remaining" => $timerRemaining,
            "broadcasted" => $broadcasted
        ]);

        exit;
    }


    // -------------------------
    // INVALID STATUS
    // -------------------------

    echo json_encode([
        "success" => false,
        "message" => "Draft cannot be started or resumed from its current status."
    ]);

?>
