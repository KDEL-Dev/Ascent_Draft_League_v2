<?php

    require_once __DIR__ . '/../../includes/connection.php';

    header('Content-Type: application/json');

    $seasonId = 1;

    $sql = "
        UPDATE draft_state
        SET
            is_active = 1,
            status = 'active',
            pick_started_at = DATE_SUB(NOW(), INTERVAL (60 - timer_remaining) SECOND),
            timer_remaining = NULL
        WHERE season_id = ?
        AND status = 'paused'
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
            "message" => "Failed to resume draft."
        ]);
        exit;
    }

    if ($stmt->affected_rows === 0)
    {
        echo json_encode([
            "success" => false,
            "message" => "Draft is not paused."
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Draft resumed successfully."
    ]);

?>
