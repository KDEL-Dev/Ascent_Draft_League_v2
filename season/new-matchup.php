<?php

    error_reporting(E_ALL);
    ini_set('display_errors',1);

    require_once __DIR__ . '/../includes/connection.php';

    $seasonId = 1;

    $sql = "SELECT active_users.id, users.default_team_name, users.default_team_mascot
        FROM active_users
        JOIN users 
        ON active_users.user_id = users.id
        WHERE active_users.season_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$seasonId);
    $stmt->execute();

    $stmt->execute();

    $teamResults = $stmt->get_result();
    $teamList = $teamResults->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <title>New Matchup</title>
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
            <div class="my-3 pb-3 border-bottom">
                <a href="matchups.php" class="btn btn-primary">Return to Matchups</a>
            </div>
            <div class="row">
                <form method="POST" action="">
                    <div id="newMatchTeamSelect1" class="col-12 col-lg-6">
                        <label for="team1" class="form-label">Team 1</label>

                        <select name="team1" id="team1" class="form-select">
                            <option value="">Select Team 1</option>

                            <?php foreach ($teamList as $team): ?>
                                <option value="<?= $team['id'] ?>">
                                    <?= htmlspecialchars($team['default_team_name']) ?>
                                    <?= htmlspecialchars($team['default_team_mascot']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="newMatchTeamSelect2" class="col-12 col-lg-6">
                        <label for="team2" class="form-label">Team 2</label>

                        <select name="team2" id="team2" class="form-select">
                            <option value="">Select Team 2</option>

                            <?php foreach ($teamList as $team): ?>
                                <option value="<?= $team['id'] ?>">
                                    <?= htmlspecialchars($team['default_team_name']) ?>
                                    <?= htmlspecialchars($team['default_team_mascot']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="pt-3 mt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            Submit
                        </button>
                    </div>
                </form>

            </div>
            <div class="row">
                <div id="newMatchRoster1" class="col-12 col-lg-6">

                </div>
                <div id="newMatchRoster2" class="col-12 col-lg-6">

                </div>
            </div>  
            <div id="newMatchAddStats" class="row">
                <p class="text-danger">*Record the Kill/Deaths from Match</p>
                <div class="col-12 col-lg-6 p-3">
                    <div class="table-responsive">
                        <table class="table table-dark table-striped">
                            <thead>
                                <tr>
                                    <th>Pokemon</th>
                                    <th>Kills</th>
                                    <th>Deaths</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Garchomp</td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                </tr>
                                <tr>
                                    <td>Muk</td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12 col-lg-6 p-3">
                    <div class="table-responsive">
                        <table class="table table-dark table-striped">
                            <thead>
                                <tr>
                                    <th>Pokemon</th>
                                    <th>Kills</th>
                                    <th>Deaths</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Scrafty</td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                </tr>
                                <tr>
                                    <td>Clefable</td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="pt-3 border-top">
                <input type="submit" value="Submit" class="btn btn-primary">
            </div>
        </div>
        
    </main>
    <script src="../javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>