<?php
    // error_reporting(E_ALL);
    // ini_set('display_errors',1);

    require_once __DIR__ . '/../includes/connection.php';

    $seasonId = 1;

    $rosterSql = "SELECT users.default_team_name, users.default_team_mascot, showdown_pokemon.name, pokemon_tier_per_season.tier
                FROM roster_pkmn
                JOIN active_users
                ON roster_pkmn.active_user_id = active_users.id
                JOIN users
                ON active_users.user_id = users.id
                JOIN showdown_pokemon
                ON showdown_pokemon.id = roster_pkmn.showdown_pokemon_id
                JOIN pokemon_tier_per_season
                ON roster_pkmn.showdown_pokemon_id = pokemon_tier_per_season.showdown_pokemon_id
                WHERE roster_pkmn.season_id = ?
                AND roster_pkmn.status = 'active'
                ORDER BY users.default_team_name, showdown_pokemon.name 
            " ; 

    $stmt = $conn->prepare($rosterSql);

    if(!$stmt)
    {
        die("Prepared Failed: " . $conn->error); // Where does error come from?
    }

    $stmt->bind_param("i", $seasonId);
    $stmt->execute();

    $rosterResults = $stmt->get_result();

    if(!$rosterResults)
    {
        die("Query Failed " . $stmt->error);
    }

    $rosters = [];



    // Here is how I sort each pokemon into seperate roster arrays
    while ($row = $rosterResults->fetch_assoc()) 
    {
        $teamName = $row['default_team_name'];
        $teamMascot = $row['default_team_mascot'];

        $rosters[$teamName]['mascot'] = $teamMascot;

        $rosters[$teamName]['pokemon'][] = [
            'name' => $row['name'],
            'tier' => $row['tier']
        ];
    }

    // Group each team's Pokémon into OU, UU, RU, and NU
    $tierGroups = 
    [
        'OU' => ['OU', 'UUBL'],
        'UU' => ['UU', 'RUBL'],
        'RU' => ['RU', 'NUBL'],
        'NU' => ['NU', 'PUBL', 'PU', 'ZUBL', 'ZU']
    ];

    foreach ($rosters as &$roster) {

        $groupedPokemon = 
        [
            'OU' => [],
            'UU' => [],
            'RU' => [],
            'NU' => []
        ];

        foreach ($roster['pokemon'] as $pokemon) {

            foreach ($tierGroups as $group => $tiers) {

                if (in_array($pokemon['tier'], $tiers)) {
                    $groupedPokemon[$group][] = $pokemon;
                    break;
                }
            }
        }

        // Sort Pokémon alphabetically within each tier
        foreach ($groupedPokemon as &$pokemonGroup) {
            usort($pokemonGroup, function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });
        }
        unset($pokemonGroup);

        // Replace the original Pokémon array with the grouped version
        $roster['pokemon'] = $groupedPokemon;
    }

    unset($roster);



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

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
            <h1>Roster</h1>
            <?php foreach ($rosters as $teamName => $roster): ?>

                <div class="mb-3 col-12 col-md-6 col-lg-3">

                    <div class="rosterCard card">

                        <div class="card-header">
                            <h2>
                                <?php echo htmlspecialchars($teamName); ?>
                                <?php echo htmlspecialchars($roster['mascot']); ?>
                            </h2>
                        </div>

                        <ul class="list-group list-group-flush">

                            <?php foreach ($roster['pokemon'] as $tier => $pokemonGroup): ?>

                                <?php foreach ($pokemonGroup as $pokemon): ?>

                                    <li class="list-group-item rosterPokemon">

                                        <div class="rosterCardTier tier-<?= strtolower($tier) ?>">
                                            <?= htmlspecialchars($tier) ?>
                                        </div>

                                        <div class="rosterCardPkmn">
                                            <?= htmlspecialchars($pokemon['name']) ?>
                                        </div>

                                        <div>
                                            <!-- Typings -->
                                        </div>
                                    </li>

                                <?php endforeach; ?>

                            <?php endforeach; ?>

                        </ul>



                    </div>

                </div>

            <?php endforeach; ?>


        </div>
    </main>

</body>
<script src="../javascript/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</html>