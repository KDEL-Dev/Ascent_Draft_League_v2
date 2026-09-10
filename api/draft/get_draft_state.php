<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/connection.php';

$seasonId = 1;

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




echo json_encode([
    'success' => true,
    'draft_state' => $draftState,
    'current_team' => $currentTeam
]);
