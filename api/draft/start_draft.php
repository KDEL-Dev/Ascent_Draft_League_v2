<?php

    require_once __DIR__ . '/../../includes/connection.php';

    header('Content-Type: application/json');

    $seasonId = 1;


    // -------------------------
    // GET CURRENT DRAFT STATE
    // -------------------------

    $sql = "
        SELECT
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


    // -------------------------
    // START NEW DRAFT
    // -------------------------

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

        echo json_encode([
            "success" => true,
            "message" => "Draft started successfully."
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

        $sql = "
            UPDATE draft_state
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

        echo json_encode([
            "success" => true,
            "message" => "Draft resumed successfully.",
            "timer_remaining" => $timerRemaining
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
