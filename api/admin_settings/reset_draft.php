<?php

    require_once __DIR__ . '/../../includes/auth.php';

    // $seasonId = 1;
    // 1. Check admin authorization
    if (!$isAdmin) {
        http_response_code(403);
        exit('Access denied.');
    }

    // 2. Get season ID from POST
    $seasonId = filter_input(
        INPUT_POST,
        'seasonId',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    // 3. Validate ID
    if ($seasonId === false || $seasonId === null) {
        http_response_code(400);
        exit('Invalid season ID.');
    }

    // 4. Check that season exists
    $seasonSql ="SELECT id
                FROM seasons
                WHERE id = ?
                LIMIT 1
    ";

    $seasonStmt = $conn->prepare($seasonSql);
    $seasonStmt->bind_param("i", $seasonId);
    $seasonStmt->execute();

    $seasonResult = $seasonStmt->get_result();
    $season = $seasonResult->fetch_assoc();

    $seasonStmt->close();

    if (!$season) {
        http_response_code(404);
        exit('Season not found.');
    }







    // --------------
    // START OF RESET
    // --------------

    try 
    {
        // Start transaction
        $conn->begin_transaction();

        // 1. Delete draft picks
        $sql = "DELETE FROM 
                draft_picks
                WHERE 
                season_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $seasonId);
        $stmt->execute();
        $stmt->close();


        // 2. Delete roster Pokémon
        $sql = "DELETE FROM 
                roster_pkmn
                WHERE 
                season_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $seasonId);
        $stmt->execute();
        $stmt->close();


        // 3. Reset draft state
        $sql = "UPDATE draft_state
                SET
                draft_position = 1,
                current_round = 1,
                is_active = 0,
                started_at = NULL,
                pick_started_at = NULL,
                timer_remaining = 60,
                total_picks = 0,
                status = 'pending'
                WHERE season_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $seasonId);
        $stmt->execute();
        $stmt->close();


        // Everything succeeded
        $conn->commit();

        header("Location: ../../season/admin.php?seasonId=" . $seasonId);
        exit;   

    } 
    catch (Exception $e) 
    {

        // Something failed — undo everything
        $conn->rollback();

        echo "Failed to reset draft: " . $e->getMessage();
    }
