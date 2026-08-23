<?php
    // error_reporting(E_ALL);
    // ini_set('display_errors',1);

    require_once __DIR__ . '/includes/connection.php';

    // Begin by created your query
    $sql = "
    SELECT showdown_pokemon.name, showdown_pokemon.type1, showdown_pokemon.type2, pokemon_tier_per_season.season_id
    FROM showdown_pokemon
    JOIN pokemon_tier_per_season
    ON pokemon_tier_per_season.showdown_pokemon_id = showdown_pokemon.id
    WHERE season_id = 1;
    ";

    // Grab the results and place them in a variable
    $result = $conn->query($sql);

    // If no results, display error
    if(!$result)
    {
        die("Query Failed: " . $conn->error);
    }

    // This is so easy, just memorize above and how to display data!

    //  Return to add placeholders
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <title>Draft</title>
</head>
<body>
    <header>
        <div class="seasonBanner d-flex justify-content-center align-items-center">
            <!-- Website Banner -->
             <h2>Future - Season Banner</h2>
        </div>
        <!-- navbar -->
         <nav class=" navbar navbar-expand-lg bg-dark-subtle">
            <!-- NavBar Toggle Icon -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"> 
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Nav bar -->
                <ul class="navbar-nav w-100 me-auto mb-2 mb-lg-0 d-flex justify-content-evenly">
                    <li class="nav-item">
                        <a class="nav-link" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="">Draft</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Matchups</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Roster</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Standings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Statistics</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Draft Recap</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">League Information</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Log Out</a>
                    </li>
                </ul>
             </div>
        </nav>
    </header>
    <main class="p-4">
        <div class="row">
            <?php while($pokemon = $result->fetch_assoc()): ?>
                <div class="col-12 col-md-6 col-lg-3 my-2">
                    <div class="border rounded p-2 d-flex justify-content-between align-items-center">
                        <div>
                            <span>
                                <?=  htmlspecialchars($pokemon['name']) ?>
                            </span>
                            <!-- Remove Tier and see if in the future you can display owners name -->
                            <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($pokemon['type1'])) ?>">
                                <?= htmlspecialchars($pokemon['type1']) ?>
                            </span>
                            <?php if (!empty($pokemon['type2'])): ?>
                                <span class="badge bg-warning">
                                    <?= htmlspecialchars($pokemon['type2']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-primary">Draft</span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>
    <!-- Bootstrap Script and My Script -->
    <script src="javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>