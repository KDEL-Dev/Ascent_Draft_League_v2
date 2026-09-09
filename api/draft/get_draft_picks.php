<?php

    require_once __DIR__ . '/../../includes/connection.php';

    header('Content-Type: application/json');

    $seasonId = 1;

    $sql = "
        SELECT
            draft_picks.pick_number,
            draft_picks.round_number,
            draft_picks.active_user_id,

            active_users.team_name,

            showdown_pokemon.id AS pokemon_id,
            showdown_pokemon.name,
            showdown_pokemon.type1,
            showdown_pokemon.type2,

            pokemon_tier_per_season.tier

        FROM draft_picks

        JOIN active_users
            ON active_users.id = draft_picks.active_user_id

        JOIN showdown_pokemon
            ON showdown_pokemon.id = draft_picks.showdown_pokemon_id

        JOIN pokemon_tier_per_season
            ON pokemon_tier_per_season.showdown_pokemon_id =
            draft_picks.showdown_pokemon_id

            AND pokemon_tier_per_season.season_id = draft_picks.season_id

        WHERE draft_picks.season_id = ?

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

    $stmt->bind_param("i", $seasonId);

    $stmt->execute();

    $result = $stmt->get_result();

    $picks = [];

    while ($row = $result->fetch_assoc()) {

        $picks[] = [
            "pick_number" => (int) $row["pick_number"],
            "round_number" => (int) $row["round_number"],
            "active_user_id" => (int) $row["active_user_id"],
            "team_name" => $row["team_name"],
            "pokemon_id" => (int) $row["pokemon_id"],
            "name" => $row["name"],
            "type1" => $row["type1"],
            "type2" => $row["type2"],
            "tier" => $row["tier"]
        ];
    }

    echo json_encode([
        "success" => true,
        "picks" => $picks
    ]);
