<?php

require_once __DIR__ . '/../../includes/auth.php';

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




    // GET TEAMS
$team1Id = $_GET['team1_id'] ?? null;
$team2Id = $_GET['team2_id'] ?? null;

if (!$team1Id || !$team2Id) {
    echo json_encode([
        "success" => false,
        "message" => "Both team IDs are required."
    ]);
    exit;
}

$sql = "SELECT
        roster_pkmn.active_user_id,
        roster_pkmn.id AS roster_pokemon_id,
        roster_pkmn.showdown_pokemon_id,

        users.default_team_name,
        users.default_team_mascot,

        showdown_pokemon.id AS pokemon_id,
        showdown_pokemon.name,
        showdown_pokemon.type1,
        showdown_pokemon.type2

    FROM roster_pkmn

    JOIN active_users
        ON active_users.id = roster_pkmn.active_user_id

    JOIN users
        ON users.id = active_users.user_id

    JOIN showdown_pokemon
        ON showdown_pokemon.id = roster_pkmn.showdown_pokemon_id

    WHERE roster_pkmn.season_id = ?
    AND roster_pkmn.active_user_id IN (?, ?)
    AND roster_pkmn.status = 'active'

    ORDER BY
        roster_pkmn.active_user_id,
        roster_pkmn.id
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
    "iii",
    $seasonId,
    $team1Id,
    $team2Id
);

$stmt->execute();

$result = $stmt->get_result();

$team1Roster = [];
$team2Roster = [];

$team1Name = '';
$team1Mascot = '';

$team2Name = '';
$team2Mascot = '';


while ($pokemon = $result->fetch_assoc()) 
{

    if ($pokemon['active_user_id'] == $team1Id) {

        $team1Roster[] = $pokemon;

        $team1Name = $pokemon['default_team_name'];
        $team1Mascot = $pokemon['default_team_mascot'];
    }

    if ($pokemon['active_user_id'] == $team2Id) {

        $team2Roster[] = $pokemon;

        $team2Name = $pokemon['default_team_name'];
        $team2Mascot = $pokemon['default_team_mascot'];
    }
}


echo json_encode([
    "success" => true,

    "team1_roster" => $team1Roster,
    "team2_roster" => $team2Roster,

    "team1_name" => $team1Name,
    "team1_mascot" => $team1Mascot,

    "team2_name" => $team2Name,
    "team2_mascot" => $team2Mascot
]);

