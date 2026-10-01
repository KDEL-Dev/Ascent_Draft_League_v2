<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/connection.php';

header('Content-Type: application/json');


// -------------------------------------------------
// GET JSON DATA
// -------------------------------------------------

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data.'
    ]);
    exit;
}


// -------------------------------------------------
// GET MATCH DATA
// -------------------------------------------------

$seasonId = $data['season_id'] ?? null;
$player1Id = $data['player1_au_id'] ?? null;
$player2Id = $data['player2_au_id'] ?? null;
$matchResult = $data['match_result'] ?? null;
$matchReplay = trim($data['match_replay'] ?? '');

$team1Pokemon = $data['team1_pokemon'] ?? [];
$team2Pokemon = $data['team2_pokemon'] ?? [];


// -------------------------------------------------
// BASIC VALIDATION
// -------------------------------------------------

if (
    !$seasonId ||
    !$player1Id ||
    !$player2Id ||
    !$matchResult
) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing match information.'
    ]);
    exit;
}

if ($player1Id == $player2Id) {
    echo json_encode([
        'success' => false,
        'message' => 'A team cannot play against itself.'
    ]);
    exit;
}

if ($matchResult != $player1Id && $matchResult != $player2Id) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid match winner.'
    ]);
    exit;
}

if (!$matchReplay) {
    echo json_encode([
        'success' => false,
        'message' => 'Replay link is required.'
    ]);
    exit;
}


// -------------------------------------------------
// START TRANSACTION
// -------------------------------------------------

$conn->begin_transaction();

try {

    // -------------------------------------------------
    // INSERT MATCH
    // -------------------------------------------------

    $sql = "
        INSERT INTO match_info
        (
            season_id,
            player1_au_id,
            player2_au_id,
            match_result,
            match_replay
        )
        VALUES (?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Failed to prepare match insert.');
    }

    $stmt->bind_param(
        "iiiis",
        $seasonId,
        $player1Id,
        $player2Id,
        $matchResult,
        $matchReplay
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to save match.');
    }


    // Get newly created match ID
    $matchupId = $conn->insert_id;


    // -------------------------------------------------
    // PREPARE POKEMON INSERT
    // -------------------------------------------------

    $sql = "
        INSERT INTO match_stats
        (
            matchup_id,
            roster_pkmn_id,
            kills,
            deaths
        )
        VALUES (?, ?, ?, ?)
    ";

    $pokemonStmt = $conn->prepare($sql);

    if (!$pokemonStmt) {
        throw new Exception('Failed to prepare Pokémon stats insert.');
    }


    // -------------------------------------------------
    // TEAM 1 POKEMON
    // -------------------------------------------------

    foreach ($team1Pokemon as $pokemon) {

        $rosterPokemonId = $pokemon['roster_pkmn_id'];
        $kills = $pokemon['kills'];
        $deaths = $pokemon['deaths'];

        $pokemonStmt->bind_param(
            "iiii",
            $matchupId,
            $rosterPokemonId,
            $kills,
            $deaths
        );

        if (!$pokemonStmt->execute()) {
            throw new Exception('Failed to save Team 1 Pokémon stats.');
        }
    }


    // -------------------------------------------------
    // TEAM 2 POKEMON
    // -------------------------------------------------

    foreach ($team2Pokemon as $pokemon) {

        $rosterPokemonId = $pokemon['roster_pkmn_id'];
        $kills = $pokemon['kills'];
        $deaths = $pokemon['deaths'];

        $pokemonStmt->bind_param(
            "iiii",
            $matchupId,
            $rosterPokemonId,
            $kills,
            $deaths
        );

        if (!$pokemonStmt->execute()) {
            throw new Exception('Failed to save Team 2 Pokémon stats.');
        }
    }


    // -------------------------------------------------
    // EVERYTHING SUCCESSFUL
    // -------------------------------------------------

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Match saved successfully.',
        'matchup_id' => $matchupId
    ]);

} catch (Exception $e) {

    // Something failed — undo everything
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
