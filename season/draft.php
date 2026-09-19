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
    
    <title>Draft</title>
</head>
<body>
    <!-- header and navbar -->
    <?php include '../includes/header.php' ?>

    <main class="p-3" id="draft">
        <div class="row border mb-3" id="draftDashboard">
            <div>
                <h3>Draft Order</h3>
                <div class="border  d-flex justify-content-between">
                    <ul class="w-100 mb-0 list-unstyled d-flex justify-content-evenly align-items-center" id="draftOrder">
                            <?php foreach ($activeUsers as $activeUser): ?>
                            <li>
                                <span class="draftPosition">
                                    <?= htmlspecialchars($activeUser['draft_position']) ?>.
                                </span>
                                <span>
                                    <?= htmlspecialchars($activeUser['team_name']) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <button id="randomizeDraft">Randomize Draft</button>
                </div>
            </div>
            <div class="contaier p-3">
                <div class="row p-3">
                    <div class="border col-1 text-center">
                        <p class="border-bottom">timer</p>
                        <p id="draftTimer" class="fs-5">60</p>
                    </div>
                    <div class="col-2 border text-center">
                        <p class="border-bottom">On the clock</p>
                        <p id="onTheClock">-</p>
                    </div>
                    <div class="col-1 border text-center">
                        <p>Next Team:</p>
                        <p id="nextTeam">-</p>
                    </div>
                    <div class="col-8 p-0 border d-flex">
                        <div class="border d-flex align-items-center">
                            <p class="m-0 text-center">Draft Log</p>
                        </div>

                        <ul id="draftLog" class="m-0 p-0 overflow-x-auto d-flex flex-grow-1">
                            <!-- Dynamically Added -->
                        </ul>
                    </div>
                </div>
                <div class="row p-3 d-flex align-items-stretch">
                    <div class="col-sm-12 col-md-3 col-lg-2 p-0 order-lg-1 d-flex flex-column">
                        <h3 class="text-center">Live Draft Order</h3>
                        <div class="border d-flex align-items-stretch flex-grow-1">
                            <div class="d-flex align-items-center">
                                <span id="draftDirection" class="fs-3">↓</span>
                            </div>
                            <div class="flex-grow-1">
                                <ul id="liveDraftOrder" class="h-100 border m-0 p-0 d-flex flex-column justify-content-evenly align-items-center">
                                    <?php foreach ($activeUsers as $activeUser): ?>
                                        <li>
                                            <span class="draftPosition">
                                                <?= htmlspecialchars($activeUser['draft_position']) ?>.
                                            </span>
                                            <span>
                                                <?= htmlspecialchars($activeUser['team_name']) ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-lg-6 p-0 order-sm-2 order-lg-2 d-flex flex-column">
                        <h3 class="text-center">Draft Board</h3>
                        <div class="border d-flex justify-content-evenly flex-grow-1">
                            <div id="draftPickInfo" class="d-flex flex-column flex-grow-1 justify-content-center align-items-center">
                                <p id="draftPickOwner">pick owner</p>
                                
                                <p id="draftPokemonStats">pokemon stats</p>
                                <div>
                                    <div id="draftPkmnStats1" class="d-flex justify-content-between">
                                        <div>
                                            <p>hp</p>
                                            <p id="draftedPkmnHp">-</p>
                                        </div>
                                        <div>
                                            <p>atk</p>
                                            <p id="draftedPkmnAtk">-</p>
                                        </div>
                                        <div>
                                            <p>def</p>
                                            <p id="draftedPkmnDef">-</p>
                                        </div>
                                    </div>
                                    <div id="draftPkmnStats2" class="d-flex justify-content-between">
                                        <div>
                                            <p>sp.atk</p>
                                            <p id=draftedPkmnSpa>-</p>
                                        </div>
                                        <div>
                                            <p>sp.def</p>
                                            <p id="draftedPkmnSpd">-</p>
                                        </div>
                                        <div>
                                            <p>spe</p>
                                            <p id="draftedPkmnSpe">-</p>
                                        </div>
                                    </div>
                                </div>
                                <p id="draftPokemonName">pokemon name</p>
                                <ul id="draftPokemonAbility">
                                    <li id="draftAbility1">-</li>
                                    <li id="draftAbility2">-</li>
                                    <li id="draftHiddenAbility">-</li>
                                </ul>                          
                            </div>
                            <div class="d-flex flex-column flex-grow-1">
                                <div id="draftPokemonImage"></div>
                                <p id="draftPokemonTier">tier</p>
                            </div>
                            <div id="draftDisplayTeamRoster" class="d-flex flex-grow-1">
                                 <div class=" d-flex flex-column flex-grow-1">
                                    <p>ou</p>
                                    <ul id="ouDraftDisplayRoster" class="p-0">
                                        <li>-</li>
                                        <li>-</li>
                                        <li>-</li>
                                    </ul>
                                    <p>uu</p>
                                    <ul id="uuDraftDisplayRoster" class="p-0">
                                        <li>-</li>
                                        <li>-</li>
                                        <li>-</li>
                                    </ul>
                                 </div>
                                 <div class="d-flex flex-column flex-grow-1">
                                    <p>ru</p>
                                    <ul id="ruDraftDisplayRoster" class="p-0">
                                        <li>-</li>
                                        <li>-</li>
                                        <li>-</li>
                                    </ul>
                                    <p>nu</p>
                                    <ul id="nuDraftDisplayRoster" class="p-0">
                                        <li>-</li>
                                        <li>-</li>
                                        <li>-</li>
                                    </ul>
                                 </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-9 col-lg-4 p-0 order-sm-1 order-lg-3 d-flex flex-column">
                        <h3 class="text-center">Your Team - <?= htmlspecialchars($teamName) ?></h3> <!-- Get team name -->
                        <div class="border d-flex justify-content-evenly flex-grow-1">
                            <div class="d-flex flex-column justify-content-center align-items-center">
                                <p>Roster Counter</p>
                                <h4 id="draftRosterCount">0/12</h4>
                            </div>
                            <div class="d-flex">
                                <div class="d-flex flex-column">
                                    <p class="text-center">OU</p>
                                    <ul id="ouDraftRoster">
                                        <li>—</li>
                                        <li>—</li>
                                        <li>—</li>
                                    </ul>
                                    <p class="text-center">UU</p>
                                    <ul id="uuDraftRoster">
                                        <li>—</li>
                                        <li>—</li>
                                        <li>—</li>                           
                                    </ul>
                                </div>
                                <div class="d-flex flex-column">
                                    <p class="text-center">RU</p>
                                    <ul id="ruDraftRoster">
                                        <li>—</li>
                                        <li>—</li>
                                        <li>—</li>
                                    </ul>
                                    <p class="text-center">NU</p>
                                    <ul id="nuDraftRoster">
                                        <li>—</li>
                                        <li>—</li>
                                        <li>—</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-1">
                    <div class="d-flex justify-content-end">
                        <button id="startDraft">Start Draft</button>
                        <button id="resumeDraft">Resume Draft</button>
                        <button id="pauseDraft">Pause Draft</button>
                        <button id="skipPick">Skip Pick</button>
                        <button id="endDraft">End Draft</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="btn-group mb-3" role="group">
            <button 
                type="button" 
                class="btn btn-primary tierButton"
                data-tier="ou">
                OU
            </button>

            <button 
                type="button" 
                class="btn btn-outline-primary tierButton"
                data-tier="uu">
                UU
            </button>

            <button 
                type="button" 
                class="btn btn-outline-primary tierButton"
                data-tier="ru">
                RU
            </button>

            <button 
                type="button" 
                class="btn btn-outline-primary tierButton"
                data-tier="nu">
                NU
            </button>
        </div>

        <div class="row tier-section" id="ouDraftList">
            <h2>
                OU Pokemon
                <span class="badge text-bg-secondary">UUBL</span>
            </h2>
            <?php foreach ($groupedPokemon['OU'] as $pokemon): ?>
                <div class="col-12 col-md-6 col-lg-4 col-xl-3 my-2">
                    <div class="border rounded p-2 d-flex justify-content-between align-items-center">
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
                    <div class="border rounded p-2 d-flex justify-content-between align-items-center">
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
                    <div class="border rounded p-2 d-flex justify-content-between align-items-center">
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
                    <div class="border rounded p-2 d-flex justify-content-between align-items-center">
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
    </main>
    <!-- Bootstrap Script and My Script -->
    <script src="../javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>