<?php

    // ini_set('display_errors', '0');
    // ini_set('log_errors', '1');
    // error_reporting(E_ALL);

    // register_shutdown_function(function () {

    //     $error = error_get_last();

    //     if ($error !== null) {

    //         error_log(
    //             "MAKE_PICK FATAL ERROR: " .
    //             json_encode($error)
    //         );
    //     }

    // });

    // Load environment variables
    require_once __DIR__ . '/../../vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();


    
    

    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json');

    $userId = $_SESSION['user_id'] ?? null;

    // BROADCAST OLD VERSION BELOW
    // function broadcastDraftEvent(array $data): void
    // {
    //     file_put_contents(
    //         __DIR__ . '/broadcast_debug.txt',
    //         date('Y-m-d H:i:s') . " BROADCAST CALLED\n" .
    //         json_encode($data) . "\n\n",
    //         FILE_APPEND
    //     );

    //     $url = 'http://localhost/ascent_draft_league_v2/api/draft/broadcast.php';

    //     $ch = curl_init($url);

    //     curl_setopt($ch, CURLOPT_POST, true);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //         'Content-Type: application/json'
    //     ]);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //     $response = curl_exec($ch);

    //     file_put_contents(
    //         __DIR__ . '/broadcast_debug.txt',
    //         "HTTP CODE: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n" .
    //         "RESPONSE: " . $response . "\n\n",
    //         FILE_APPEND
    //     );

    //     if ($response === false) {
    //         file_put_contents(
    //             __DIR__ . '/broadcast_debug.txt',
    //             "CURL ERROR: " . curl_error($ch) . "\n\n",
    //             FILE_APPEND
    //         );
    //     }

    //     curl_close($ch);
    // }




    // BROADCAST NEW - ONLINE
    // function broadcastDraftEvent(array $data): void
    // {
    //     // Replace with your actual Render URL and secret key
    //     $url = 'https://ascent-web-server.onrender.com/broadcast';
    //     // $secret = getenv('BROADCAST_SECRET') ?: 'YOUR_BROADCAST_SECRET';
    //     $secret = $_ENV['BROADCAST_SECRET'];

    //     $ch = curl_init($url);

    //     curl_setopt($ch, CURLOPT_POST, true);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //         'Content-Type: application/json',
    //         'Authorization: Bearer ' . $secret
    //     ]);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //     $response = curl_exec($ch);
    //     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    //     file_put_contents(
    //         __DIR__ . '/broadcast_debug.txt',
    //         date('Y-m-d H:i:s') . " BROADCAST CALLED\n" .
    //         "HTTP CODE: " . $httpCode . "\n" .
    //         "RESPONSE: " . $response . "\n\n",
    //         FILE_APPEND
    //     );

    //     if ($response === false) {
    //         file_put_contents(
    //             __DIR__ . '/broadcast_debug.txt',
    //             "CURL ERROR: " . curl_error($ch) . "\n\n",
    //             FILE_APPEND
    //         );
    //     }

    //     curl_close($ch);
    // }

    function broadcastDraftEvent(array $data): bool
{
    $url = 'https://ascent-web-server.onrender.com/broadcast';

    $secret = $_ENV['BROADCAST_SECRET'] ?? '';

    if (empty($secret)) {
        error_log("BROADCAST ERROR: Missing secret.");
        return false;
    }

    $ch = curl_init($url);

    if (!$ch) {
        error_log("BROADCAST ERROR: Could not initialize cURL.");
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $secret
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false || $curlError) {
        error_log("BROADCAST CURL ERROR: " . $curlError);
        return false;
    }

    error_log("BROADCAST HTTP CODE: " . $httpCode);
    error_log("BROADCAST RESPONSE: " . $response);

    return $httpCode === 200;
}


    // ----------------------
    // GET JSON REQUEST DATA
    // ----------------------

    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data)) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON request."
        ]);

        exit;
    }

    // Get season ID from JSON
    $seasonId = filter_var(
        $data['seasonId'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    // Get Pokemon ID from JSON
    $pokemonId = filter_var(
        $data['pokemon_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    // Validate IDs
    if ($seasonId === false || $seasonId === null) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid season ID."
        ]);

        exit;
    }

    if ($pokemonId === false || $pokemonId === null) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid Pokemon ID."
        ]);

        exit;
    }

    // Check that season exists
    $seasonSql = "SELECT id
                FROM seasons
                WHERE id = ?
                LIMIT 1";

    $seasonStmt = $conn->prepare($seasonSql);
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

    

    //-------------------
    // GET ACTIVE USER ID
    //-------------------

    $sql = "SELECT id
        FROM active_users
        WHERE user_id = ?
        AND season_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $seasonId);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            http_response_code(403);

            echo json_encode([
                "success" => false,
                "message" => "You are not active in this season."
            ]);

            exit;
        }

        $activeUserId = (int)$row['id'];
        $stmt->close();

   



    // ---------------
    // Get Draft State
    // ---------------

    $sql = "SELECT draft_position, current_round, total_picks, is_active
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

    $sql = "SELECT
            active_users.id,
            users.default_team_name,
            active_users.draft_position
        FROM active_users
        JOIN users ON active_users.user_id = users.id
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

    if ((int)$currentUser['id'] !== (int)$activeUserId) {
        echo json_encode([
            "success" => false,
            "message" => "It is not your turn."
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

    $sql = "SELECT id
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

    // -------------------------
    // GET POKEMON TIER
    // -------------------------

    $sql = "SELECT tier
            FROM pokemon_tier_per_season
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
    $pokemonTierData = $result->fetch_assoc();

    if (!$pokemonTierData) {
        echo json_encode([
            "success" => false,
            "message" => "Could not find Pokémon tier."
        ]);
        exit;
    }

    $pokemonTier = $pokemonTierData['tier'];

    // -------------------------
    // DETERMINE TIER GROUP
    // -------------------------

    if (in_array($pokemonTier, ['OU', 'UUBL'])) {
        $tierGroup = 'OU';
    }
    elseif (in_array($pokemonTier, ['UU', 'RUBL'])) {
        $tierGroup = 'UU';
    }
    elseif (in_array($pokemonTier, ['RU', 'NUBL'])) {
        $tierGroup = 'RU';
    }
    elseif (in_array($pokemonTier, ['NU', 'PUBL', 'PU', 'ZUBL', 'ZU'])) {
        $tierGroup = 'NU';
    }
    else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid Pokémon tier."
        ]);
        exit;
    }

    // -------------------------
    // CHECK TIER LIMIT
    // -------------------------

    $tierCountSql ="SELECT COUNT(*) AS tier_count
                    FROM draft_picks dp
                    JOIN pokemon_tier_per_season pt
                        ON pt.showdown_pokemon_id = dp.showdown_pokemon_id
                        AND pt.season_id = dp.season_id
                    WHERE dp.season_id = ?
                    AND dp.active_user_id = ?
                    AND (
                        (? = 'OU' AND pt.tier IN ('OU', 'UUBL'))
                        OR
                        (? = 'UU' AND pt.tier IN ('UU', 'RUBL'))
                        OR
                        (? = 'RU' AND pt.tier IN ('RU', 'NUBL'))
                        OR
                        (? = 'NU' AND pt.tier IN ('NU', 'PUBL', 'PU', 'ZUBL', 'ZU'))
                    )
    ";

    $stmt = $conn->prepare($tierCountSql);

    $stmt->bind_param(
        "iissss",
        $seasonId,
        $activeUserId,
        $tierGroup,
        $tierGroup,
        $tierGroup,
        $tierGroup
    );

    $stmt->execute();

    $result = $stmt->get_result();
    $tierCount = (int)$result->fetch_assoc()['tier_count'];

    if ($tierCount >= 3) {
        echo json_encode([
            "success" => false,
            "message" => "You already have 3 Pokémon from the {$tierGroup} tier."
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


    // -------------------------
    // INSERT INTO ROSTER
    // -------------------------

    $sql = "
        INSERT INTO roster_pkmn
        (
            season_id,
            active_user_id,
            showdown_pokemon_id,
            status
        )
        VALUES (?, ?, ?, 'active')
    ";

    $rosterStmt = $conn->prepare($sql);

    $rosterStmt->bind_param(
        "iii",
        $seasonId,
        $currentUser['id'],
        $pokemonId
    );

    if (!$rosterStmt->execute()) {
        echo json_encode([
            "success" => false,
            "message" => "Pick was saved, but roster Pokémon failed to save: " . $rosterStmt->error
        ]);
        exit;
    }

    $rosterStmt->close();


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

    $sql = "UPDATE draft_state
            SET
                total_picks = ?,
                draft_position = ?,
                current_round = ?,
                pick_started_at = NOW()
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


    // -------------------------
    // BROADCAST DRAFT PICK
    // -------------------------

    $draftEvent = [
        "type" => "draft_pick",
        "pick_number" => $pickNumber,
        "round_number" => $roundNumber,
        "active_user_id" => $currentUser['id'],
        "team_name" => $currentUser['default_team_name'],
        "pokemon_id" => $pokemonId
    ];

    // broadcastDraftEvent($draftEvent);
    // error_log("ABOUT TO BROADCAST: " . json_encode($draftEvent));

    // broadcastDraftEvent($draftEvent);

    // error_log("BROADCAST FUNCTION FINISHED");

    error_log("ABOUT TO BROADCAST");

    $broadcastResult = broadcastDraftEvent($draftEvent);

    error_log("BROADCAST RESULT: " . ($broadcastResult ? "SUCCESS" : "FAILED"));

    error_log("BROADCAST FUNCTION FINISHED");



    // -------------------------
    // API RESPONSE
    // -------------------------

    echo json_encode([
        "success" => true,
        "message" => "Pick saved successfully."
    ]);


?>