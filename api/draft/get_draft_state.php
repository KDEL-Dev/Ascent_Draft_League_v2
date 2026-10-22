<?php

    error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

    require_once __DIR__ . '/../../includes/auth.php';
    header('Content-Type: application/json');


    

    // -------------------------
    // GET SEASON ID FROM URL
    // -------------------------

    $seasonId = filter_input(
        INPUT_GET,
        'seasonId',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($seasonId === false || $seasonId === null)
    {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid or missing season ID.'
        ]);

        exit;
    }

    // -------------------------
    // VERIFY SEASON EXISTS
    // -------------------------

    $seasonSql = "SELECT id
                FROM seasons
                WHERE id = ?
                LIMIT 1";

    $seasonStmt = $conn->prepare($seasonSql);

    if (!$seasonStmt)
    {
        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Failed to prepare season query.'
        ]);

        exit;
    }

    $seasonStmt->bind_param("i", $seasonId);
    $seasonStmt->execute();

    $seasonResult = $seasonStmt->get_result();
    $season = $seasonResult->fetch_assoc();

    $seasonStmt->close();

    if (!$season)
    {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Season not found.'
        ]);

        exit;
    }

    // -----------
    // GET USER ID
    // -----------


    $userId = $_SESSION['user_id'] ?? null;
    

    if (!$userId) {
        echo json_encode([
            'success' => false,
            'message' => 'User is not logged in'
        ]);
        exit;
    }

    // -------------------------
    // GET LOGGED-IN ACTIVE USER
    // -------------------------

    $sql = "SELECT
            active_users.id,
            users.default_team_name,
            active_users.draft_position
        FROM active_users
        JOIN users
            ON active_users.user_id = users.id
        WHERE active_users.user_id = ?
        AND active_users.season_id = ?
        LIMIT 1";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to prepare active user query.'
        ]);
        exit;
    }

    $stmt->bind_param("ii", $userId, $seasonId);
    $stmt->execute();

    $result = $stmt->get_result();
    $myActiveUser = $result->fetch_assoc();

    $stmt->close();

    if (!$myActiveUser) {
        echo json_encode([
            'success' => false,
            'message' => 'You are not an active user for this season.'
        ]);
        exit;
    }



$sql = "SELECT
        is_active,
        status,
        started_at,
        pick_started_at,
        paused_at,
        timer_remaining,
        draft_position,
        current_round,
        total_picks
        FROM 
        draft_state
        WHERE 
        season_id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Prepare failed'
    ]);
    exit;
}

$stmt->bind_param("i", $seasonId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Draft state not found'
    ]);
    exit;
}

$draftState = $result->fetch_assoc();

// -------------------------
// GET DRAFT DIRECTION
// -------------------------

    $draftState['draft_direction'] =
    ($draftState['current_round'] % 2 === 1)
        ? 'forward'
        : 'backward';

// -------------------------
// GET TEAM ON THE CLOCK
// -------------------------

$sql = "SELECT
            active_users.id,
            users.default_team_name,
            active_users.draft_position
        FROM active_users
        JOIN users
        ON active_users.user_id = users.id
        WHERE season_id = ?
        AND draft_position = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Prepare failed when finding current drafter'
    ]);
    exit;
}

$stmt->bind_param(
    "ii",
    $seasonId,
    $draftState['draft_position'] // Where does this come from?
);

$stmt->execute();

$result = $stmt->get_result();

$currentTeam = $result->fetch_assoc();


// -------------------------
// GET NEXT TEAM
// -------------------------

$currentPosition = (int) $draftState['draft_position']; // ADDED


if ($draftState['draft_direction'] === 'forward') {
    $nextPosition = $currentPosition + 1;
} else {
    $nextPosition = $currentPosition - 1;
}

$sql = "SELECT
            active_users.id,
            users.default_team_name,
            active_users.draft_position
        FROM active_users
        JOIN users
        ON active_users.user_id = users.id
        WHERE season_id = ?
        AND draft_position = ?
    ";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Prepare failed when finding next drafter'
    ]);
    exit;
}

$stmt->bind_param(
    "ii",
    $seasonId,
    $nextPosition
);

$stmt->execute();

$result = $stmt->get_result();

$nextTeam = $result->fetch_assoc();








echo json_encode([
    'success' => true,
    'draft_state' => $draftState,
    'current_team' => $currentTeam,
    'future_team' => $nextTeam,
    'my_active_user' => $myActiveUser
]);
