<?php
    session_start();

    require_once __DIR__ . '/../includes/auth.php';
    
    $seasonId = 1;

    $sql = "SELECT draft_picks.round_number, draft_picks.pick_number, showdown_pokemon.name, pokemon_tier_per_season.tier, users.default_team_name
    FROM draft_picks
    JOIN active_users
    ON draft_picks.active_user_id = active_users.id
    JOIN users
    ON active_users.user_id = users.id
    JOIN showdown_pokemon
    ON draft_picks.showdown_pokemon_id = showdown_pokemon.id
    JOIN pokemon_tier_per_season
    ON draft_picks.showdown_pokemon_id = pokemon_tier_per_season.showdown_pokemon_id
    WHERE draft_picks.season_id = ?
    ORDER BY draft_picks.pick_number ASC;
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$seasonId);
    $stmt->execute();

    $recapResults = $stmt->get_result();

    while($row = $recapResults->fetch_assoc())
    {
        $roundNumber = $row['round_number'];

        $round[$roundNumber][] = 
        [
            'pick_number' => $row['pick_number'],
            'name' => $row['name'],
            'tier' => $row['tier'],
            'default_team_name' => $row['default_team_name']
        ];
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    

    <title>Draft Recap</title>
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
            <h1 class="fs-3">Draft Recap</h1>
            <!-- <p>Date: </p> -->
            <hr>
            <?php foreach ($round as $roundNumber => $picks): ?>
            <div class="mt-3 col-sm-12 col-md-6 col-lg-4 col-xl-3">
                <h2 class="fs-5 border-bottom">Round <?= htmlspecialchars($roundNumber) ?></h2>
                <?php foreach ($picks as $pick): ?>
                    <ul id="draftRecapList" class="ps-0 list-group">
                        <li class="list-group-item d-flex">
                            <div class="recapPickNum border-end">
                                <?= htmlspecialchars($pick['pick_number']) ?>
                            </div>
                            <div class="flex-grow-1 d-flex">
                                <div class="ps-3 fw-bold">
                                    <?= htmlspecialchars($pick['name']) ?>
                                </div>

                                <div class="tierBadge-<?= htmlspecialchars($pick['tier']) ?> mb-0 ms-2 badge rounded-5 d-flex align-items-center">
                                    <?= htmlspecialchars($pick['tier']) ?>
                                </div>
                            </div>
                            

                            <div class="fw-bold border-start ps-2 d-flex justify-content-end">
                                <?= htmlspecialchars($pick['default_team_name']) ?>
                            </div>
                        </li>
                    </ul>

                <?php endforeach; ?>
            </div>
            <?php endforeach ?>
        </div>
    </main>


</body>
</html>