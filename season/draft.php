<?php
    // error_reporting(E_ALL);
    // ini_set('display_errors',1);

    session_start();

    $userId = $_SESSION['user_id'];

    require_once __DIR__ . '/../includes/connection.php';

    $seasonId = 1;
    // $activeUserId = 6;


    // ------------------
    // GET ACTIVE USER ID
    // ------------------

    $sql = "SELECT active_users.id
        FROM active_users
        JOIN users
        ON active_users.user_id = users.id
        WHERE active_users.season_id = ?
        AND users.id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii",$seasonId,$userId);
    $stmt->execute();

    $activeUserResult = $stmt->get_result();
    $activeUserId = $activeUserResult->fetch_assoc()['id'];
        
    // -------------
    // GET TEAM NAME
    // -------------
    
    $sql = "SELECT users.default_team_name
    FROM users
    WHERE users.id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$userId);
    $stmt->execute();

    $teamNameResult = $stmt->get_result();
    $teamName = $teamNameResult->fetch_assoc()['default_team_name'];

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

    // ---------------
    // RANDOMIZE TEAMS
    // ---------------

    // Retrieve in order, randomized draft order
    $activeUsersSql = "SELECT active_users.id, users.default_team_name, active_users.draft_position
        FROM active_users
        JOIN users
        ON active_users.user_id = users.id
        WHERE active_users.season_id = ?
        ORDER BY active_users.draft_position ASC;
    ";
    
    $stmt = $conn->prepare($activeUsersSql);

    if(!$stmt)
    {
        die("Prepare Failed: " . $conn->error);   
    }

    $stmt->bind_param("i", $seasonId);
    $stmt->execute();

    $activeUsersResults = $stmt->get_result();

    $activeUsers = [];

    while ($row = $activeUsersResults->fetch_assoc()) {
        $activeUsers[] = $row;
    }


    if(!$activeUsersResults)
    {
        die("Query Failed: " . $stmt->error);
    }

    // -------------
    // DRAFT BUTTONS
    // -------------

    // Begin by created your query
    $pokemonSql = "
    SELECT
        showdown_pokemon.id,
        showdown_pokemon.name,
        showdown_pokemon.type1,
        showdown_pokemon.type2,
        pokemon_tier_per_season.tier,
        pokemon_tier_per_season.season_id
    FROM showdown_pokemon
    JOIN pokemon_tier_per_season
        ON pokemon_tier_per_season.showdown_pokemon_id = showdown_pokemon.id
    WHERE pokemon_tier_per_season.season_id = ?
    ";

    $stmt = $conn->prepare($pokemonSql);

    if (!$stmt) {
        die("Prepare Failed: " . $conn->error);
    }

    $stmt->bind_param("i", $seasonId);
    $stmt->execute();

    $pokemonResults = $stmt->get_result();

    $allPokemon = [];

    while ($pokemon = $pokemonResults->fetch_assoc()) {
        $allPokemon[] = $pokemon;
    }

    // SPLIT INTO DIFFERENT TIERS

    $tierGroups = 
    [
        'OU' => ['OU', 'UUBL'],
        'UU' => ['UU', 'RUBL'],
        'RU' => ['RU', 'NUBL'],
        'NU' => ['NU', 'PUBL', 'PU', 'ZUBL', 'ZU']
    ];


    $groupedPokemon = 
    [
        'OU' => [],
        'UU' => [],
        'RU' => [],
        'NU' => []
    ];

    foreach ($allPokemon as $pokemon) {

        foreach ($tierGroups as $group => $tiers) {

            if (in_array($pokemon['tier'], $tiers)) {
                $groupedPokemon[$group][] = $pokemon;
                break;
            }
        }
    }

    // Sort each tier alphabetically by Pokemon name
    foreach ($groupedPokemon as &$pokemonGroup) {
        usort($pokemonGroup, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
    }
    unset($pokemonGroup);

    // -------------
    // COUNT TIER
    // -------------

    $tierCountSql = "
        SELECT
            CASE
                WHEN pt.tier IN ('OU', 'UUBL') THEN 'OU'
                WHEN pt.tier IN ('UU', 'RUBL') THEN 'UU'
                WHEN pt.tier IN ('RU', 'NUBL') THEN 'RU'
                WHEN pt.tier IN ('NU', 'PUBL', 'PU', 'ZUBL', 'ZU') THEN 'NU'
            END AS tier_group,
            COUNT(*) AS tier_count
        FROM draft_picks dp
        JOIN pokemon_tier_per_season pt
            ON pt.showdown_pokemon_id = dp.showdown_pokemon_id
            AND pt.season_id = dp.season_id
        WHERE dp.season_id = ?
        AND dp.active_user_id = ?
        GROUP BY tier_group
    ";

    $tierCounts = 
    [
        'OU' => 0,
        'UU' => 0,
        'RU' => 0,
        'NU' => 0
    ];

    $stmt = $conn->prepare($tierCountSql);

    $stmt->bind_param(
        "ii",
        $seasonId,
        $activeUserId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if ($row['tier_group'] !== null) {
            $tierCounts[$row['tier_group']] = (int)$row['tier_count'];
        }
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

    <title>Draft</title>
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

    <!-- Main Draft Content -->
    <main id="draft">
        <div id="draftOrderCont" class="draftPanel m-3 p-3 bg-dark-subtle border border-light rounded-1 d-flex flex-column flex-lg-row">
            <p class="mb-lg-0 me-3 d-flex align-items-lg-center">Draft Order:</p>
            <div class="border flex-grow-1 bg-white d-flex justify-content-between flex-column flex-lg-row">
                <ul class="w-100 mb-0 list-unstyled d-flex justify-content-evenly align-items-center" id="draftOrder">
                        <?php foreach ($activeUsers as $activeUser): ?>
                        <li>
                            <span class="draftPosition">
                                <?= htmlspecialchars($activeUser['draft_position']) ?>
                            </span>
                            <span>
                                <?= htmlspecialchars($activeUser['default_team_name']) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button id="randomizeDraft" class="btn border rounded-0">Randomize</button>
            </div>
        </div>

        <div id="draftTickerPanel" class="container-fluid mb-3 px-4 sticky-top bg-white">
            <div class="row p-1">

                <!-- Timer -->
                <div id="draftTimerCont" class="col-2 col-md-2 col-lg-1 border p-0 bg-danger text-white d-flex flex-column">
                    <div class="border-bottom d-flex justify-content-center align-items-center">
                        <i class="fa-regular fa-clock mx-1"></i>
                        <p class="mb-0">Timer</p>
                    </div>
                    <div class="d-flex justify-content-center align-items-center flex-grow-1">
                        <p id="draftTimer" class="m-0">60</p>
                    </div>
                </div>

                <!-- Current picker -->
                <div id="draftCurrentPickCont" class="col-5 col-md-5 col-lg-2 p-0 border text-center d-flex flex-column">
                    <div class="border-bottom d-flex justify-content-center align-items-center">
                        <i class="fa-solid fa-circle-down mx-1"></i>
                        <p class="m-0">On the clock</p>
                    </div>
                    <div class="d-flex flex-grow-1 align-items-center justify-content-center">
                        <p id="onTheClock" class="mb-0">-</p>
                    </div>
                </div>

                <!-- Next team -->
                <div id="draftNextPickCont" class="col-5 col-md-5 col-lg-2 p-0 border text-center d-flex flex-column">
                    <div class="border-bottom d-flex justify-content-center align-items-center">
                        <i class="fa-solid fa-circle-right mx-1"></i>
                        <p class="m-0">Next Team:</p>
                    </div>
                    <div class="d-flex flex-grow-1 align-items-center justify-content-center">
                        <p id="nextTeam" class="mb-0">-</p>
                    </div>
                    
                </div>

                <!-- Draft log -->
                 <div id="draftLogTitle" class="col-2 col-lg-1 border d-flex align-items-center justify-content-center">
                    <i class="fa-regular fa-pen-to-square"></i>
                    <p class="m-0 text-center">Draft Log</p>
                </div>
                <div id="draftLogCont" class="col-10 col-lg-6 p-0 border-bottom border-top border-end d-flex">
                    <ul id="draftLog" class="m-0 p-0 overflow-x-auto d-flex flex-grow-1">
                        <!-- Dynamically Added -->
                    </ul>
                </div>

            </div>
        </div>

        <div class="container-fluid mb-4" id="draftDashboard">
            <div class="p-3">
                
                <div class="row p-3 d-flex align-items-stretch">
                    <div class="col-sm-12 col-md-3 col-xl-1 p-0 order-xl-1 d-flex flex-column">
                        <div class="border bg-white ">
                            <h3 class="text-center">Order</h3>
                        </div>
                        <div class="d-flex align-items-stretch flex-grow-1">
                            <div id="draftDirection" class="d-flex align-items-center justify-content-center">
                                <span id="draftDirection">↓</span> <!-- Change this with font awesome -->
                            </div>
                            <div class="flex-grow-1">
                                <ul id="liveDraftOrder" class="h-100 border-end m-0 p-0 flex-grow-1 d-flex flex-column justify-content-evenly align-items-center">
                                    <?php foreach ($activeUsers as $activeUser): ?>
                                        <li class="w-100 text-black bg-white border-bottom flex-grow-1 d-flex justify-content-between align-items-center">
                                            <span class="draftPosition">
                                                <?= htmlspecialchars($activeUser['draft_position']) ?>
                                            </span>
                                            <span class="flex-grow-1 d-flex align-items-center justify-content-center">
                                                <?= htmlspecialchars($activeUser['default_team_name']) ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-xl-8 p-0 order-sm-2 order-xl-2 d-flex flex-column">
                        <div class="bg-white border-bottom">
                            <h3 class="text-center">Draft Board</h3>
                        </div>
                        <div class=" bg-white d-flex justify-content-evenly flex-grow-1 flex-wrap">
                            
                            <div class="d-flex flex-column justify-content-center align-items-center">
                                <div id="draftPkmnOwnerTier">
                                    <p id="draftPickOwner"></p>
                                    <p id="draftPickOwnerMasc"></p>
                                </div>
                                <div id="draftPokemonImage"></div>
                                
                            </div>

                            <div id="draftPickInfo" class="m-3 p-3 bg-light border border-2 rounded-3 d-flex flex-column flex-grow-1 justify-content-center align-items-center">
                                <div id="draftPkmnNameTitleCard">
                                    <p id="draftPokemonName">pokemon name</p>
                                    <p id="draftPokemonTier" class="mb-0 badge rounded-pill text-bg-secondary">tier</p>
                                </div>
                                <div class="d-flex">
                                    <div class="mx-1 mb-1">
                                        <span id="draftPokemonType1" class="fs-6">-</p>
                                    </div>
                                    <div class="mx-1 mb-1">
                                        <span id="draftPokemonType2" class="fs-6">-</p>
                                    </div>
                                </div>
                                
                                <div id="draftPokemonStats" class="w-100">
                                    <div id="draftPkmnStats1" class=" mb-2 px-3 text-center d-flex justify-content-between">
                                        <div>
                                            <p>HP</p>
                                            <div class="rayquazaCircle">
                                                <p id="draftedPkmnHp">-</p>
                                            </div>
                                            
                                        </div>
                                        <div>
                                            <p>ATK</p>
                                            <div class="rayquazaCircle">
                                                <p id="draftedPkmnAtk">-</p>
                                            </div>
                                            
                                        </div>
                                        <div>
                                            <p>DEF</p>                                            
                                            <div class="rayquazaCircle">
                                                <p id="draftedPkmnDef">-</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="draftPkmnStats2" class="mb-2 px-3 text-center d-flex justify-content-between">
                                        <div>
                                            <p>SP.ATK</p>
                                            <div class="rayquazaCircle">
                                                <p id=draftedPkmnSpa>-</p>
                                            </div>                                            
                                        </div>
                                        <div>
                                            <p>SP.DEF</p>
                                            
                                            <div class="rayquazaCircle">
                                                <p id="draftedPkmnSpd">-</p>
                                            </div> 
                                        </div>
                                        <div>
                                            <p>SPE</p>
                                            
                                            <div class="rayquazaCircle">
                                                <p id="draftedPkmnSpe">-</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <p class="mb-0">Abilities</p>
                                <div id="draftPokemonAbility" class="w-100 p-0 m-0 d-flex justify-content-between">
                                    <div class="draftAbilities">
                                        <span id="draftAbility1"></span>
                                    </div>
                                    <div class="draftAbilities">
                                        <span id="draftAbility2"></span>
                                    </div>
                                </div>      
                                <p class="mb-0">Hidden Ability:</p>
                                <div class="draftAbilities w-100 d-flex align-items-center">
                                    <span id="draftHiddenAbility"></span>
                                </div>                    
                            </div>

                            <div id="draftDisplayTeamRoster" class="m-3 p-3 bg-light border border-2 rounded-3 d-flex flex-grow-1 align-items-center flex-column">
                                <p class="fs-5">Roster</p>
                                <div class="w-100 p-3 bg-white border rounded-3 d-flex justify-content-around flex-wrap">
                                    <div class="d-flex flex-column">
                                        <div class="draftTierTitle d-flex align-items-center justify-content-center">
                                            <p class="mb-0">ou</p>
                                        </div>
                                        <ul id="ouDraftDisplayRoster" class="p-0 flex-grow-1">
                                            <li>-</li>
                                            <li>-</li>
                                            <li>-</li>
                                        </ul>
                                        <div class="draftTierTitle d-flex align-items-center justify-content-center">
                                            <p class="mb-0">uu</p>
                                        </div>
                                        <ul id="uuDraftDisplayRoster" class="p-0">
                                            <li>-</li>
                                            <li>-</li>
                                            <li>-</li>
                                        </ul>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <div class="draftTierTitle d-flex align-items-center justify-content-center">
                                            <p class="mb-0">ru</p>
                                        </div>
                                        <ul id="ruDraftDisplayRoster" class="p-0">
                                            <li>-</li>
                                            <li>-</li>
                                            <li>-</li>
                                        </ul>
                                        <div class="draftTierTitle d-flex align-items-center justify-content-center">
                                            <p class="mb-0">nu</p>
                                        </div>
                                        <ul id="nuDraftDisplayRoster" class="p-0">
                                            <li>-</li>
                                            <li>-</li>
                                            <li>-</li>
                                        </ul>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                    <div id="draftYourTeamCont" class="col-sm-12 col-md-9 col-xl-3 p-0 order-sm-1 order-xl-3 bg-white d-flex flex-column">
                        
                        <div class="p-3 border d-flex flex-column justify-content-evenly flex-grow-1">
                            <h3 class="text-center">Your Team - <?= htmlspecialchars($teamName) ?></h3> <!-- Get team name -->
                            <div class="draftYourTeam d-flex flex-grow-1 justify-content-around align-items-center flex-wrap">
                                <div class="d-flex flex-column">
                                    <div class="draftTierTitle">
                                        <p class="text-center">OU</p>
                                    </div>
                                    <ul id="ouDraftRoster" class="p-0 d-flex flex-column align-items-center">
                                        <li>—</li>
                                        <li>—</li>
                                        <li>—</li>
                                    </ul>
                                    <div class="draftTierTitle">
                                        <p class="text-center">UU</p>
                                    </div>
                                    
                                    <ul id="uuDraftRoster" class="p-0 d-flex flex-column align-items-center">
                                        <li>—</li>
                                        <li>—</li>
                                        <li>—</li>                           
                                    </ul>
                                </div>
                                <div class="d-flex flex-column">
                                    <div class="draftTierTitle">
                                        <p class="text-center">RU</p>
                                    </div>
                                    <ul id="ruDraftRoster" class="p-0 d-flex flex-column align-items-center">
                                        <li>—</li>
                                        <li>—</li>
                                        <li>—</li>
                                    </ul>
                                    <div class="draftTierTitle">
                                        <p class="text-center">NU</p>
                                    </div>
                                    <ul id="nuDraftRoster" class="p-0 d-flex flex-column align-items-center">
                                        <li>—</li>
                                        <li>—</li>
                                        <li>—</li>
                                    </ul>
                                </div>
                                
                            </div>
                            <div id="draftYourTeamInfo" class="border-top d-flex flex-column justify-content-center align-items-center">
                                <p class="m-0">Roster Counter</p>
                                <h4 id="draftRosterCount" class="m-0">0/12</h4>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="draftControls" class="mt-1 d-flex justify-content-between">
                    <div>
                        <button id="startDraft" class=" me-3 btn btn-secondary ">Start Draft</button>
                    </div>
                    <div>
                        <button id="resumeDraft" class="mx-1 btn btn-secondary">Resume Draft</button>
                        <button id="pauseDraft" class="mx-1 btn btn-secondary">Pause Draft</button>
                        <button id="skipPick" class="mx-1 btn btn-secondary">Skip Pick</button>
                        <button id="endDraft" class="mx-1 btn btn-secondary">End Draft</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="draftTierPageBtn" class="btn-group mb-3 px-3" role="group">
            <button 
                type="button" 
                class="btn btn-primary btn-lg tierButton"
                data-tier="ou">
                OU
            </button>

            <button 
                type="button" 
                class="btn btn-outline-primary btn-lg tierButton"
                data-tier="uu">
                UU
            </button>

            <button 
                type="button" 
                class="btn btn-outline-primary btn-lg tierButton"
                data-tier="ru">
                RU
            </button>

            <button 
                type="button" 
                class="btn btn-outline-primary btn-lg tierButton"
                data-tier="nu">
                NU
            </button>
        </div>

        <div class="m-3">
            <div class="row tier-section" id="ouDraftList">
                <h2>
                    OU Pokemon
                    <span class="badge text-bg-secondary">UUBL</span>
                </h2>
                <?php foreach ($groupedPokemon['OU'] as $pokemon): ?>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 my-2">
                        <div class="border rounded p-2 bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <span class="me-1">
                                    <?=  htmlspecialchars($pokemon['name']) ?>
                                </span>
                                <!-- Remove Tier and see if in the future you can display owners name -->
                                <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($pokemon['type1'])) ?>">
                                    <?= htmlspecialchars($pokemon['type1']) ?>
                                </span>
                                <?php if (!empty($pokemon['type2'])): ?>
                                    <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($pokemon['type2'])) ?>">
                                        <?= htmlspecialchars($pokemon['type2']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <button 
                                class="draftBtn btn" 
                                data-pokemon-id="<?= $pokemon['id'] ?>"
                                data-pokemon-name="<?= htmlspecialchars($pokemon['name']) ?>"
                                data-tier="OU">
                                Draft 
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="row tier-section" id="uuDraftList">
                <h2>
                    UU Pokemon
                    <span class="badge text-bg-secondary">RUBL</span>
                </h2>
                <?php foreach ($groupedPokemon['UU'] as $pokemon): ?>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 my-2">
                        <div class="border rounded p-2 bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <span class="me-1">
                                    <?=  htmlspecialchars($pokemon['name']) ?>
                                </span>
                                <!-- Remove Tier and see if in the future you can display owners name -->
                                <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($pokemon['type1'])) ?>">
                                    <?= htmlspecialchars($pokemon['type1']) ?>
                                </span>
                                <?php if (!empty($pokemon['type2'])): ?>
                                    <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($pokemon['type2'])) ?>">
                                        <?= htmlspecialchars($pokemon['type2']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <button 
                                class="draftBtn btn btn-primary" 
                                data-pokemon-id="<?= $pokemon['id'] ?>"
                                data-pokemon-name="<?= htmlspecialchars($pokemon['name']) ?>"
                                data-tier="UU">
                                Draft 
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="row tier-section" id="ruDraftList">
                <h2>
                    RU Pokemon
                    <span class="badge text-bg-secondary">NUBL</span>
                </h2>
                <?php foreach ($groupedPokemon['RU'] as $pokemon): ?>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 my-2">
                        <div class="border rounded p-2 bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <span class="me-1">
                                    <?=  htmlspecialchars($pokemon['name']) ?>
                                </span>
                                <!-- Remove Tier and see if in the future you can display owners name -->
                                <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($pokemon['type1'])) ?>">
                                    <?= htmlspecialchars($pokemon['type1']) ?>
                                </span>
                                <?php if (!empty($pokemon['type2'])): ?>
                                    <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($pokemon['type2'])) ?>">
                                        <?= htmlspecialchars($pokemon['type2']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <button 
                                class="draftBtn btn btn-primary" 
                                data-pokemon-id="<?= $pokemon['id'] ?>"
                                data-pokemon-name="<?= htmlspecialchars($pokemon['name']) ?>"
                                data-tier="RU">
                                Draft 
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="row tier-section" id="nuDraftList">
                <h2>
                    NU Pokemon
                    <span class="badge text-bg-secondary">PUBL</span>
                    <span class="badge text-bg-secondary">PU</span>
                    <span class="badge text-bg-secondary">ZUBL</span>
                    <span class="badge text-bg-secondary">ZU</span>
                </h2>
                <?php foreach ($groupedPokemon['NU'] as $pokemon): ?>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 my-2">
                        <div class="border rounded p-2 bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <span class="me-1">
                                    <?=  htmlspecialchars($pokemon['name']) ?>
                                </span>
                                <!-- Remove Tier and see if in the future you can display owners name -->
                                <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($pokemon['type1'])) ?>">
                                    <?= htmlspecialchars($pokemon['type1']) ?>
                                </span>
                                <?php if (!empty($pokemon['type2'])): ?>
                                    <span class="badge typeBadge-<?=  strtolower(htmlspecialchars($pokemon['type2'])) ?>">
                                        <?= htmlspecialchars($pokemon['type2']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <button 
                                class="draftBtn btn btn-primary" 
                                data-pokemon-id="<?= $pokemon['id'] ?>"
                                data-pokemon-name="<?= htmlspecialchars($pokemon['name']) ?>"
                                data-tier="NU">
                                Draft 
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    <!-- Bootstrap Script and My Script -->
    <script src="../javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>