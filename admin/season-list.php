<?php
    require_once  __DIR__ . '/../includes/auth.php';

    $userId = $_SESSION['user_id'];

    //------------------------------
    //----- RETRIEVE TEAM NAME -----
    //------------------------------

    $sql = "SELECT default_team_name 
            FROM users
            WHERE users.id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt-> bind_param("i",$userId);
    $stmt->execute();

    $teamNameResult = $stmt->get_result();

    $teamName = $teamNameResult->fetch_assoc()['default_team_name'];

    // Must I close the connection here?


    $seasonsSql = "SELECT
                        id,
                        season_number,
                        season_name,
                        draft_date,
                        start_date,
                        created_at,
                        is_active,
                        format
                    FROM seasons
                    ORDER BY season_number ASC
    ";

    $seasonsResult = $conn->query($seasonsSql);

    if (!$seasonsResult) {
        die("Failed to retrieve seasons: " . $conn->error);
    }

    // ----------------
    // GRAB ALL SEASONS
    // ----------------
    
    $sql ="SELECT s.id, s.season_number, s.season_name, s.created_at, s.format
           FROM seasons s
    ";


    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $stmtResults = $stmt->get_result();
    
    $seasons = [];

    while($row = $stmtResults->fetch_assoc())
    {
        $seasons[] = [
            'id' => $row['id'],
            'season_number' => $row['season_number'],
            'season_name' => $row['season_name'],
            'created_at' => $row['created_at'],
            'format' => $row['format']
        ];
    }

    $stmt->close()

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/4a4034fc29.js" crossorigin="anonymous"></script>

    <title>Season List - Admin</title>
</head>
<body class="bg-body-secondary">
    <nav class="px-3 mb-3 sticky-top navbar navbar-expand-lg bg-dark" data-bs-theme="dark">
        <?php include '../includes/nav-main.php' ?>
    </nav>
    <main class="p-3 bg-white container">

        <h1 class="fs-3">Season List</h1>
        <hr>

        <div class="table-responsive">
        
            <table class="table">
                <thead>
                    <tr>
                        <th>Season Number</th>
                        <th>Seeason Name</th>
                        <th>Format</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($seasons as $season): ?>
                        <tr>
                            <td><?= htmlspecialchars($season['season_number']) ?></td>
                            <td><?= htmlspecialchars($season['season_name']) ?></td>
                            <td><?= htmlspecialchars($season['format']) ?></td>
                            <td><?= htmlspecialchars($season['created_at']) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

    </main>
<script src="javascript/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>