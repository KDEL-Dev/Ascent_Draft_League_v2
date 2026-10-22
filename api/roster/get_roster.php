<?php


    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json');

    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        echo json_encode([
            "success" => false,
            "message" => "User is not logged in."
        ]);
        exit;
    }

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

    // ----------------------
    // GET ACTIVE USER ID
    // ----------------------

    $sql = "
        SELECT id
        FROM active_users
        WHERE user_id = ?
        AND season_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to prepare active user query."
        ]);
        exit;
    }

    $stmt->bind_param("ii", $userId, $seasonId);
    $stmt->execute();

    $result = $stmt->get_result();
    $activeUser = $result->fetch_assoc();

    if (!$activeUser) {
        echo json_encode([
            "success" => false,
            "message" => "User is not active in this season."
        ]);
        exit;
    }

    $activeUserId = $activeUser['id'];

    // END OF ACTIVE USER
    
        $sql = "
        SELECT
            draft_picks.pick_number,
            draft_picks.round_number,

            showdown_pokemon.id AS pokemon_id,
            showdown_pokemon.name,
            showdown_pokemon.type1,
            showdown_pokemon.type2,

            pokemon_tier_per_season.tier

        FROM draft_picks

        JOIN showdown_pokemon
            ON showdown_pokemon.id = draft_picks.showdown_pokemon_id

        JOIN pokemon_tier_per_season
            ON pokemon_tier_per_season.showdown_pokemon_id = showdown_pokemon.id
            AND pokemon_tier_per_season.season_id = draft_picks.season_id

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

        // --------------
        // Roster Counter
        // --------------

        $rosterCount = count($roster);

        $tierCounts = [
            "ou" => 0,
            "uu" => 0,
            "ru" => 0,
            "nu" => 0
        ];

        foreach ($roster as $pokemon) {

            $tier = $pokemon['tier'];

            if (in_array($tier, ['OU', 'UUBL'])) {
                $tierCounts['ou']++;
            }
            elseif (in_array($tier, ['UU', 'RUBL'])) {
                $tierCounts['uu']++;
            }
            elseif (in_array($tier, ['RU', 'NUBL'])) {
                $tierCounts['ru']++;
            }
            elseif (in_array($tier, ['NU', 'PUBL', 'PU', 'ZUBL', 'ZU'])) {
                $tierCounts['nu']++;
            }
        }



        echo json_encode([
            "success" => true,
            "roster_count" => $rosterCount,
            "roster_limit" => 12, // remove hardcoded limit later
            "tier_counts" => $tierCounts,
            "roster" => $roster
        ]);

