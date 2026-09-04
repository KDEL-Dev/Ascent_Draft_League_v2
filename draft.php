<?php
    // error_reporting(E_ALL);
    // ini_set('display_errors',1);

    require_once __DIR__ . '/includes/connection.php';

    $seasonId = 1;

    // ---------------
    // DRAFT STATE
    // ---------------

    $draftStateSql = "
        SELECT draft_position, current_round, total_picks, is_active
        FROM draft_state
        WHERE season_id = ?
    ";

    $stmt = $conn->prepare($draftStateSql);

    if (!$stmt) {
        die("Prepare Failed: " . $conn->error);
    }

    $stmt->bind_param("i", $seasonId);
    $stmt->execute();

    $draftStateResult = $stmt->get_result();

    if (!$draftStateResult) {
        die("Query Failed: " . $stmt->error);
    }

    $draftState = $draftStateResult->fetch_assoc();

    $draftStateSql = "
    SELECT draft_position, current_round, total_picks, is_active
    FROM draft_state
    WHERE season_id = ?
";

// dropping this here cause i feel insane and cant think
// next is to display whatever info im grabbing
$stmt = $conn->prepare($draftStateSql);

if (!$stmt) {
    die("Prepare Failed: " . $conn->error);
}

$stmt->bind_param("i", $seasonId);
$stmt->execute();

$draftStateResult = $stmt->get_result();

if (!$draftStateResult) {
    die("Query Failed: " . $stmt->error);
}

$draftState = $draftStateResult->fetch_assoc();


    // ---------------
    // RANDOMIZE TEAMS
    // ---------------

    // Retrieve in order, randomized draft order
    $activeUsersSql = "
        SELECT id, team_name, draft_position
        FROM active_users
        WHERE season_id = ?
        ORDER BY draft_position ASC
    ";
    
    $stmt = $conn->prepare($activeUsersSql);

    if(!$stmt)
    {
        die("Prepare Failed: " . $conn->error);   
    }

    $stmt->bind_param("i", $seasonId);
    $stmt->execute();

    $activeUsersResults = $stmt->get_result();

    if(!$activeUsersResults)
    {
        die("Query Failed: " . $stmt->error);
    }

    // -------------
    // DRAFT BUTTONS
    // -------------

    // Begin by created your query
    $ouSql = "
    SELECT showdown_pokemon.id, showdown_pokemon.name, showdown_pokemon.type1, showdown_pokemon.type2, pokemon_tier_per_season.season_id
    FROM showdown_pokemon
    JOIN pokemon_tier_per_season
    ON pokemon_tier_per_season.showdown_pokemon_id = showdown_pokemon.id
    WHERE pokemon_tier_per_season.season_id = 1
    AND pokemon_tier_per_season.tier IN ('OU', 'UUBL');
    ";

    // Grab the results and place them in a variable
    $ouResults = $conn->query($ouSql);

    // If no results, display error
    if(!$ouResults)
    {
        die("Query Failed: " . $conn->error);
    }

    // This is so easy, just memorize above and how to display data!

    //  Return to add placeholders

    // Adding rest of the tiers

    $uuSql = "
    SELECT showdown_pokemon.id, showdown_pokemon.name, showdown_pokemon.type1, showdown_pokemon.type2, pokemon_tier_per_season.season_id
    FROM showdown_pokemon
    JOIN pokemon_tier_per_season
    ON pokemon_tier_per_season.showdown_pokemon_id = showdown_pokemon.id
    WHERE pokemon_tier_per_season.season_id = 1
    AND pokemon_tier_per_season.tier IN ('UU', 'RUBL');
    ";

    $uuResults = $conn->query($uuSql);

    if(!$uuResults)
    {
        die("Query Failed: " . $conn->error);
    }

    // RU + NUBL POKEMON

     $ruSql = "
    SELECT showdown_pokemon.id, showdown_pokemon.name, showdown_pokemon.type1, showdown_pokemon.type2, pokemon_tier_per_season.season_id
    FROM showdown_pokemon
    JOIN pokemon_tier_per_season
    ON pokemon_tier_per_season.showdown_pokemon_id = showdown_pokemon.id
    WHERE pokemon_tier_per_season.season_id = 1
    AND pokemon_tier_per_season.tier IN ('RU', 'NUBL');
    ";

    $ruResults = $conn->query($ruSql);

    if(!$ruResults)
    {
        die("Query Failed: " . $conn->error);
    }

    $nuSql = "
    SELECT showdown_pokemon.id, showdown_pokemon.name, showdown_pokemon.type1, showdown_pokemon.type2, pokemon_tier_per_season.season_id
    FROM showdown_pokemon
    JOIN pokemon_tier_per_season
    ON pokemon_tier_per_season.showdown_pokemon_id = showdown_pokemon.id
    WHERE pokemon_tier_per_season.season_id = 1
    AND pokemon_tier_per_season.tier IN ('NU', 'PUBL', 'PU', 'ZUBL', 'ZU');
    ";

    $nuResults = $conn->query($nuSql);

    if(!$nuResults)
    {
        die("Query Failed: " . $conn->error);
    }
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
    <main class="p-4" id="draft">
        <div class="row border">
            <h2>Draft Overview</h2>
            <div>
                <div>
                    <h3>Draft Order</h3>
                    <div class="border  d-flex justify-content-between">
                        <ul class="w-100 mb-0 list-unstyled d-flex justify-content-evenly align-items-center" id="draftOrder">
                             <?php while ($activeUser = $activeUsersResults->fetch_assoc()): ?>
                                <li>
                                    <span class="draftPosition">
                                        <?= htmlspecialchars($activeUser['draft_position']) ?>.
                                    </span>
                                    <span>
                                        <?= htmlspecialchars($activeUser['team_name']) ?>
                                    </span>
                                    
                                </li>
                            <?php endwhile; ?>
                        </ul>
                        <button id="randomizeDraft">Randomize Draft</button>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="d-flex justify-content-end">
                        <button id="startDraft">Start Draft</button>
                        <button>Pause Draft</button>
                        <button>Skip Pick</button>
                        <button>End Draft</button>
                    </div>
                </div>
            </div>
            <div>
                <h2>Draftboard</h2>
                <div class="d-flex justify-content-evenly">
                    <div>
                        <h3>Drafted</h3>
                    </div>
                    <div>
                        <h3>Current Pick</h3>
                    </div>
                    <div>
                        <h3>Your Dashboard</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <h2>
                OU Pokemon
                <span class="badge text-bg-secondary">UUBL</span>
            </h2>
            <?php while($ouPokemon = $ouResults->fetch_assoc()): ?>
                <div class="col-12 col-md-6 col-lg-4 col-xl-3 my-2">
                    <div class="border rounded p-2 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="me-1">
                                <?=  htmlspecialchars($ouPokemon['name']) ?>
                            </span>
                            <!-- Remove Tier and see if in the future you can display owners name -->
                            <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($ouPokemon['type1'])) ?>">
                                <?= htmlspecialchars($ouPokemon['type1']) ?>
                            </span>
                            <?php if (!empty($ouPokemon['type2'])): ?>
                                <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($ouPokemon['type2'])) ?>">
                                    <?= htmlspecialchars($ouPokemon['type2']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <button 
                            class="draftBtn btn btn-primary" 
                            data-pokemon-id="<?= $ouPokemon['id'] ?>"
                            data-pokemon-name="<?= htmlspecialchars($ouPokemon['name']) ?>">
                            Draft 
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="row">
            <h2>
                UU Pokemon
                <span class="badge text-bg-secondary">RUBL</span>
            </h2>
            <?php while($uuPokemon = $uuResults->fetch_assoc()): ?>
                <div class="col-12 col-md-6 col-lg-4 col-xl-3 my-2">
                    <div class="border rounded p-2 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="me-1">
                                <?=  htmlspecialchars($uuPokemon['name']) ?>
                            </span>
                            <!-- Remove Tier and see if in the future you can display owners name -->
                            <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($uuPokemon['type1'])) ?>">
                                <?= htmlspecialchars($uuPokemon['type1']) ?>
                            </span>
                            <?php if (!empty($uuPokemon['type2'])): ?>
                                <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($uuPokemon['type2'])) ?>">
                                    <?= htmlspecialchars($uuPokemon['type2']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="draftBtn badge bg-primary">Draft</span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="row">
            <h2>
                RU Pokemon
                <span class="badge text-bg-secondary">NUBL</span>
            </h2>
            <?php while($ruPokemon = $ruResults->fetch_assoc()): ?>
                <div class="col-12 col-md-6 col-lg-4 col-xl-3 my-2">
                    <div class="border rounded p-2 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="me-1">
                                <?=  htmlspecialchars($ruPokemon['name']) ?>
                            </span>
                            <!-- Remove Tier and see if in the future you can display owners name -->
                            <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($ruPokemon['type1'])) ?>">
                                <?= htmlspecialchars($ruPokemon['type1']) ?>
                            </span>
                            <?php if (!empty($ruPokemon['type2'])): ?>
                                <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($ruPokemon['type2'])) ?>">
                                    <?= htmlspecialchars($ruPokemon['type2']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="draftBtn badge bg-primary">Draft</span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="row">
            <h2>
                NU Pokemon
                <span class="badge text-bg-secondary">PUBL</span>
                <span class="badge text-bg-secondary">PU</span>
                <span class="badge text-bg-secondary">ZUBL</span>
                <span class="badge text-bg-secondary">ZU</span>
            </h2>
            <?php while($nuPokemon = $nuResults->fetch_assoc()): ?>
                <div class="col-12 col-md-6 col-lg-4 col-xl-3 my-2">
                    <div class="border rounded p-2 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="me-1">
                                <?=  htmlspecialchars($nuPokemon['name']) ?>
                            </span>
                            <!-- Remove Tier and see if in the future you can display owners name -->
                            <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($nuPokemon['type1'])) ?>">
                                <?= htmlspecialchars($nuPokemon['type1']) ?>
                            </span>
                            <?php if (!empty($nuPokemon['type2'])): ?>
                                <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($nuPokemon['type2'])) ?>">
                                    <?= htmlspecialchars($nuPokemon['type2']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="draftBtn badge bg-danger">Draft</span>
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