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

    <title>Legacy Stats</title>
</head>
<body class="bg-body-secondary">
    <nav class="px-3 mb-3 sticky-top navbar navbar-expand-lg bg-dark" data-bs-theme="dark">
        <?php include 'includes/nav-main.php' ?>
    </nav>
    <main>
        <div class="container p-3 bg-white">
            <h1 class="fs-3">Legacy Stats</h1>   
            <div class="border-bottom p-3">
                <!-- This will be buttons/controls for stats -->
                 <a href="#" class="ms-auto btn btn-primary">New Entry</a> 
            </div>
        </div>
        <div class="container p-3 bg-white">
            <!-- Results will appear in here -->

            <!-- I guess this really should be two different statistics. One for Players records and One for 
             Pokemon Stats. I'll start off with just playoff stats? Or do I need to do this in a way where I input
             all stats from a match which includes pokemon stats and game result? -->
             <table>

             </table>
        </div>
        

    </main>
    <script src="javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>