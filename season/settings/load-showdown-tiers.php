<?php

    require_once __DIR__ . '/../../includes/auth.php';

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

    <link rel="stylesheet" href="../../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/4a4034fc29.js" crossorigin="anonymous"></script>

    <title>Load Tiers - Ascent</title>
</head>
<body class="bg-body-secondary">
    <!-- For now just banner image -->
    <header>
        <?php include '../../includes/banner.php' ?>
    </header>

    <!-- Navbar Sticky -->
    <nav class="px-3 sticky-top navbar navbar-expand-lg" data-bs-theme="dark">
        <?php include '../../includes/nav.php' ?>
    </nav>

    <main class="p-3 bg-white container">

        <h1 class="fs-3">Load New Showdown Tiers</h1>

        <hr>
        
        <!-- Form to upload JSON -->
        <!-- <form enctype="multipart/form-data" method="POST">

            <input type="hidden" name="seasonId" value="<?= htmlspecialchars((string)$season['id'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-3">
                <label for="uploadNewTiers" class="form-label">
                    Showdown Pokémon JSON
                </label>

                <input
                    type="file"
                    class="form-control"
                    id="uploadNewTiers"
                    name="uploadNewTiers"
                    accept=".json,application/json"
                    required
                >
            </div>

            <button type="submit" name="preview" value="1" class="btn btn-primary">
                Preview JSON
            </button>

        </form> -->


        <!-- Success Message -->

        <?php if (($_GET['import'] ?? '') === 'success'): ?>
            <div class="alert alert-success">
                Pokémon tiers imported successfully!
            </div>
        <?php endif; ?>



        <!-- NEW - action will go to new location -->
        <form action="../../api/upload_to_db/process-showdown-tiers.php?seasonId=<?= (int)$season['id'] ?>"
            method="POST"
            enctype="multipart/form-data">

            <label for="uploadNewTiers" class="form-label">
                Pokémon Showdown JSON
            </label>

            <input
                type="file"
                class="form-control mb-3"
                id="uploadNewTiers"
                name="uploadNewTiers"
                accept=".json,application/json"
                required
            >

            <button type="submit" class="btn btn-primary">
                Import Tiers
            </button>

        </form>

    </main>

    <script src="../../javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>