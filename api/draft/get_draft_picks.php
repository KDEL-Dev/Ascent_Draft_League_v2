<?php

    require_once __DIR__ . '/../../includes/connection.php';

    header('Content-Type: application/json');

    $seasonId = 1;

    $sql = "SELECT
            draft_picks.pick_number,
            draft_picks.round_number,
            draft_picks.active_user_id,

            users.default_team_name,

            showdown_pokemon.id AS pokemon_id,
            showdown_pokemon.name,
            showdown_pokemon.type1,
            showdown_pokemon.type2,
            showdown_pokemon.hp,
            showdown_pokemon.attack,
            showdown_pokemon.defense,
            showdown_pokemon.sp_attack,
            showdown_pokemon.sp_defense,
            showdown_pokemon.speed,
            showdown_pokemon.ability_1,
            showdown_pokemon.ability_2,
            showdown_pokemon.hidden_ability,

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
        JOIN users
        ON active_users.user_id = users.id 

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
            "team_name" => $row["default_team_name"],
            "pokemon_id" => (int) $row["pokemon_id"],
            "name" => $row["name"],
            "type1" => $row["type1"],
            "type2" => $row["type2"],
            "tier" => $row["tier"],
            "hp" => (int) $row["hp"],
            "attack" => (int) $row["attack"],
            "defense" => (int) $row["defense"],
            "sp_attack" => (int) $row["sp_attack"],
            "sp_defense" => (int) $row["sp_defense"],
            "speed" => (int) $row["speed"],
            "ability_1" => $row["ability_1"],
            "ability_2" =>  $row["ability_2"],
            "hidden_ability" =>  $row["hidden_ability"]
        ];
    }

    echo json_encode([
        "success" => true,
        "picks" => $picks
    ]);
