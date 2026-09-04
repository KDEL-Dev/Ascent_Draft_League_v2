<?php

    require_once __DIR__ . '/../../includes/connection.php';

    $seasonId = 1;

    $sql = "
        UPDATE draft_state
        SET
            is_active = 1,
            started_at = NOW()
        WHERE season_id = ?
        AND is_active = 0
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Prepare Failed: " . $conn->error);
    }

    $stmt->bind_param("i", $seasonId);

    if (!$stmt->execute()) {
        die("Execute Failed: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        echo "Draft is already active or does not exist.";
    } else {
        echo "Draft started successfully.";
    }

?>