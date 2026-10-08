<?php 

    require_once __DIR__ . '/includes/auth.php';

    $userId = $_SESSION['user_id'];

    //---------------------------------------------
    //----- Retrieve team name of active user -----
    //---------------------------------------------

    $sql = " SELECT default_team_name 
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
                        format,
                        team_size
                    FROM seasons
                    ORDER BY season_number DESC
    ";

    $seasonsResult = $conn->query($seasonsSql);

    if (!$seasonsResult) {
        die("Failed to retrieve seasons: " . $conn->error);
    }

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <title>Hub - Ascent Draft League</title>
</head>
<body class="bg-body-secondary">
   <nav class="px-3 sticky-top navbar navbar-expand-lg bg-dark" data-bs-theme="dark">
        <button 
            type="button" data-bs-toggle = "collapse" data-bs-target="#navbarSupportedContentHome" 
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation" 
            class="navbar-toggler"
        >
            <span class="navbar-toggler-icon"></span>
        </button>
        <div id="navbarSupportedContentHome" class="collapse navbar-collapse">
            <ul class="navbar-nav w-100 me-auto mb-2 mb-lg-0 d-flex justify-content-evenly" >
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item" href="#">
                    <a class="nav-link" href="#">About</a>
                </li>
                <li class="nav-item" href="#">
                    <a class="nav-link" href="#">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
   </nav>
   <main class="container my-3 p-3 bg-white">
        <h1>Ascent Draft League</h1>
        <!-- Current Season -->
        <div id="currentSeasonCont" class="row p-3">
            <div class="card col-12 px-0">
                <div class="row g-0">
                    <div class="col-lg-3 d-flex align-items-center">
                        <img src="img/Ascent Horizontal Text.svg" class="img-fluid rounded-start" alt="...">
                    </div>
                    <div class="col-md-8">
                        <div class="card-body">
                            <p class="card-title">Season: Placeholder</p>
                            <p class="card-text">Format:</p>
                            <p class="card-text">Season Start:</p>
                            <a class="btn btn-primary" href="season/draft.php">Enter</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Previous Seasons -->

        <div id="previousSeasonCont" class="border m-3 p-3">

            <h2 class="fs-4 mb-3">Previous Seasons</h2>

            <?php if ($seasonsResult->num_rows > 0): ?>

                <div class="row g-3">

                    <?php while ($season = $seasonsResult->fetch_assoc()): ?>

                        <div class="col-12 col-md-6 col-lg-4">

                            <a
                                href="season/draft.php?season_id=<?= (int) $season['id'] ?>"
                                class="text-decoration-none text-reset"
                            >
                                <div class="card h-100">

                                    <div class="card-body">

                                        <h3 class="card-title fs-5">
                                            <?= htmlspecialchars(
                                                $season['season_name']
                                                ?: 'Season ' . $season['season_number']
                                            ) ?>
                                        </h3>

                                        <p class="card-text">
                                            <strong>Season Number:</strong>
                                            <?= (int) $season['season_number'] ?>
                                        </p>

                                        <p class="card-text">
                                            <strong>Format:</strong>
                                            <?= htmlspecialchars($season['format'] ?? 'Not set') ?>
                                        </p>

                                        <p class="card-text">
                                            <strong>Team Size:</strong>
                                            <?= (int) $season['team_size'] ?>
                                        </p>

                                        <p class="card-text">
                                            <strong>Status:</strong>
                                            <?= (int) $season['is_active'] === 1
                                                ? 'Active'
                                                : 'Inactive' ?>
                                        </p>

                                        <?php if (!empty($season['start_date'])): ?>
                                            <p class="card-text">
                                                <strong>Start Date:</strong>
                                                <?= htmlspecialchars($season['start_date']) ?>
                                            </p>
                                        <?php endif; ?>

                                        <span class="btn btn-primary">
                                            Enter Season
                                        </span>

                                    </div>

                                </div>
                            </a>

                        </div>

                    <?php endwhile; ?>

                </div>

            <?php else: ?>

                <p>No seasons have been created yet.</p>

            <?php endif; ?>

        </div>
        <!-- Legacy Stats -->
         <div id="statisticsCont" class="border m-3 p-3">
            <p>statistics</p>
            <a class="btn btn-primary" href="#">Enter</a>
         </div>
   </main>
   <footer>

   </footer>
</body>
    <script src="javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>