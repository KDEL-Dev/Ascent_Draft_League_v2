<?php

    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    require_once __DIR__ . '/../../includes/auth.php';

        // 1. Get seasonId from url
    $seasonId = filter_input(INPUT_GET,'seasonId',FILTER_VALIDATE_INT,['options' => ['min_range' => 1]]);

    // 2. Validate ID
    if($seasonId === false || $seasonId === null)
    {
        http_response_code(400);
        exit("Invalid season ID.");
    }

    // 3. Check that season exists
    $seasonSql ="SELECT
                 id, season_number, season_name, format
                 FROM
                 seasons
                 WHERE
                 id = ?
                 LIMIT 
                 1
    ";

    $seasonStmt = $conn->prepare($seasonSql);
    $seasonStmt->bind_param("i", $seasonId);
    $seasonStmt->execute();

    $seasonResult = $seasonStmt->get_result();
    $season = $seasonResult->fetch_assoc();

    $seasonStmt->close();

    // 4. Stop if eseason doesnt exist
    if(!$season)
    {
        http_response_code(404);
        exit("Season not found.");
    }


    // --------------
    // Check if ADMIN
    // --------------

    

    if (!$isAdmin) 
    {
        http_response_code(403);
        exit('Access denied.');
    }



    // --------------------
    // RETRIEVE ALL MEMBERS
    // --------------------

    $userSql ="SELECT users.default_team_name, users.email
               FROM users
            
    ";

    $stmt =$conn->prepare($userSql);
    $stmt->execute();

    $userResults = $stmt->get_result();

    $users = [];

    while ($row = $userResults->fetch_assoc()) {
    $users[] = [
        'default_team_name' => $row['default_team_name'],
        'email' => $row['email']
    ];
}

    $stmt->close();

    // echo '<pre>';
    // print_r($users);
    // echo '</pre>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/4a4034fc29.js" crossorigin="anonymous"></script>

    <title>Edit Competitors - Ascent</title>
</head>
<body class="bg-body-secondary">
    <!-- For now just banner image -->
    <header>
        <?php include '../../includes/banner.php' ?>
    </header>

    <!-- Navbar Sticky -->
    <nav class="px-3 sticky-top navbar navbar-expand-lg" data-bs-theme="dark">
        <?php include '../../includes/nav.php' ?>
    </nav>

    <main class="p-3 bg-white container">
        <h1 class="f-3">Edit Competitors</h1>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Team Name</th>
                        <th>Emails</th>
                        <th>Competitor Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['default_team_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <select name="" id="" class="form-select form-select-sm">
                                    <option value="No">No</option>
                                    <option value="Yes">Yes</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="../../javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>