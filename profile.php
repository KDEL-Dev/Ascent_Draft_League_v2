<?php 

    require_once __DIR__ . '/includes/auth.php';

    $userId = $_SESSION['user_id'];

    //------------------------------
    //----- RETRIEVE TEAM NAME -----
    //------------------------------

    $sql = "SELECT default_team_name 
            FROM users
            WHERE users.id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt-> bind_param("i",$userId);
    $stmt->execute();

    $teamNameResult = $stmt->get_result();

    $teamName = $teamNameResult->fetch_assoc()['default_team_name'];

    // Must I close the connection here?


    $seasonsSql = "SELECT
                        id,
                        season_number,
                        season_name,
                        draft_date,
                        start_date,
                        created_at,
                        is_active,
                        format
                    FROM seasons
                    ORDER BY season_number ASC
    ";

    $seasonsResult = $conn->query($seasonsSql);

    if (!$seasonsResult) {
        die("Failed to retrieve seasons: " . $conn->error);
    }

    // --------------
    // CURRENT SEASON
    // --------------

    $currentSql ="SELECT s.id, s.season_number, s.season_name, s.format, s.is_active
                        FROM seasons s
                        WHERE s.is_active = 1
                        ORDER BY s.id DESC
                        LIMIT 1;
    ";

    $currentStmt = $conn->prepare($currentSql);
    $currentStmt->execute();

    $currentResults = $currentStmt->get_result();
    $currentSeason = $currentResults->fetch_assoc();

    // echo '<pre>';
    // print_r($currentSeason);
    // echo '</pre>';
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/4a4034fc29.js" crossorigin="anonymous"></script>

    <title>Profile</title>
</head>
<body class="bg-body-secondary">
     <nav class="px-3 mb-3 sticky-top navbar navbar-expand-lg bg-dark" data-bs-theme="dark">
        <?php include 'includes/nav-main.php' ?>
    </nav>
    <main id="profile" class="bg-white p-3 container">

        <h1 class="fs-3">Profile</h1>
        <a href="<?= BASE_URL ?>index.php" class="btn btn-secondary">Return</a>
        <hr>

            <a href="change-team-info.php" class="btn btn-primary">Change Team Name and Mascot</a>

    </main>
<script src="../javascript/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>