<?php

    require_once __DIR__ . '/../../includes/auth.php';

    // 1. Check admin authorization
    if (!$isAdmin) {
        http_response_code(403);
        exit('Access denied.');
    }

    // 2. Validate season ID
    $seasonId = filter_input(
        INPUT_POST,
        'seasonId',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($seasonId === false || $seasonId === null) {
        http_response_code(400);
        exit('Invalid season ID.');
    }

    // 3. Confirm season exists
    $sql = "SELECT id
            FROM seasons
            WHERE id = ?
            LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $seasonId);
    $stmt->execute();

    $season = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$season) {
        http_response_code(404);
        exit('Season not found.');
    }

    // 4. Clear the season's transaction log
    try {
        $conn->begin_transaction();

        $sql = "DELETE rt
                FROM roster_transactions AS rt
                JOIN active_users AS au
                    ON rt.active_user_id = au.id
                WHERE au.season_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $seasonId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        header(
            "Location: ../../season/admin.php?seasonId=" . $seasonId
        );
        exit;

    } catch (Throwable $e) {
        $conn->rollback();

        error_log($e->getMessage());

        http_response_code(500);
        exit('Failed to clear transaction log.');
    }