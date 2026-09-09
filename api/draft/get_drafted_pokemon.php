<?php

    require_once __DIR__ . '/../../includes/connection.php';

    header('Content-Type: application/json');

    $seasonId = 1;

    // Get every Pokemon that has already been drafted
    $sql = "
        SELECT showdown_pokemon_id
        FROM draft_picks
        WHERE season_id = ?
        ORDER BY pick_number ASC
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to prepare query."
        ]);
        exit;
    }

    $stmt->bind_param("i", $seasonId);
    $stmt->execute();

    $result = $stmt->get_result();

    $draftedPokemon = [];

    while ($row = $result->fetch_assoc()) {
        $draftedPokemon[] = (int) $row['showdown_pokemon_id'];
    }

    echo json_encode([
        "success" => true,
        "drafted_pokemon" => $draftedPokemon
    ]);
