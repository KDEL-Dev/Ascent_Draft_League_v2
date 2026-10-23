<?php

        require_once __DIR__ . '/../../includes/auth.php';

        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Invalid request method.');
        }

        // Check admin access
        if (!$isAdmin) {
            http_response_code(403);
            exit('Access denied.');
        }

        // Validate season ID
        $seasonId = filter_input(
            INPUT_POST,
            'seasonId',
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($seasonId === false || $seasonId === null) {
            http_response_code(400);
            exit('Invalid season ID.');
        }

        // Verify that the season exists
        $seasonStmt = $conn->prepare(
            "SELECT id FROM seasons WHERE id = ? LIMIT 1"
        );

        $seasonStmt->bind_param("i", $seasonId);
        $seasonStmt->execute();

        $seasonResult = $seasonStmt->get_result();
        $season = $seasonResult->fetch_assoc();

        $seasonStmt->close();

        if (!$season) {
            http_response_code(404);
            exit('Season not found.');
        }





        // Delete tiers for this season only
        $deleteStmt = $conn->prepare(
            "DELETE FROM pokemon_tier_per_season
            WHERE season_id = ?"
        );

        $deleteStmt->bind_param("i", $seasonId);
        $deleteStmt->execute();

        $deleted = $deleteStmt->affected_rows;

        $deleteStmt->close();
        $conn->close();

        // Display result
    ?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <title>Clear Season Tiers - Ascent</title>
</head>
<body class="bg-body-secondary">

<main class="container bg-white p-4 mt-4 rounded">
    <h1 class="fs-3">Season Tiers Cleared</h1>

    <div class="alert alert-success">
        Successfully deleted <?= (int)$deleted ?> tier records
        for season ID <?= (int)$seasonId ?>.
    </div>

    <button class="btn btn-secondary" onclick="history.back()">
        Go Back
    </button>
</main>

</body>
</html>