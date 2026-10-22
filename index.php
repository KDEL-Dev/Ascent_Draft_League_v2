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

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/4a4034fc29.js" crossorigin="anonymous"></script>

    
    <title>Ascent Draft League</title>
</head>
<body class=" bg-body-secondary">
   <nav class="px-3 mb-3 sticky-top navbar navbar-expand-lg bg-dark" data-bs-theme="dark">
        <div class="container-fluid position-relative">
            <!-- Logo -->
            <a href="index.php" class="navbar-brand">
                <img
                    src="img/Ascent_Horizontal_Text_GRAD_2.png"
                    alt="Ascent Grad Logo"
                    height="50"
                    style="width: auto;"
                >
            </a>
            <!-- Collapse Button -->
            <button 
            type="button" data-bs-toggle = "collapse" data-bs-target="#navbarSupportedContentHome" 
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation" 
            class="navbar-toggler">
                <span class="navbar-toggler-icon"></span>
            </button>
            <!-- Main Nav Buttons -->
            <div id="navbarSupportedContentHome" class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item" href="#">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item" href="#">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin-main.php">Admin Settings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

   <div class="d-flex flex-column">

   
    <main id="indexPage" class="container my-3 p-3 bg-white rounded-2">
            <h1 class="fs-3 text-center">Ascent Draft League</h1>
            <!-- Current Season -->
            <div id="indexTopCont" class="row p-3">
                <div class="col-12 col-lg-6 px-0">
                    <div class="h-100 p-3 mx-1 border rounded-2">
                        <h2 class="fs-5 text-center fw-bold">Current Season</h2>
                        <div class="card">
                            <div class="row g-0">
                                <div class="col-md-4 bg-dark">
                                    <img class="img-fluid" src="img/Ascent Grad Logo w Text.svg" alt="">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <p class="card-title fs-5 fw-bold">Season 14</p>
                                        <p class="card-text">name:</p>
                                        <p class="card-text">format:</p>
                                        <a href="#" class="btn btn-primary">Enter</a>
                                    </div>
                                </div>  
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div id="homeAboutCont" class="h-100 p-3 mx-1 border rounded-2">
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fa-solid fa-play"></i>
                            <h2 class="mx-3 fs-5 text-center fw-bold mb-0">About - Ascent Draft League</h2>
                            <i class="fa-solid fa-play"></i>
                        </div>
                        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Exercitationem doloribus atque 
                            enim odio obcaecati, velit repellendus tempore? Dignissimos rem, quasi porro impedit alias 
                            temporibus, voluptatibus illum nulla earum deserunt reiciendis?
                        </p>
                        <div class="d-flex justify-content-center">
                            <a href="about.php" class="btn btn-primary">Read More</a>
                        </div>
                       
                    </div>
                    
                </div>
            </div>

            <!-- Previous Seasons -->

            <div id="previousSeasonCont" class="border rounded-3 my-3 p-3">

                <h2 class="fs-4 mb-3 text-center">Previous Seasons</h2>

                <?php if ($seasonsResult->num_rows > 0): ?>

                    <div class="row g-3">

                        <?php while ($season = $seasonsResult->fetch_assoc()): ?>

                            <div class="col-6 col-md-4 col-lg-3">

                                <a
                                    href="season/draft.php?seasonId=<?= (int) $season['id'] ?>"
                                    class="text-decoration-none text-reset"
                                >
                                    <div class="card h-100">

                                        <div class="card-header">
                                            
                                            <p class="card-text text-center">
                                                <strong>Season Number:</strong>
                                                <span class="fs-4">
                                                    <?= (int) $season['season_number'] ?>
                                                </span>
                                                
                                            </p>
                                        </div>
                                            <img src="img/Ascent Grad Logo w Text.svg" alt="" class="card-img-top h-50">

                                       
                                        <div class="card-body border-top">
                    
                                            <h3 class="card-title fs-4 text-center">
                                                <?= htmlspecialchars(
                                                    $season['season_name']
                                                    ?: 'Season ' . $season['season_number']
                                                ) ?>
                                            </h3>

                                            <p class="card-text text-center">
                                                <strong>Format:</strong>
                                                <?= htmlspecialchars($season['format'] ?? 'Not set') ?>
                                            </p>

                                            <p class="card-text text-center">
                                                <strong>Status:</strong>
                                                <?= (int) $season['is_active'] === 1
                                                    ? 'Active'
                                                    : 'Inactive' ?>
                                            </p>

                                            <?php if (!empty($season['start_date'])): ?>
                                                <p class="card-text text-center">
                                                    <strong>Start Date:</strong>
                                                    <?= htmlspecialchars($season['start_date']) ?>
                                                </p>
                                            <?php endif; ?>

                                            <!-- <div class="d-flex justify-content-center">
                                                <span class="btn btn-primary">
                                                    Enter Season
                                                </span>
                                            </div> -->
                                            

                                        </div>

                                        <div class="card-footer">
                                            <button class="btn">Enter Season</button>
                                        </div>

                                    </div>
                                </a>

                            </div>

                        <?php endwhile; ?>

                    </div>

                <?php else: ?>

                    <p>No seasons have been created yet.</p>

                <?php endif; ?>

            </div>
            <!-- Legacy Stats -->
            <div class="row mb-3">
                <div class="col-lg-6 my-1">
                    <div id="statisticsCont" class="h-100 p-3 border rounded-2 d-flex justify-content-center flex-column align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-chart-simple"></i>
                            <h2 class="fs-4 mb-0 mx-3">Legacy Stats</h2>                            
                            <i class="fa-solid fa-chart-simple"></i>
                        </div>
                        <p>Stats from all previous seasons</p>
                        <div class="flex-grow-1">
                            <a class="btn btn-primary" href="statistics.php">Enter</a>
                        </div>
                        
                    </div>
                </div>
                
                <div class="col-lg-6 my-1">
                    <div id="pastChampionsCont" class=" p-3 h-100 border rounded-2 d-flex justify-content-center flex-column align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-crown"></i>
                            <h2 class="fs-4 mb-0 mx-3">Past Champions</h2>
                            <i class="fa-solid fa-crown"></i>
                        </div>    
                    
                        <p>List of all previous league winners and their teams</p>
                        <a href="champions.php" class="btn btn-primary">Enter</a>
                    </div>
                </div>
                
            </div>
            <div class="row">
                <div class="col-12">
                    <div id="homeCreateSeasonCont" class="p-3 border rounded-3 d-flex justify-content-center align-items-center flex-column">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-plus"></i>
                            <h2 class="fs-4 mx-3">Create New Season</h2>
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <div>
                            <p>Click Below to create a new  season</p>
                        </div>
                        <div>
                            <a id="createNewSeason" href="create-season.php" class="btn btn-primary">Create</a>
                        </div>
                    </div>
                </div>
            </div>
            
    </main>
    <footer class="border-top text-center">
            <p>Copyright Ascent Draft League</p>
    </footer>
   </div>
</body>
    <script src="javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>