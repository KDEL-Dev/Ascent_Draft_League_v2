<?php 

    session_start();

    $userId = $_SESSION['user_id'];

    $seasonId = 1;

    require_once __DIR__ . '/../includes/auth.php';

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




    // ---------------------
    // GET USER ROSTER COUNT
    // ---------------------

    $rosterCountSql = "
    SELECT COUNT(*) AS roster_count
    FROM roster_pkmn
    WHERE active_user_id = ?
    AND season_id = ?
    AND status = 'active'
";

$stmt = $conn->prepare($rosterCountSql);
$stmt->bind_param("ii", $activeUserId, $seasonId);
$stmt->execute();

$rosterCountResult = $stmt->get_result();
$rosterCount = (int)$rosterCountResult->fetch_assoc()['roster_count'];

// GET USER TEAM

$rosterSql ="SELECT
                showdown_pokemon.name,
                pokemon_tier_per_season.tier
            FROM roster_pkmn

            JOIN showdown_pokemon
                ON showdown_pokemon.id = roster_pkmn.showdown_pokemon_id

            JOIN pokemon_tier_per_season
                ON showdown_pokemon.id = pokemon_tier_per_season.showdown_pokemon_id

            WHERE roster_pkmn.active_user_id = ?
            AND roster_pkmn.status = 'active'
        ";

$rosterStmt = $conn->prepare($rosterSql);
$rosterStmt->bind_param("i",$activeUserId);
$rosterStmt->execute();

$rosterResults = $rosterStmt->get_result();

$userRoster = 
[
    'OU' => [],
    'UU' => [],
    'RU' => [],
    'NU' => []
];

while ($pokemon = $rosterResults->fetch_assoc()) {

    $group = getTierGroup($pokemon['tier']);

    if ($group !== null) {
        $userRoster[$group][] = $pokemon;
    }
}


// GET ALL Pokemon in current meta
    $pokemonSql="SELECT
                    showdown_pokemon.id,
                    showdown_pokemon.name,
                    showdown_pokemon.type1,
                    showdown_pokemon.type2,
                    pokemon_tier_per_season.tier
                FROM showdown_pokemon
                JOIN pokemon_tier_per_season
                    ON pokemon_tier_per_season.showdown_pokemon_id = showdown_pokemon.id
                WHERE pokemon_tier_per_season.season_id = ?
                ORDER BY showdown_pokemon.name
";

$stmt = $conn->prepare($pokemonSql);
$stmt->bind_param("i", $seasonId);
$stmt->execute();

$pokemonResults = $stmt->get_result();

$allPokemon = [];

while ($pokemon = $pokemonResults->fetch_assoc()) {
    $allPokemon[] = $pokemon;
}

//  Get ALL Rostered Pokemon

$ownedSql = "SELECT DISTINCT showdown_pokemon_id
            FROM roster_pkmn
            WHERE season_id = ?
            AND status = 'active'
";

$stmt = $conn->prepare($ownedSql);
$stmt->bind_param("i", $seasonId);
$stmt->execute();

$ownedResults = $stmt->get_result();

$ownedPokemon = [];

while ($row = $ownedResults->fetch_assoc()) {
    $ownedPokemon[] = (int)$row['showdown_pokemon_id'];
}

// GROUP INTO APPRORIATE TIERS

function getTierGroup($tier)
{
    $groups = [
        'OU' => 'OU',
        'UUBL' => 'OU',

        'UU' => 'UU',
        'RUBL' => 'UU',

        'RU' => 'RU',
        'NUBL' => 'RU',

        'NU' => 'NU',
        'PUBL' => 'NU',
        'PU' => 'NU',
        'ZUBL' => 'NU',
        'ZU' => 'NU'
    ];

    return $groups[$tier] ?? null;
}

$groupedPokemon = [
    'OU' => [],
    'UU' => [],
    'RU' => [],
    'NU' => []
];

foreach ($allPokemon as $pokemon) {

    $group = getTierGroup($pokemon['tier']);

    if ($group !== null) {
        $groupedPokemon[$group][] = $pokemon;
    }
}


foreach ($groupedPokemon as &$pokemonGroup) {
    usort($pokemonGroup, function ($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
}
unset($pokemonGroup);



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/4a4034fc29.js" crossorigin="anonymous"></script>


    <title>Roster - Ascent</title>
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
    
    <main class="container p-3">
        
        <div class="p-3 row bg-white">
            <h1 class="fs-3">Pokebox</h1>

            <hr>

            <div class="alert alert-secondary">
                Your Roster Count: <strong><?= $rosterCount ?>/12</strong>
            </div>

            <div
                id="pokeboxData"
                data-roster-count="<?= $rosterCount ?>"
                data-roster-max="12">
            </div>     

            <p class="mb-0 fs-5">Your Roster:</p>
            <div class="col-12 col-md-6 col-lg-3 mt-3">
                <div class="pokeboxRoster">
                    <h2 class="fs-5 badge tierBadge-OU">OU</h2>
                    <ul id="ouUserRoster" class="list-group list-group-flush">
                        <?php foreach ($userRoster['OU'] as $pokemon): ?>
                            <li class="list-group-item px-0">
                                <?= htmlspecialchars($pokemon['name']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3 mt-3">
                <div class="pokeboxRoster">
                    <h2 class="fs-5 badge tierBadge-UU">UU</h2>
                    <ul id="uuUserRoster" class="list-group list-group-flush">
                        <?php foreach ($userRoster['UU'] as $pokemon): ?>
                            <li class="list-group-item px-0">
                                <?= htmlspecialchars($pokemon['name']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3 mt-3">
                <div class="pokeboxRoster">
                    <h2 class="fs-5 badge tierBadge-RU">RU</h2>
                    <ul id="ruUserRoster" class="list-group list-group-flush">
                        <?php foreach ($userRoster['RU'] as $pokemon): ?>
                            <li class="list-group-item px-0">
                                <?= htmlspecialchars($pokemon['name']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3 mt-3">
                <div class="pokeboxRoster">
                    <h2 class="fs-5 badge tierBadge-NU">NU</h2>
                    <ul id="nuUserRoster" class="list-group list-group-flush">
                        <?php foreach ($userRoster['NU'] as $pokemon): ?>
                            <li class="list-group-item px-0">
                                <?= htmlspecialchars($pokemon['name']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Pokemon Tier Buttons -->
            <div class="btn-group my-3 d-flex justify-content-center" role="group">

                <button 
                    type="button" 
                    class="btn btn-primary btn-sm pokeboxTierButton"
                    data-tier="ou">
                    OU
                </button>

                <button 
                    type="button" 
                    class="btn btn-outline-primary btn-sm pokeboxTierButton"
                    data-tier="uu">
                    UU
                </button>

                <button 
                    type="button" 
                    class="btn btn-outline-primary btn-sm pokeboxTierButton"
                    data-tier="ru">
                    RU
                </button>

                <button 
                    type="button" 
                    class="btn btn-outline-primary btn-sm pokeboxTierButton"
                    data-tier="nu">
                    NU
                </button>

            </div>

        <div class="m-3">

            <!-- OU -->
            <div id="ouPokeboxList" class="row pokebox-tier-section">

                <h2 class="fs-5 badge tierBadge-OU">
                    OU Pokemon
                    <span class="badge text-bg-secondary">+UUBL</span>
                </h2>

                <?php foreach ($groupedPokemon['OU'] as $pokemon): ?>

                    <?php
                        $isOwned = in_array((int)$pokemon['id'], $ownedPokemon, true);
                        $tierGroup = getTierGroup($pokemon['tier']);

                        if ($isOwned) {
                            $buttonText = 'Owned';
                            $buttonClass = 'btn-secondary';
                        } elseif ($rosterCount >= 12) {
                            $buttonText = 'Swap';
                            $buttonClass = 'btn-primary';
                        } else {
                            $buttonText = 'Add';
                            $buttonClass = 'btn-primary';
                        }
                    ?>

                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 my-2">

                        <div class="border rounded p-2 bg-white d-flex justify-content-between align-items-center">

                            <div class="d-flex flex-column">
                                <span class="me-1">
                                    <?= htmlspecialchars($pokemon['name']) ?>
                                </span>
                                <div>
                                    <span class="badge typeBadge-<?= strtolower(htmlspecialchars($pokemon['type1'])) ?>">
                                        <?= htmlspecialchars($pokemon['type1']) ?>
                                    </span>

                                    <?php if (!empty($pokemon['type2'])): ?>

                                        <span class="badge typeBadge-<?= strtolower(htmlspecialchars($pokemon['type2'])) ?>">
                                            <?= htmlspecialchars($pokemon['type2']) ?>
                                        </span>

                                    <?php endif; ?>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="pokeboxBtn btn <?= $buttonClass ?>"
                                data-pokemon-id="<?= $pokemon['id'] ?>"
                                data-pokemon-name="<?= htmlspecialchars($pokemon['name']) ?>"
                                data-tier="<?= htmlspecialchars($pokemon['tier']) ?>"
                                data-tier-group="<?= htmlspecialchars($tierGroup) ?>"
                                <?= $isOwned ? 'disabled' : '' ?>
                            >
                                <?= $buttonText ?>
                            </button>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


            <!-- UU -->
            <div id="uuPokeboxList" class="row pokebox-tier-section">

                <h2 class="fs-5 badge tierBadge-UU">
                    UU Pokemon
                    <span class="badge text-bg-secondary">+RUBL</span>
                </h2>

                <?php foreach ($groupedPokemon['UU'] as $pokemon): ?>

                    <?php
                        $isOwned = in_array((int)$pokemon['id'], $ownedPokemon, true);
                        $tierGroup = getTierGroup($pokemon['tier']);

                        if ($isOwned) {
                            $buttonText = 'Owned';
                            $buttonClass = 'btn-secondary';
                        } elseif ($rosterCount >= 12) {
                            $buttonText = 'Swap';
                            $buttonClass = 'btn-primary';
                        } else {
                            $buttonText = 'Add';
                            $buttonClass = 'btn-primary';
                        }
                    ?>

                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 my-2">

                        <div class="border rounded p-2 bg-white d-flex justify-content-between align-items-center">

                            <div class="d-flex flex-column">
                                <span class="me-1">
                                    <?= htmlspecialchars($pokemon['name']) ?>
                                </span>
                                <div>
                                    <span class="badge typeBadge-<?= strtolower(htmlspecialchars($pokemon['type1'])) ?>">
                                        <?= htmlspecialchars($pokemon['type1']) ?>
                                    </span>

                                    <?php if (!empty($pokemon['type2'])): ?>
                                        <span class="badge typeBadge-<?= strtolower(htmlspecialchars($pokemon['type2'])) ?>">
                                            <?= htmlspecialchars($pokemon['type2']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                            </div>

                            <button
                                type="button"
                                class="pokeboxBtn btn <?= $buttonClass ?>"
                                data-pokemon-id="<?= $pokemon['id'] ?>"
                                data-pokemon-name="<?= htmlspecialchars($pokemon['name']) ?>"
                                data-tier="<?= htmlspecialchars($pokemon['tier']) ?>"
                                data-tier-group="<?= htmlspecialchars($tierGroup) ?>"
                                <?= $isOwned ? 'disabled' : '' ?>
                            >
                                <?= $buttonText ?>
                            </button>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


            <!-- RU -->
            <div id="ruPokeboxList" class="row pokebox-tier-section">

                <h2 class="fs-5 badge tierBadge-RU">
                    RU Pokemon
                    <span class="badge text-bg-secondary">+NUBL</span>
                </h2>

                <?php foreach ($groupedPokemon['RU'] as $pokemon): ?>

                    <?php
                        $isOwned = in_array((int)$pokemon['id'], $ownedPokemon, true);
                        $tierGroup = getTierGroup($pokemon['tier']);

                        if ($isOwned) {
                            $buttonText = 'Owned';
                            $buttonClass = 'btn-secondary';
                        } elseif ($rosterCount >= 12) {
                            $buttonText = 'Swap';
                            $buttonClass = 'btn-primary';
                        } else {
                            $buttonText = 'Add';
                            $buttonClass = 'btn-primary';
                        }
                    ?>

                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 my-2">

                        <div class="border rounded p-2 bg-white d-flex justify-content-between align-items-center">

                            <div class="d-flex flex-column">
                                <span class="me-1">
                                    <?= htmlspecialchars($pokemon['name']) ?>
                                </span>
                                <div>
                                    <span class="badge typeBadge-<?= strtolower(htmlspecialchars($pokemon['type1'])) ?>">
                                        <?= htmlspecialchars($pokemon['type1']) ?>
                                    </span>

                                    <?php if (!empty($pokemon['type2'])): ?>
                                        <span class="badge typeBadge-<?= strtolower(htmlspecialchars($pokemon['type2'])) ?>">
                                            <?= htmlspecialchars($pokemon['type2']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                            </div>

                            <button
                                type="button"
                                class="pokeboxBtn btn <?= $buttonClass ?>"
                                data-pokemon-id="<?= $pokemon['id'] ?>"
                                data-pokemon-name="<?= htmlspecialchars($pokemon['name']) ?>"
                                data-tier="<?= htmlspecialchars($pokemon['tier']) ?>"
                                data-tier-group="<?= htmlspecialchars($tierGroup) ?>"
                                <?= $isOwned ? 'disabled' : '' ?>
                            >
                                <?= $buttonText ?>
                            </button>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


            <!-- NU -->
            <div id="nuPokeboxList" class="row pokebox-tier-section">

                <h2 class="fs-5 badge tierBadge-NU">
                    NU Pokemon

                    <span class="badge text-bg-secondary">+PUBL</span>
                    <span class="badge text-bg-secondary">+PU</span>
                    <span class="badge text-bg-secondary">+ZUBL</span>
                    <span class="badge text-bg-secondary">+ZU</span>
                </h2>

                <?php foreach ($groupedPokemon['NU'] as $pokemon): ?>

                    <?php
                        $isOwned = in_array((int)$pokemon['id'], $ownedPokemon, true);
                        $tierGroup = getTierGroup($pokemon['tier']);

                        if ($isOwned) {
                            $buttonText = 'Owned';
                            $buttonClass = 'btn-secondary';
                        } elseif ($rosterCount >= 12) {
                            $buttonText = 'Swap';
                            $buttonClass = 'btn-primary';
                        } else {
                            $buttonText = 'Add';
                            $buttonClass = 'btn-primary';
                        }
                    ?>

                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 my-2">

                        <div class="border rounded p-2 bg-white d-flex justify-content-between align-items-center">

                            <div class="d-flex flex-column">
                                <span class="me-1">
                                    <?= htmlspecialchars($pokemon['name']) ?>
                                </span>
                                <div>
                                      <span class="badge typeBadge-<?= strtolower(htmlspecialchars($pokemon['type1'])) ?>">
                                        <?= htmlspecialchars($pokemon['type1']) ?>
                                    </span>

                                    <?php if (!empty($pokemon['type2'])): ?>
                                        <span class="badge typeBadge-<?= strtolower(htmlspecialchars($pokemon['type2'])) ?>">
                                            <?= htmlspecialchars($pokemon['type2']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                              
                            </div>

                            <button
                                type="button"
                                class="pokeboxBtn btn <?= $buttonClass ?>"
                                data-pokemon-id="<?= $pokemon['id'] ?>"
                                data-pokemon-name="<?= htmlspecialchars($pokemon['name']) ?>"
                                data-tier="<?= htmlspecialchars($pokemon['tier']) ?>"
                                data-tier-group="<?= htmlspecialchars($tierGroup) ?>"
                                <?= $isOwned ? 'disabled' : '' ?>
                            >
                                <?= $buttonText ?>
                            </button>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </main>

</body>
<script src="../javascript/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>