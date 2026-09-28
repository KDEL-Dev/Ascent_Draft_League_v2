<?php

    require_once __DIR__ . '/../../includes/connection.php';

    header('Content-Type: application/json');

    $seasonId = 1;


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

        // WebSocket handshake

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


        // Read handshake response

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


        // Encode message

        $message = json_encode($data);

        if ($message === false)
        {
            fclose($socket);
            return false;
        }


        // Create masked WebSocket frame

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


        // Send frame

        $result = fwrite($socket, $frame);

        fclose($socket);

        return $result !== false;
    }


    // =====================================================
    // END DRAFT
    // =====================================================

    $sql = " UPDATE draft_state
        SET
            is_active = 0,
            status = 'ended',
            timer_remaining = NULL
        WHERE season_id = ?
        AND status IN ('active', 'paused')
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

    $stmt->bind_param("i", $seasonId);

    if (!$stmt->execute())
    {
        echo json_encode([
            "success" => false,
            "message" => "Failed to end draft."
        ]);

        exit;
    }

    if ($stmt->affected_rows === 0)
    {
        echo json_encode([
            "success" => false,
            "message" => "Draft is not currently active or paused."
        ]);

        exit;
    }


    // =====================================================
    // BROADCAST END EVENT
    // =====================================================

    $broadcasted = broadcastDraftEvent([
        "type" => "draft_ended",
        "season_id" => $seasonId,
        "status" => "ended"
    ]);


    // =====================================================
    // RESPONSE
    // =====================================================

    echo json_encode([
        "success" => true,
        "message" => "Draft ended successfully.",
        "broadcasted" => $broadcasted
    ]);

