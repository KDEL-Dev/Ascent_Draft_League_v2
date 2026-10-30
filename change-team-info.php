<?php 
    require_once __DIR__ . '/includes/auth.php';

    $userId = $_SESSION['user_id'];
    $error = '';
    $success = '';

    //--------------------------------------------------------------------------
    // Fetch Pokémon list from PokéAPI (For validation)
    //--------------------------------------------------------------------------
    $apiUrl = "https://pokeapi.co/api/v2/pokemon?limit=1200";
    $pokemonList = [];

    $apiResponse = @file_get_contents($apiUrl);
    if ($apiResponse !== false) {
        $data = json_decode($apiResponse, true);
        if (isset($data['results'])) {
            foreach ($data['results'] as $pokemon) {
                $pokemonList[] = ucfirst($pokemon['name']);
            }
        }
    }
    sort($pokemonList);

    //--------------------------------------------------------------------------
    // Handle Form Submission (Update)
    //--------------------------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newTeamName   = strtoupper(trim($_POST['newTeamName'] ?? ''));
        $newTeamMascot = trim($_POST['newTeamMascot'] ?? '');

        // Validation
        if (empty($newTeamName) || empty($newTeamMascot)) {
            $error = "Please fill in both fields.";
        } elseif (strlen($newTeamName) > 5) {
            $error = "Team abbreviation must be 5 characters or fewer.";
        } elseif (!in_array($newTeamMascot, $pokemonList, true)) {
            $error = "Please select a valid Pokémon from the list.";
        } else {
            // Update Query
            $updateSql = "UPDATE users SET default_team_name = ?, default_team_mascot = ? WHERE id = ?";
            $stmt = $conn->prepare($updateSql);

            if ($stmt) {
                $stmt->bind_param("ssi", $newTeamName, $newTeamMascot, $userId);
                
                if ($stmt->execute()) {
                    $success = "Team information updated successfully!";
                } else {
                    $error = "Failed to update team information. Please try again.";
                }
                $stmt->close();
            } else {
                $error = "Database query error.";
            }
        }
    }

    //--------------------------------------------------------------------------
    // RETRIEVE CURRENT TEAM INFO (Refreshed after update)
    //--------------------------------------------------------------------------
    $sql = "SELECT default_team_name, default_team_mascot
            FROM users
            WHERE users.id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $teamInfoResult = $stmt->get_result();
    $teamRow = $teamInfoResult->fetch_assoc();

    if ($teamRow) {
        $teamName = $teamRow['default_team_name'];
        $teamMascot = $teamRow['default_team_mascot'];
    } else {
        $teamName = '';
        $teamMascot = '';
    }

    // Seasons Query (Rest of your existing code...)
    $seasonsSql = "SELECT id, season_number, season_name, draft_date, start_date, created_at, is_active, format
                   FROM seasons
                   ORDER BY season_number ASC";
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

    <title>Change Team Info</title>
</head>
<body class="bg-body-secondary">
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
            <div id="navbarSupportedContentHome" class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item" href="#">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <!-- <li class="nav-item" href="#">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link" href="admin-main.php">Admin Settings</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li> -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= $teamName ?></a>
                        <ul class="dropdown-menu px-3"> 
                            <li><a href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main id="changeTeamInfo" class="bg-white p-3 container">

        <h1 class="fs-3">Change Team name and Mascot</h1>

        <hr>

        <!-- Success / Error Alerts -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>




        <div class="mb-3">
            <a href="profile.php" class="btn btn-secondary">Return to Profile</a>
        </div>

        <div class="mb-3 p-3 border-top border-bottom bg-light">
            <h2 class="fs-5">Current Name and Mascot</h2>
            <div class="row">
                <div class="col-12 col-md-6">
                    <label for="currentTeamName" class="form-label">Current Team Abbrv:</label>
                    <input type="text" name="currentTeamName" class="form-control" value="<?= htmlspecialchars($teamName) ?>" disabled>
                </div>  
                <div class="col-12 col-md-6">
                    <label for="currentTeamMascot" class="form-label">Current Mascot:</label>
                    <input type="text" name="currentTeamMascot" class="form-control" value="<?= htmlspecialchars($teamMascot) ?>" disabled>
                </div>  
            </div>
            
            
            
        </div>
        
        <!-- Change Name & Mascot Form -->
        <div class="p-3">
            <h2 class="fs-5">Change Team Name and Mascot</h2>
            <form action="" method="POST">
                <div class="row">
                    <!-- New Team Abbreviation -->
                    <div class="col-12 col-md-6">
                        <label for="newTeamName" class="form-label">New Team Abbreviation (Max 5 Char):</label>
                        <input type="text" name="newTeamName" id="newTeamName" maxlength="5" style="text-transform: uppercase;" class="form-control">    
                    </div>
                    
                    <!-- New Pokémon Mascot -->
                    <div class="col-12 col-md-6 position-relative">
                        <label for="teamMascotInput" class="form-label">New Team Mascot:</label>
                        <input 
                            type="text" 
                            name="newTeamMascot" 
                            id="teamMascotInput"
                            list="pokemonDatalist"
                            maxlength="30" 
                            placeholder="Pikachu"
                            class="form-control"
                            autocomplete="off">

                        <!-- HTML5 Native Dropdown List -->
                        <datalist id="pokemonDatalist">
                            <?php foreach ($pokemonList as $pokemon): ?>
                                <option value="<?php echo htmlspecialchars($pokemon); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </main>

<script src="../javascript/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>