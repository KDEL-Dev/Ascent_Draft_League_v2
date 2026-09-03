<?php

require_once __DIR__ . '/../../includes/connection.php';

$seasonId = 1;

// Get all active users for this season
$sql = "
    SELECT id
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
