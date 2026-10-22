<?php

    require_once __DIR__ . '/../../includes/connection.php';

    header('Content-Type: application/json');


    // Get JSON input from request
    $input = json_decode(file_get_contents("php://input"), true);

    // Validate JSON input
    if (!is_array($input)) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid request data."
        ]);
        exit;
    }

    // Get and validate season ID
    $seasonId = filter_var(
        $input['seasonId'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($seasonId === false || $seasonId === null) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid season ID."
        ]);
        exit;
    }

    // Get and validate timer remaining
    $timerRemaining = filter_var(
        $input['timer_remaining'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0, 'max_range' => 60]]
    );

    if ($timerRemaining === false || $timerRemaining === null) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid timer remaining."
        ]);
        exit;
    }

    // Check that the season exists
    $seasonSql = "
        SELECT id
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
    // BROADCAST WEBSOCKET EVENT
    // =====================================================

    function broadcastDraftEvent(array $data): bool
    {
        $host = '127.0.0.1';
        $port = 8080;

        $socket = @fsockopen(
            $host,
            $port,
            $errno,
            $errstr,
            2
        );

        if (!$socket)
        {
            error_log(
                "WebSocket connection failed: {$errstr} ({$errno})"
            );

            return false;
        }


        // ---------------------------------------------
        // WebSocket handshake
        // ---------------------------------------------

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


        // ---------------------------------------------
        // Read handshake response
        // ---------------------------------------------

        $response = '';

        while (!feof($socket))
        {
            $line = fgets($socket);

            if ($line === false)
            {
                break;
            }

            $response .= $line;

            if (rtrim($line) === '')
            {
                break;
            }
        }


        if (strpos($response, '101') === false)
        {
            fclose($socket);

            error_log(
                "WebSocket handshake failed: " . $response
            );

            return false;
        }


        // ---------------------------------------------
        // Encode message
        // ---------------------------------------------

        $message = json_encode($data);

        if ($message === false)
        {
            fclose($socket);

            return false;
        }


        // ---------------------------------------------
        // Create MASKED WebSocket frame
        // ---------------------------------------------

        $length = strlen($message);

        $mask = random_bytes(4);

        $maskedMessage = '';

        for ($i = 0; $i < $length; $i++)
        {
            $maskedMessage .=
                $message[$i] ^
                $mask[$i % 4];
        }


        if ($length <= 125)
        {
            $frame =
                chr(0x81) .
                chr(0x80 | $length) .
                $mask .
                $maskedMessage;
        }
        elseif ($length <= 65535)
        {
            $frame =
                chr(0x81) .
                chr(0x80 | 126) .
                pack('n', $length) .
                $mask .
                $maskedMessage;
        }
        else
        {
            $frame =
                chr(0x81) .
                chr(0x80 | 127) .
                pack('J', $length) .
                $mask .
                $maskedMessage;
        }


        // ---------------------------------------------
        // Send frame
        // ---------------------------------------------

        $result = fwrite($socket, $frame);

        fclose($socket);

        return $result !== false;
    }


    //------ PAUSE DRAFT ------
    $sql = "
        UPDATE draft_state
        SET
            is_active = 0,
            status = 'paused',
            timer_remaining = ?
        WHERE season_id = ?
        AND status = 'active'
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt)
    {
        echo json_encode([
            "success" => false,
            "message" => "Prepare failed."
        ]);
        exit;
    }

    $stmt->bind_param(
        "ii",
        $timerRemaining,
        $seasonId
    );

    if (!$stmt->execute())
    {
        echo json_encode([
            "success" => false,
            "message" => "Failed to pause draft."
        ]);
        exit;
    }

    if ($stmt->affected_rows === 0)
    {
        echo json_encode([
            "success" => false,
            "message" => "Draft is not currently active."
        ]);
        exit;
    }

    // BROADCAST PAUSE EVENT
    $broadcasted = broadcastDraftEvent([
        "type" => "draft_paused",
        "season_id" => $seasonId,
        "status" => "paused",
        "timer_remaining" => $timerRemaining
    ]);


    //----- RESPONSE -----
    echo json_encode([
        "success" => true,
        "message" => "Draft paused successfully.",
        "timer_remaining" => (int) $timerRemaining,
        "broadcasted" => $broadcasted
    ]);

?>
