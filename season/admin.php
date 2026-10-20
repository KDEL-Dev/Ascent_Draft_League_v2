<?php

// CHANGE NAME OF THIS FILE!!!


    require_once __DIR__ . '/../includes/auth.php';

    // 1. Get seasonId from url
    $seasonId = filter_input(INPUT_GET,'seasonId',FILTER_VALIDATE_INT,['options' => ['min_range' => 1]]);

    // 2. Validate ID
    if($seasonId === false || $seasonId === null)
    {
        http_response_code(400);
        exit("Invalid season ID.");
    }

    // 3. Check that season exists
    $seasonSql ="SELECT
                 id, season_number, season_name, format
                 FROM
                 seasons
                 WHERE
                 id = ?
                 LIMIT 
                 1
    ";

    $seasonStmt = $conn->prepare($seasonSql);
    $seasonStmt->bind_param("i", $seasonId);
    $seasonStmt->execute();

    $seasonResult = $seasonStmt->get_result();
    $season = $seasonResult->fetch_assoc();

    $seasonStmt->close();

    // 4. Stop if eseason doesnt exist
    if(!$season)
    {
        http_response_code(404);
        exit("Season not found.");
    }


    // --------------
    // Check if ADMIN
    // --------------

    

    if (!$isAdmin) 
    {
        http_response_code(403);
        exit('Access denied.');
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/4a4034fc29.js" crossorigin="anonymous"></script>


    <title>Season Setting - Ascent</title>
</head>
<body class="bg-body-secondary">

    <!-- For now just banner image -->
    <header>
        <?php include '../includes/banner.php' ?>
    </header>

    <!-- Navbar Sticky -->
    <nav class="px-3 sticky-top navbar navbar-expand-lg" data-bs-theme="dark">
        <?php include '../includes/nav.php' ?>
    </nav>

    <main class="p-3 container">
        <div class="p-3 bg-white">

            <h1 class="fs-3">Season Settings</h1>

            <div id="admin_settings_cont" class="row">
                
                <div class="col-12 col-md-6 col-lg-3 p-3">

                    <div class="p-3 border rounded-3">

                        <h2 class="text-center border-bottom fs-4">Draft Settings</h2>

                        <div class="d-flex flex-column gap-2 align-items-center">

                            <button class="btn btn-secondary">Load Pokemon Tier</button>

                            <button class="btn btn-secondary">Edit Tier Numbers</button>
                        
                            <form action="../api/admin_settings/reset_draft.php" method="POST" onsubmit="return confirm('Reset the draft?');">

                                <input type="hidden" name="seasonId" value="<?= htmlspecialchars((string)$seasonId) ?>">

                                <button type="submit" class="btn btn-danger">Reset Draft</button>

                            </form>

                        </div>

                    </div>

                </div>

                <div class="col-12 col-md-6 col-lg-3 p-3">

                    <div class="p-3 border rounded-3">

                        <h2 class="text-center border-bottom fs-4">General Settings</h2>

                        <div class="d-flex flex-column gap-2 align-items-center">

                            <a class="btn btn-secondary" href="settings/edit-competitors.php?seasonId=<?= (int)$seasonId ?>">Edit Competitors</a>

                        </div>

                    </div>

                </div>

                <div class="col-12 col-md-6 col-lg-3 p-3">

                    <div class="p-3 border rounded-3">

                        <h2 class="text-center border-bottom fs-4">Pokebox</h2>

                        <div class="d-flex flex-column gap-2 align-items-center">

                            <button class="btn btn-secondary">Reset Transaction Counter</button>

                            <form action="../api/admin_settings/clear_transaction_log.php" method="POST" onsubmit="return confirm('Clear the transaction log for this season? This cannot be undone.');">

                                <input type="hidden" name="seasonId"  value="<?= htmlspecialchars((string)$seasonId) ?>">
                                
                                <button type="submit" class="btn btn-danger">Clear Transaction Log</button>

                            </form>

                        </div>

                    </div>

                </div>

                <div class="col-12 col-md-6 col-lg-3 p-3">

                    <div class="p-3 border rounded-3">

                        <h2 class="text-center border-bottom fs-4">MatchUps</h2>

                        <div class="d-flex flex-column gap-2 align-items-center">
                            <!-- <button class="btn btn-danger">Clear Matchups</button> -->

                            <form action="../api/admin_settings/clear_matchups.php" method="POST" onsubmit="return confirm('Clear all matchups for this season? This cannot be undone');">
                                
                                <input type="hidden" name="seasonId" value="<?= htmlspecialchars((string)$seasonId) ?>"> 
                                
                                <button type="submit" class="btn btn-danger">Clear Matchups</button>

                            </form>
                        </div>

                    </div>
                    
                </div>

            </div>
        </div>
    </main>

    <script src="../javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>