<?php
    error_reporting(E_ALL);
    ini_set('display_errors',1);

    require_once __DIR__ . '/../includes/connection.php';

    $seasonId = 1;

    $rosterSql = "SELECT users.default_team_name, showdown_pokemon.name
    FROM roster_pkmn
    JOIN active_users
    ON roster_pkmn.active_user_id = active_users.id
    JOIN users
    ON active_users.user_id = users.id
    JOIN showdown_pokemon
    ON showdown_pokemon.id = roster_pkmn.showdown_pokemon_id
    WHERE roster_pkmn.season_id = ?
    AND roster_pkmn.status = 'active'
    ORDER BY users.default_team_name, showdown_pokemon.name 
    " ; // temporary

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
        $rosters[$teamName][] = $row['name'];
    }


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <title>Roster</title>
</head>
<body>

    <!-- For now just banner image -->
    <header>
        <?php include '../includes/banner.php' ?>
    </header>

    <!-- Navbar Sticky -->
    <nav class="px-3 sticky-top navbar navbar-expand-lg" data-bs-theme="dark">
        <?php include '../includes/nav.php' ?>
    </nav>
    
    <main class="container p-3">
        <h1>Roster</h1>
        <div class="row">

            <?php foreach ($rosters as $teamName => $pokemon): ?>

                <div class="col"> <!-- Edit this later to add styling to whole div -->
                    <h3>
                        <?php echo htmlspecialchars($teamName); ?>
                    </h3>
                    <ul class="list-group">
                        <?php foreach ($pokemon as $name): ?>
                            <li class="list-group-item">
                                <?php echo htmlspecialchars($name) ?>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </div>

            <?php endforeach; ?>

        </div>
    </main>

</body>
<script src="../javascript/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</html>