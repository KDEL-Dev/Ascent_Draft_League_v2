<?php

require_once __DIR__ . '/../../includes/auth.php';

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


// Get all active users for this season
$sql = "SELECT id
        FROM active_users
        WHERE season_id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare Failed: " . $conn->error);
}

$stmt->bind_param("i", $seasonId);
$stmt->execute();

$result = $stmt->get_result();

if (!$result) {
    die("Query Failed: " . $stmt->error);
}

// Store active user IDs
$activeUserIds = [];

while ($row = $result->fetch_assoc()) {
    $activeUserIds[] = $row['id'];
}

// Make sure we actually have teams
if (count($activeUserIds) === 0) {
    die("No active users found.");
}

// Randomize order
shuffle($activeUserIds);

// Prepare update
$updateSql = "
    UPDATE active_users
    SET draft_position = ?
    WHERE id = ?
    AND season_id = ?
";

$updateStmt = $conn->prepare($updateSql);

if (!$updateStmt) {
    die("Prepare Failed: " . $conn->error);
}

// Assign positions
foreach ($activeUserIds as $index => $activeUserId) {

    $draftPosition = $index + 1;

    $updateStmt->bind_param(
        "iii",
        $draftPosition,
        $activeUserId,
        $seasonId
    );

    if (!$updateStmt->execute()) {
        die("Update Failed: " . $updateStmt->error);
    }
}

echo "Draft order randomized successfully.";
