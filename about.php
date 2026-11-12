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

    <title>About</title>
</head>
<body class="bg-body-secondary">
   <nav class="px-3 mb-3 sticky-top navbar navbar-expand-lg bg-dark" data-bs-theme="dark">
        <?php include 'includes/nav-main.php' ?>
    </nav>
    <main class="container p-3 bg-white">

        <div class="w-100 justify-self-start">
            
            <!-- <a href="<?= BASE_URL ?>index.php" class="btn btn-secondary">Back</a> -->

             <a href="<?= BASE_URL ?>index.php" class="d-flex align-items-center">
                
                <i class="backBtn fa-solid fa-arrow-left fa-2x"></i>

             </a>
             
        </div> 
        
        <h1 class="fs-3 text-center">About</h1>
        <hr>

        <div class="d-flex align-items-center flex-column">
            <img src="img/Ascent_Horizontal_Text_GRAD_2.png" height="150" alt="" class="img-fluid">
            <p class="text-center">The Ascent Draft League is a competitive Pokémon draft-style league focused on strategy, team building, and player skill. Each participant drafts a unique roster of Pokémon across multiple tiers, creating balanced and diverse teams for the season. 

            The league is structured into a Round Robin stage followed by a Knockout stage, where players compete in Best-of-One matches. Success depends on preparation, adaptability, and smart roster management, including limited mid-season trades.

            Players are responsible for scheduling their own matches and are expected to maintain clear communication and respectful conduct throughout the season. The Ascent Draft League is designed to deliver a competitive yet community-driven experience for all participants. 
            </p>
        </div>
    </main>
        <footer class="border-top text-center">
            <p>&copy;Copyright Ascent Draft League 2026</p>
    </footer>

    <script src="javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>