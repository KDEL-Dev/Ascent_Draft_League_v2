<?php
    session_start();

    require_once __DIR__ . '/../includes/connection.php';

    $seasonId = 1;

    // QUERY for getting all MATCHUPS
    $sql = "SELECT match_stats.matchup_id, users.default_team_name, users.default_team_mascot,
                    showdown_pokemon.name, match_stats.kills, match_stats.deaths, 
                    match_info.match_replay, match_info.match_result
            FROM match_stats
            JOIN match_info
            ON match_info.id = match_stats.matchup_id
            JOIN roster_pkmn
            ON match_stats.roster_pkmn_id = roster_pkmn.id
            JOIN active_users
            ON active_users.id = roster_pkmn.active_user_id
            JOIN users
            ON users.id = active_users.user_id
            JOIN showdown_pokemon
            ON roster_pkmn.showdown_pokemon_id = showdown_pokemon.id
            WHERE roster_pkmn.season_id = ?
            ORDER BY `match_stats`.`matchup_id` DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$seasonId);
    $stmt->execute();

    $matchupResult = $stmt->get_result();

    $matchups = [];

    while ($row = $matchupResult->fetch_assoc()) 
    {
        $matchupId = $row['matchup_id'];
        $teamName = $row['default_team_name'];

        // Create matchup
        if (!isset($matchups[$matchupId])) {
            $matchups[$matchupId] = [
                'replay' => $row['match_replay'],
                'result' => $row['match_result'],
                'teams' => []
            ];
        }

        // Create team
        if (!isset($matchups[$matchupId]['teams'][$teamName])) {
            $matchups[$matchupId]['teams'][$teamName] = [
                'mascot' => $row['default_team_mascot'],
                'pokemon' => []
            ];
        }

        // Add Pokemon
        $matchups[$matchupId]['teams'][$teamName]['pokemon'][] = [
            'name' => $row['name'],
            'kills' => $row['kills'],
            'deaths' => $row['deaths']
        ];
    }



    // echo '<pre>';
    // print_r($matchups);
    // echo '</pre>'

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <title>Matchups</title>
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
        <div class="p-3 bg-white">
            <h1>Matchups</h1>
            <div class="pb-3 border-bottom">
                <button class="btn btn-primary"><a href="new-matchup.php">New Entry</a></button>
            </div>
            <div class="row">
                <?php foreach ($matchups as $matchupId => $matchup): ?>
                    <div class="col-12 col-md-6 col-lg-4 p-3 g-3 bg-light">
                        <div class="border rounded-1 border-dark-subtle">

                        
                            <?php
                                // What is array keys doing?
                                $teams = array_keys($matchup['teams']);
                                $team1 = $teams[0] ?? 'Team 1';
                                $team2 = $teams[1] ?? 'Team 2';

                                $team1Mascot = $matchup['teams'][$team1]['mascot'] ?? '';
                                $team2Mascot = $matchup['teams'][$team2]['mascot'] ?? '';

                            ?>
                            <div>
                                <h2 class="mb-0 text-center"><?=  htmlspecialchars($team1) ?> <?= htmlspecialchars($team1Mascot) ?></h2>
                            </div>
                            <div class="table-responsive">
                                <table class="table mb-0 table-bordered">  
                                    <thead>
                                        <tr>
                                            <th>Pokemon</th>
                                            <th>Kills</th>
                                            <th>Deaths</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-group-divider">
                                        <?php foreach ($matchup['teams'][$team1]['pokemon'] as $pokemon): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($pokemon['name']) ?></td>
                                                <td><?= $pokemon['kills'] ?></td>
                                                <td><?= $pokemon['deaths'] ?></td>
                                            </tr>
                                        <?php endforeach ?>
                                    </tbody>
                                </table>

                                <div class="matchVsDivider d-flex p-1 border-top border-bottom">
                                    <div class="flex-grow-1 d-flex align-items-center justify-content-center">
                                        <h2 class="mb-0">VS</h2>
                                    </div>
                                    <div>
                                        <a
                                            href="<?= htmlspecialchars($matchup['replay']) ?>"
                                            target="_blank"
                                            class="btn btn-outline-light">
                                            Replay
                                        </a>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table mb-0 table-bordered">  
                                        <thead>
                                            <tr>
                                                <th>Pokemon</th>
                                                <th>Kills</th>
                                                <th>Deaths</th>
                                            </tr>
                                        </thead>
                                        <tbody class="table-group-divider">
                                            <?php foreach ($matchup['teams'][$team2]['pokemon'] as $pokemon): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($pokemon['name']) ?></td>
                                                    <td><?= $pokemon['kills'] ?></td>
                                                    <td><?= $pokemon['deaths'] ?></td>
                                                </tr>
                                            <?php endforeach ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div>
                                    <h2 class="mb-0 text-center"><?=  htmlspecialchars($team2) ?> <?= htmlspecialchars($team2Mascot) ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
        
    </main>
    <script src="../javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>