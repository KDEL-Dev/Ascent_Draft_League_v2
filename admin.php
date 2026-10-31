<?php 

    require_once __DIR__ . '/includes/auth.php';

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/4a4034fc29.js" crossorigin="anonymous"></script>

    <title>Admin Settings</title>
</head>
<body class="bg-body-secondary">
    <nav class="px-3 mb-3 sticky-top navbar navbar-expand-lg bg-dark" data-bs-theme="dark">
        <?php include 'includes/nav-main.php' ?>
    </nav>
    <main class="p-3 bg-white container">

        <h1 class="fs-3">Admin Settings</h1>
        <hr>

        <div class="row">
            <div class="mb-3">
                <a href="" class="btn btn-primary">Member List</a>
            </div>
            <div>
                <a href="<?= BASE_URL ?>admin/season-list.php" class="btn btn-primary">Season List</a>
            </div>
        </div>
        

    </main>
<script src="javascript/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>