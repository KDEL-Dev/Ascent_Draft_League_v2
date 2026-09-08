<?php

    error_reporting(E_ALL);
    ini_set('display_errors', 1);    

    require_once __DIR__ . '/../../includes/connection.php';

    header('Content-Type: application/json');

    $seasonId = 1;

    // Get data from Javascript
    $data = json_decode(file_get_contents("php://input"), true);

    $pokemonId = $data['pokemon_id'] ?? null;

    if(!$pokemonId)
    {
        echo json_encode([
            "success" => false,
            "message" => "No Pokemon selected"
        ]);
        exit;
    }

    // Get Draft State

    $sql = "
    SELECT draft_position, current_round, total_picks, is_active
    FROM draft_state
    WHERE season_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $seasonId);
    $stmt->execute();

    $result = $stmt->get_result();
    $draftState = $result->fetch_assoc();

    if (!$draftState) {
        echo json_encode([
            "success" => false,
            "message" => "Draft state not found."
        ]);
        exit;
    }

    if (!$draftState['is_active']) {
        echo json_encode([
            "success" => false,
            "message" => "Draft is not active."
        ]);
        exit;
    }

    // Get Current Draft User

    $sql = "
    SELECT id, team_name, draft_position
    FROM active_users
    WHERE season_id = ?
    AND draft_position = ?
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $seasonId,
        $draftState['draft_position']
    );

    $stmt->execute();

    $result = $stmt->get_result();
    $currentUser = $result->fetch_assoc();

    if (!$currentUser) {
        echo json_encode([
            "success" => false,
            "message" => "Could not find the current drafter."
        ]);
        exit;
    }

    // ----------
    // TEAM COUNT
    // ----------

    $sql = "
        SELECT COUNT(*) AS team_count
        FROM active_users
        WHERE season_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $seasonId);
    $stmt->execute();

    $result = $stmt->get_result();
    $teamCount = $result->fetch_assoc()['team_count'];

    // --------------------------------
    // CHECK IF POKEMON ALREADY DRAFTED
    // --------------------------------

    // Check if Pokemon has already been drafted

    $sql = "
        SELECT id
        FROM draft_picks
        WHERE season_id = ?
        AND showdown_pokemon_id = ?
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $seasonId,
        $pokemonId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            "success" => false,
            "message" => "That Pokemon has already been drafted."
        ]);
        exit;
    }


    // --------------
    // INSERT into dB
    // --------------

    $pickNumber = $draftState['total_picks'] + 1;
    $roundNumber = $draftState['current_round'];

    $sql = "
        INSERT INTO draft_picks
        (
            season_id,
            active_user_id,
            showdown_pokemon_id,
            pick_number,
            round_number
        )
        VALUES (?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iiiii",
        $seasonId,
        $currentUser['id'],
        $pokemonId,
        $pickNumber,
        $roundNumber
    );

    if (!$stmt->execute()) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to save pick: " . $stmt->error
        ]);
        exit;
    }

    



    // -------------------
    // Advance Draft State
    // -------------------

    $newTotalPicks = $draftState['total_picks'] + 1;

    $currentPosition = $draftState['draft_position'];
    $currentRound = $draftState['current_round'];

    // Odd rounds move forward
    if($currentRound % 2 === 1)
    {
        if($currentPosition < $teamCount)
        {
            // Keep moving forward
            $newDraftPosition = $currentPosition + 1;
            $newRound = $currentRound;
        }
        else
        {
            // End of round
            // Same team gets the first pick
            // of the next round
            $newDraftPosition = $teamCount;
            $newRound = $currentRound + 1;
        }
    }
    // Even rounds move backward
    else
    {
        if($currentPosition > 1)
        {
            // Keep moving backward
            $newDraftPosition = $currentPosition - 1;
            $newRound = $currentRound;
        }
        else
        {
            // End of round
            // Same team gets first pick
            // of the next round

            $newDraftPosition = 1;
            $newRound = $currentRound + 1;
        }
    }

    $sql = "
        UPDATE draft_state
        SET
            total_picks = ?,
            draft_position = ?,
            current_round = ?
        WHERE season_id = ?
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iiii",
        $newTotalPicks,
        $newDraftPosition,
        $newRound,
        $seasonId
    );

    if (!$stmt->execute()) {
        echo json_encode([
            "success" => false,
            "message" => "Pick was saved, but draft state failed to update."
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Pick saved successfully."
    ]);

?>