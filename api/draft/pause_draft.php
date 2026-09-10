<?php

    require_once __DIR__ . '/../../includes/connection.php';

    header('Content-Type: application/json');

    $seasonId = 1;

    // Get timer information from JavaScript
    $data = json_decode(file_get_contents("php://input"), true);

    $timerRemaining = $data['timer_remaining'] ?? null;

    if ($timerRemaining === null) {
        echo json_encode([
            "success" => false,
            "message" => "Timer remaining was not provided."
        ]);
        exit;
    }

    // Make sure timer is between 0 and 60
    $timerRemaining = max(0, min(60, (int)$timerRemaining));

    $sql = "
        UPDATE draft_state
        SET
            is_active = 0,
            status = 'paused',
            paused_at = NOW(),
            timer_remaining = ?
        WHERE season_id = ?
        AND status = 'active'
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Prepare failed: " . $conn->error
        ]);
        exit;
    }

    $stmt->bind_param(
        "ii",
        $timerRemaining,
        $seasonId
    );

    if (!$stmt->execute()) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to pause draft: " . $stmt->error
        ]);
        exit;
    }

    if ($stmt->affected_rows === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Draft is not currently active."
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Draft paused successfully.",
        "timer_remaining" => $timerRemaining
    ]);

?>
