<?php

    require_once __DIR__ . '/../../includes/connection.php';

    header('Content-Type: application/json');

    $seasonId = 1;
    $activeUserId = 6; // Temporary for testing

    $sql = "
        SELECT
            draft_picks.pick_number,
            draft_picks.round_number,
            showdown_pokemon.id AS pokemon_id,
            showdown_pokemon.name,
            showdown_pokemon.type1,
            showdown_pokemon.type2
        FROM draft_picks
        JOIN showdown_pokemon
            ON showdown_pokemon.id = draft_picks.showdown_pokemon_id
        WHERE draft_picks.season_id = ?
        AND draft_picks.active_user_id = ?
        ORDER BY draft_picks.pick_number ASC
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to prepare query."
        ]);
        exit;
    }

    $stmt->bind_param(
        "ii",
        $seasonId,
        $activeUserId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $roster = [];

    while ($pokemon = $result->fetch_assoc()) {
        $roster[] = $pokemon;
    }

    echo json_encode([
        "success" => true,
        "roster" => $roster
    ]);
