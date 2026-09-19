<?php

    session_start();

    header('Content-Type: application/json');

    require_once __DIR__ . '/../../includes/connection.php';

    $userId = $_SESSION['user_id'] ?? null;
    $seasonId = 1;

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

    $sql = "
        SELECT id, team_name, draft_position
        FROM active_users
        WHERE user_id = ?
        AND season_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Prepare failed when finding active user'
        ]);
        exit;
    }

    $stmt->bind_param("ii", $userId, $seasonId);
    $stmt->execute();

    $result = $stmt->get_result();

    $myActiveUser = $result->fetch_assoc();

    if (!$myActiveUser) {
        echo json_encode([
            'success' => false,
            'message' => 'You are not an active user for this season'
        ]);
        exit;
    }



$sql = "
    SELECT
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

    WHERE season_id = ?
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

$sql = "
    SELECT
        id,
        team_name,
        draft_position
    FROM active_users
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

$sql = "
    SELECT
        id,
        team_name,
        draft_position
    FROM active_users
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
