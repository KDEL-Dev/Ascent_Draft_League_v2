<?php

    session_start();

    header('Content-Type: application/json');

    require_once __DIR__ . '/../../includes/connection.php';


    // --------------------------------------------------
    // CHECK LOGIN
    // --------------------------------------------------

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);

        echo json_encode([
            'status' => 'error',
            'error' => 'You must be logged in.'
        ]);

        exit;
    }

    $userId = (int)$_SESSION['user_id'];

    $seasonId = 1;


    // --------------------------------------------------
    // GET POST DATA
    // --------------------------------------------------

    $data = json_decode(file_get_contents('php://input'), true);

    $pokemonId = isset($data['pokemon_id'])
        ? (int)$data['pokemon_id']
        : 0;

    if ($pokemonId <= 0) {
        http_response_code(400);

        echo json_encode([
            'status' => 'error',
            'error' => 'Invalid Pokémon.'
        ]);

        exit;
    }


    // --------------------------------------------------
    // GET ACTIVE USER
    // --------------------------------------------------

    $sql = "
        SELECT
            id,
            transactions_left
        FROM active_users
        WHERE user_id = ?
        AND season_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        http_response_code(500);

        echo json_encode([
            'status' => 'error',
            'error' => 'Database error.'
        ]);

        exit;
    }

    $stmt->bind_param("ii", $userId, $seasonId);
    $stmt->execute();

    $result = $stmt->get_result();

    $activeUser = $result->fetch_assoc();

    $stmt->close();

    if (!$activeUser) {
        http_response_code(404);

        echo json_encode([
            'status' => 'error',
            'error' => 'Active user not found.'
        ]);

        exit;
    }

    $activeUserId = (int)$activeUser['id'];

    // --------------------------------------------------
// CHECK ROSTER COUNT
// --------------------------------------------------

$sql = "SELECT COUNT(*) AS roster_count
        FROM roster_pkmn
        WHERE active_user_id = ?
        AND season_id = ?
        AND status = 'active'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $activeUserId, $seasonId);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$stmt->close();

$rosterCount = (int)$row['roster_count'];


// Maximum roster size
$maxRosterSize = 12;

if ($rosterCount >= $maxRosterSize) {

    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'error' => 'Your roster is already full.'
    ]);

    exit;
}


// --------------------------------------------------
// CHECK IF ALREADY OWNED
// --------------------------------------------------

$sql = "SELECT id
        FROM roster_pkmn
        WHERE active_user_id = ?
        AND season_id = ?
        AND showdown_pokemon_id = ?
        AND status = 'active'
        LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iii",
    $activeUserId,
    $seasonId,
    $pokemonId
);

$stmt->execute();

$result = $stmt->get_result();

$alreadyOwned = $result->fetch_assoc();

$stmt->close();

if ($alreadyOwned) {

    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'error' => 'You already own this Pokémon.'
    ]);

    exit;
}


// --------------------------------------------------
// CHECK POKÉMON EXISTS IN CURRENT SEASON
// --------------------------------------------------

$sql = "SELECT showdown_pokemon_id
        FROM pokemon_tier_per_season
        WHERE showdown_pokemon_id = ?
        AND season_id = ?
        LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $pokemonId, $seasonId);
$stmt->execute();

$result = $stmt->get_result();

$pokemonExists = $result->fetch_assoc();

$stmt->close();

if (!$pokemonExists) {

    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'error' => 'That Pokémon is not available in this season.'
    ]);

    exit;
}


// --------------------------------------------------
// BEGIN DATABASE TRANSACTION
// --------------------------------------------------

$conn->begin_transaction();

try {

    // ----------------------------------------------
    // ADD POKÉMON TO ROSTER
    // ----------------------------------------------

    $sql = "INSERT INTO roster_pkmn (
            season_id,
            active_user_id,
            showdown_pokemon_id,
            status
        )
            VALUES (?, ?, ?, 'active')
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Failed to prepare roster insert.');
    }

    $stmt->bind_param(
        "iii",
        $seasonId,
        $activeUserId,
        $pokemonId
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to add Pokémon to roster.');
    }

    $stmt->close();


    // ----------------------------------------------
    // RECORD TRANSACTION
    // ----------------------------------------------

    $transactionType = 'add';

    $sql = "
        INSERT INTO roster_transactions (
            active_user_id,
            added_pokemon_id,
            dropped_pokemon_id,
            transaction_type
        )
        VALUES (?, ?, NULL, ?)
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Failed to prepare transaction insert.');
    }

    $stmt->bind_param(
        "iis",
        $activeUserId,
        $pokemonId,
        $transactionType
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to record transaction.');
    }

    $stmt->close();


    // ----------------------------------------------
    // COMMIT
    // ----------------------------------------------

    $conn->commit();


    echo json_encode([
        'status' => 'success',
        'message' => 'Pokémon added to your roster.'
    ]);

} catch (Exception $e) {

    $conn->rollback();

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}