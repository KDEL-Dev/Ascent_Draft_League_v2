<?php

    header('Content-Type: application/json');

    require_once __DIR__ . '/../../includes/connection.php';

    $seasonId = 1;

    // BROADCAST

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

        $message = json_encode($data);

        if ($message === false)
        {
            fclose($socket);
            return false;
        }

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

        $result = fwrite($socket, $frame);

        fclose($socket);

        return $result !== false;
    }



    // -------------------------
    // GET DRAFT STATE
    // -------------------------

    $sql = "
        SELECT
            draft_position,
            current_round,
            total_picks,
            is_active
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

    if (!$draftState['is_active']) {
        echo json_encode([
            "success" => false,
            "message" => "Draft is not active."
        ]);
        exit;
    }


    // -------------------------
    // GET CURRENT DRAFT USER
    // -------------------------

    $sql = "
        SELECT
            id,
            team_name,
            draft_position
        FROM active_users
        WHERE season_id = ?
        AND draft_position = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Prepare failed when finding current drafter."
        ]);
        exit;
    }

    $stmt->bind_param(
        "ii",
        $seasonId,
        $draftState['draft_position']
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $currentUser = $result->fetch_assoc();

    if (!$currentUser) {
        echo json_encode([
            "success" => false,
            "message" => "Could not find the current drafter."
        ]);
        exit;
    }


    // -------------------------
    // TEAM COUNT
    // -------------------------

    $sql = "
        SELECT COUNT(*) AS team_count
        FROM active_users
        WHERE season_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Prepare failed when counting teams."
        ]);
        exit;
    }

    $stmt->bind_param("i", $seasonId);
    $stmt->execute();

    $result = $stmt->get_result();

    $teamCount = $result->fetch_assoc()['team_count'];


    // -------------------------
    // RECORD SKIPPED PICK
    // -------------------------

    $pickNumber = $draftState['total_picks'] + 1;
    $roundNumber = $draftState['current_round'];

    $sql = "
        INSERT INTO draft_picks
        (
            season_id,
            active_user_id,
            showdown_pokemon_id,
            pick_number,
            round_number
        )
        VALUES (?, ?, NULL, ?, ?)
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Prepare failed when recording skipped pick."
        ]);
        exit;
    }

    $stmt->bind_param(
        "iiii",
        $seasonId,
        $currentUser['id'],
        $pickNumber,
        $roundNumber
    );

    if (!$stmt->execute()) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to record skipped pick."
        ]);
        exit;
    }




    // -------------------------
    // ADVANCE DRAFT
    // -------------------------

    $newTotalPicks = $draftState['total_picks'] + 1;

    $currentPosition = $draftState['draft_position'];
    $currentRound = $draftState['current_round'];


    // Odd rounds move forward
    if ($currentRound % 2 === 1)
    {
        if ($currentPosition < $teamCount)
        {
            $newDraftPosition = $currentPosition + 1;
            $newRound = $currentRound;
        }
        else
        {
            // End of round
            // Same team picks first next round
            $newDraftPosition = $teamCount;
            $newRound = $currentRound + 1;
        }
    }


    // Even rounds move backward
    else
    {
        if ($currentPosition > 1)
        {
            $newDraftPosition = $currentPosition - 1;
            $newRound = $currentRound;
        }
        else
        {
            // End of round
            // Same team picks first next round
            $newDraftPosition = 1;
            $newRound = $currentRound + 1;
        }
    }


    // -------------------------
    // UPDATE DRAFT STATE
    // -------------------------

    $sql = "
        UPDATE draft_state
        SET
            total_picks = ?,
            draft_position = ?,
            current_round = ?,
            pick_started_at = NOW()
        WHERE season_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Prepare failed when updating draft state."
        ]);
        exit;
    }

    $stmt->bind_param(
        "iiii",
        $newTotalPicks,
        $newDraftPosition,
        $newRound,
        $seasonId
    );

    if (!$stmt->execute()) {
        echo json_encode([
            "success" => false,
            "message" => "Skipped pick was recorded, but draft state failed to update."
        ]);
        exit;
    }

    //----- BROADCAST SKIPPED PICK -----
        $broadcasted = broadcastDraftEvent([
            "type" => "draft_skipped",
            "season_id" => $seasonId,
            "pick_number" => $pickNumber,
            "active_user_id" => $currentUser['id'],
            "draft_position" => $newDraftPosition,
            "current_round" => $newRound,
            "total_picks" => $newTotalPicks
        ]);

    // -------------------------
    // SUCCESS
    // -------------------------

    echo json_encode([
        "success" => true,
        "message" => "Pick skipped successfully.",
        "broadcasted" => $broadcasted
    ]);

?>
