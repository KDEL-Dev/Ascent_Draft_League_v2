<?php

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    session_start();

    require_once __DIR__ . '/../includes/connection.php';

    $seasonId = 1;

    $sql = "SELECT
            users.default_team_name,
            showdown_pokemon.name,
            pokemon_tier_per_season.tier,
            COUNT(match_stats.id) AS usage_count,
            COALESCE(SUM(match_stats.kills), 0) AS total_kills,
            COALESCE(SUM(match_stats.deaths), 0) AS total_deaths
        FROM roster_pkmn
        JOIN active_users
            ON active_users.id = roster_pkmn.active_user_id
        JOIN users
            ON users.id = active_users.user_id
        JOIN showdown_pokemon
            ON showdown_pokemon.id = roster_pkmn.showdown_pokemon_id
        JOIN pokemon_tier_per_season
            ON pokemon_tier_per_season.showdown_pokemon_id = showdown_pokemon.id
            AND pokemon_tier_per_season.season_id = roster_pkmn.season_id
        LEFT JOIN match_stats
            ON match_stats.roster_pkmn_id = roster_pkmn.id
        WHERE roster_pkmn.season_id = ?
        GROUP BY
            users.default_team_name,
            showdown_pokemon.name,
            pokemon_tier_per_season.tier";


    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $seasonId);
    $stmt->execute();

    $result = $stmt->get_result();

    $teams = [];

    while ($row = $result->fetch_assoc()) 
    {
        $team = $row['default_team_name'];

        if (!isset($teams[$team])) {
            $teams[$team] = [
                'usage_count' => 0,
                'total_kills' => 0,
                'total_deaths' => 0,
                'pokemon' => []
            ];
        }

        $teams[$team]['usage_count'] += $row['usage_count'];
        $teams[$team]['total_kills'] += $row['total_kills'];
        $teams[$team]['total_deaths'] += $row['total_deaths'];

        $teams[$team]['pokemon'][] = [
            'name' => $row['name'],
            'tier' => $row['tier'],
            'usage' => $row['usage_count'],
            'kills' => $row['total_kills'],
            'deaths' => $row['total_deaths']
        ];
    }

    // Group each team's Pokémon into OU, UU, RU, and NU
    $tierGroups = [
        'OU' => ['OU', 'UUBL'],
        'UU' => ['UU', 'RUBL'],
        'RU' => ['RU', 'NUBL'],
        'NU' => ['NU', 'PUBL', 'PU', 'ZUBL', 'ZU']
    ];

    foreach ($teams as &$team) {

        $groupedPokemon = [
            'OU' => [],
            'UU' => [],
            'RU' => [],
            'NU' => []
        ];

        foreach ($team['pokemon'] as $pokemon) {

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

        // Replace original Pokémon array with grouped version
        $team['pokemon'] = $groupedPokemon;
    }

    unset($team);




    

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <title>Statistics - Ascent</title>
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
        <div class="row p-3 bg-white">
            <h1 class="border-bottom fs-3">Statistics</h1>
            <?php foreach ($teams as $teamName => $team): ?>
                <div class="col-12 col-lg-6">
                    <div class="p-3 bg-light">
                        <h2 class="fs-4"><?= htmlspecialchars($teamName) ?></h2>
                        <table class="statsTable table table-sm table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th scope="col">Pokemon</th>
                                    <th scope="col" style="width:12%">Kills</th>
                                    <th scope="col" style="width:12%">Deaths</th>
                                    <th scope="col" style="width:12%">Usage</th>
                                    <th scope="col" style="width:12%">+/-</th>
                                </tr>
                            </thead>
                            <tbody class="table-group-divider">
                                <?php foreach ($team['pokemon'] as $tier => $pokemonGroup): ?>

                                    <?php if (!empty($pokemonGroup)): ?>

                                        <?php foreach ($pokemonGroup as $pokemon): ?>

                                            <tr>
                                                <td><?= htmlspecialchars($pokemon['name']) ?> <span class="tierBadge-<?= htmlspecialchars($pokemon['tier']) ?> badge rounded-5"><?= htmlspecialchars($pokemon['tier']) ?></span></td>
                                                <td><?= $pokemon['kills'] ?></td>
                                                <td><?= $pokemon['deaths'] ?></td>
                                                <td><?= $pokemon['usage'] ?></td>
                                                <td><?= $pokemon['kills'] - $pokemon['deaths'] ?></td>
                                            </tr>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </main>
    <script src="../javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>