<?php

    require_once __DIR__ . '/../../includes/connection.php';

    header('Content-Type: application/json');

    // -------------
    // GET SEASON ID
    // -------------

    // $seasonId = 1;

    // Get season ID from POST
        $seasonId = filter_input(
            INPUT_POST,
            'seasonId',
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        // Validate season ID
        if ($seasonId === false || $seasonId === null) {
            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "Invalid season ID."
            ]);

            exit;
        }

        // Check that season exists
        $seasonSql ="SELECT id
                    FROM seasons
                    WHERE id = ?
                    LIMIT 1
        ";

        $seasonStmt = $conn->prepare($seasonSql);

        if (!$seasonStmt) {
            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" => "Failed to prepare season query."
            ]);

            exit;
        }

        $seasonStmt->bind_param("i", $seasonId);
        $seasonStmt->execute();

        $seasonResult = $seasonStmt->get_result();
        $season = $seasonResult->fetch_assoc();

        $seasonStmt->close();

        if (!$season) {
            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "Season not found."
            ]);

            exit;
        }





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
