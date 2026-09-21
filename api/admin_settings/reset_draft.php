<?php

    require_once __DIR__ . '/../../includes/connection.php';

    $seasonId = 1;

    try 
    {
        // Start transaction
        $conn->begin_transaction();

        // 1. Delete draft picks
        $sql = "
            DELETE FROM draft_picks
            WHERE season_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $seasonId);
        $stmt->execute();
        $stmt->close();


        // 2. Delete roster Pokémon
        $sql = "
            DELETE FROM roster_pkmn
            WHERE season_id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $seasonId);
        $stmt->execute();
        $stmt->close();


        // 3. Reset draft state
        $sql = "
            UPDATE draft_state
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

        header("Location: ../../season/admin.php");
        exit;   

    } 
    catch (Exception $e) 
    {

        // Something failed — undo everything
        $conn->rollback();

        echo "Failed to reset draft: " . $e->getMessage();
    }
