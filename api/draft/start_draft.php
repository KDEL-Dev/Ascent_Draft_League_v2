<?php

    require_once __DIR__ . '/../../includes/connection.php';

    header('Content-Type: application/json');

    $seasonId = 1;

    $sql = "
        UPDATE draft_state
        SET
            is_active = 1,
            status = 'active',
            started_at = NOW(),
            pick_started_at = NOW()
        WHERE season_id = ?
        AND status = 'pending'
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

    if (!$stmt->execute()) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to start draft."
        ]);
        exit;
    }

    if ($stmt->affected_rows === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Draft cannot be started. It may already be active, paused, or ended."
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Draft started successfully."
    ]);

?>
