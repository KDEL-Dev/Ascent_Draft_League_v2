<?php

    // error 
    
    // start session
    session_start();

    // connect to db
    require_once __DIR__ . '/../includes/connection.php';

    // temp season number
    $seasonId = 1;

    // Query - Because I needed losses as well, the query is a little different then im use to. Look into CASE later.
    $standingSql = "SELECT
                    active_users.id AS active_user_id,
                    users.default_team_name,
                    users.default_team_mascot,
                    SUM(
                        CASE
                            WHEN match_info.match_result = active_users.id
                            THEN 1
                            ELSE 0
                        END
                    ) AS wins,
                    SUM(
                        CASE
                            WHEN match_info.match_result IS NOT NULL
                                AND match_info.match_result != active_users.id
                            THEN 1
                            ELSE 0
                        END
                    ) AS losses

                FROM active_users
                JOIN users
                    ON active_users.user_id = users.id

                LEFT JOIN match_info
                    ON (
                        match_info.player1_au_id = active_users.id
                        OR match_info.player2_au_id = active_users.id
                    )
                    AND match_info.season_id = ?

                WHERE active_users.season_id = ?

                GROUP BY
                    active_users.id,
                    users.default_team_name,
                    users.default_team_mascot
                ORDER BY
                    wins DESC,
                    losses ASC
                ";

    $stmt = $conn->prepare($standingSql);
    $stmt->bind_param("ii", $seasonId, $seasonId);
    $stmt->execute();

    $standingResult = $stmt->get_result();
    // fetch all gets everything from the result all at once. 
    // Method I've been using before only gets one and I normally have to loop to get rest.
    $standings = $standingResult->fetch_all(MYSQLI_ASSOC);

    // POKEMON LEADERBOARD

    $pkmnLeaderSql = "SELECT showdown_pokemon.name, pokemon_tier_per_season.tier,
                   SUM(match_stats.kills) AS total_kills,
                   SUM(match_stats.deaths) AS total_deaths,
                   users.default_team_name
                   FROM match_stats
                   JOIN roster_pkmn
                    ON roster_pkmn.id = match_stats.roster_pkmn_id
                   JOIN showdown_pokemon
                    ON showdown_pokemon.id = roster_pkmn.showdown_pokemon_id
                    JOIN active_users
                    ON roster_pkmn.active_user_id = active_users.id
                    JOIN users
                    ON users.id = active_users.user_id
                    JOIN pokemon_tier_per_season
                    ON pokemon_tier_per_season.showdown_pokemon_id = roster_pkmn.showdown_pokemon_id
              
                   WHERE roster_pkmn.season_id = ?
                   GROUP BY showdown_pokemon.id, showdown_pokemon.name, pokemon_tier_per_season.tier, users.default_team_name
                   ORDER BY total_kills DESC, total_deaths ASC
                   LIMIT 5;
                ";
    $PkmnStmt = $conn->prepare($pkmnLeaderSql) ;
    $PkmnStmt->bind_param("i", $seasonId);
    $PkmnStmt->execute();

    $pkmnResult = $PkmnStmt->get_result();
    $pkmnLeader = $pkmnResult->fetch_all(MYSQLI_ASSOC);

    // echo '<pre>';
    // print_r($pkmnLeader);
    // echo '</pre>'
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <title>Standings - Ascent</title>
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
            <h1 class="fs-3">Standings</h1>

            <hr> 
            
            <div class="col-12 col-lg-4">
                <h2 class="fs-4">Regular Season</h2>
                <table id="standingsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Team</th>
                            <th scope="col">Wins</th>
                            <th scope="col">Losses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($standings as $index => $team): ?>

                            <tr>
                                <td class="fs-5"><?= $index + 1 ?></td>

                                <td class="fs-5">
                                    <?= htmlspecialchars($team['default_team_name']) ?>
                                    <?= htmlspecialchars($team['default_team_mascot']) ?>                                   
                                </td>

                                <td class="fs-5">
                                    <?= $team['wins'] ?>
                                </td>

                                <td class="fs-5">
                                    <?= $team['losses'] ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>
            </div>
            <div id="killLeaderCont" class="col-12 col-lg-8">
                <h2 class="fs-4">Pokemon Kill Leaders</h2>
                <div class="card-group">
                    <!-- For every item, create a new card -->
                     <?php foreach ($pkmnLeader as $index => $pkmn): ?> <!-- colon is important for some reason - look into -->
                    <div class="card d-sm-flex flex-row d-md-block">
                        
                        <?php
                            $rankClass = match ($index) {
                                0 => 'rank-gold',
                                1 => 'rank-silver',
                                2 => 'rank-bronze',
                                3, 4 => 'rank-grey',
                                default => '',
                            };
                        ?>
                        <div class="card-header <?= $rankClass ?>">
                            <p class="mb-0 text-center text-white fw-bold"><?= $index + 1 ?></p>
                        </div>
                        <div class="pkmnLeaderImgCont">
                            <img src="" alt="<?= htmlspecialchars($pkmn['name']) ?>" data-pkmn-name="<?= htmlspecialchars($pkmn['name']) ?>" class="pkmnLeaderImg">
                        </div>
                        <div class="card-body">
                            <div>
                                <p class="card-title fw-bold mb-0"><?= htmlspecialchars($pkmn['name']) ?></p>
                                <p class="tierBadge-<?= htmlspecialchars($pkmn['tier']) ?>  mb-0 badge rounded-5 d-inline-block"><?= htmlspecialchars($pkmn['tier']) ?></p>
                            </div>
                            <p class="card-text mb-0 d-flex align-items-center">Kills: <span class="ms-1 fw-bold fs-4"><?= htmlspecialchars($pkmn['total_kills']) ?></span></p>
                            <p class="card-text d-flex align-items-center">Deaths: <span class="ms-1 fw-bold fs-4"><?= htmlspecialchars($pkmn['total_deaths']) ?></span></p>
                        </div>
                        <div class="card-footer fw-bold">
                            <p class="card-text text-center"><?= htmlspecialchars($pkmn['default_team_name']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>  
            </div>
        </div>
        
    </main>
    <script src="../javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</html>