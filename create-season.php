<?php
    require_once __DIR__ . '/includes/auth.php';

    $successMessage = "";
    $errorMessage = "";

    if($_SERVER["REQUEST_METHOD"] === "POST")
    {
        // Grabbing the form information is the first step. Build this first before doing any validation.

        // Started off with getting season number normally but then to validate we need to convert number instead, so going to get number in a slightly different way.
        // $seasonNumber = $_POST["season_number"] ?? "";
        $seasonNumber = filter_var($_POST['season_number'] ?? "", FILTER_VALIDATE_INT); // This will get the number which starts as a string and converts to an interger.

        $seasonName = trim($_POST["season_name"]) ?? "";

        $seasonFormat = trim($_POST["season_format"]) ?? "";

        // Remember: This is how you can quickly test if form is submitting.
        // echo "<pre>";
        //     var_dump($seasonNumber, $seasonName, $seasonFormat);
        // echo "</pre>";


        if ($seasonNumber === false || $seasonNumber === null || $seasonNumber < 1) 
        {
            $errorMessage = "Enter a valid season number.";
        } 
        elseif (strlen($seasonName) > 100) 
        {
            $errorMessage = "Season name must be 100 characters or fewer.";
        } 
        elseif (strlen($seasonFormat) > 100) 
        {
            $errorMessage = "Format must be 100 characters or fewer.";
        } 
        else 
        {
            $seasonNameValue = $seasonName === "" ? null : $seasonName;
            $seasonFormatValue = $seasonFormat === "" ? null : $seasonFormat;

            try
            {
                // Start transaction
                $conn->begin_transaction();

                // 1. Create the season
                $sql = "
                    INSERT INTO seasons
                        (season_number, season_name, format)
                    VALUES (?, ?, ?)
                ";

                $stmt = $conn->prepare($sql);

                if (!$stmt) {
                    throw new RuntimeException("Failed to prepare season insert.");
                }

                $stmt->bind_param(
                    "iss",
                    $seasonNumber,
                    $seasonNameValue,
                    $seasonFormatValue
                );

                $stmt->execute();

                // Get the ID of the newly created season
                $seasonId = $conn->insert_id;

                $stmt->close();


                // 2. Create its initial draft state
                $sql = "INSERT INTO 
                        draft_state (
                        season_id,
                        draft_position,
                        current_round,
                        total_picks,
                        is_active,
                        status,
                        timer_remaining
                    )
                        VALUES (?, 1, 1, 0, 0, 'pending', 60)
                ";

                $stmt = $conn->prepare($sql);

                if (!$stmt) {
                    throw new RuntimeException(
                        "Failed to prepare draft state insert."
                    );
                }

                $stmt->bind_param("i", $seasonId);
                $stmt->execute();
                $stmt->close();


                // 3. Save both records
                $conn->commit();

                // Redirect after successful creation
                header("Location: index.php");
                exit;
            }
            catch (Throwable $e)
            {
                // Undo both inserts if anything failed
                $conn->rollback();

                if ($e instanceof mysqli_sql_exception && $e->getCode() === 1062)
                {
                    $errorMessage = "That season number already exists.";
                }
                else
                {
                    error_log($e->getMessage());
                    $errorMessage = "Something went wrong while creating the season.";
                }
            }
        }
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

    <title>Create New Season</title>
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
                        <a class="nav-link" href="#">Profile</a>
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
    <main id="createNewSeasonPage" class="container p-3 bg-white rounded-2 d-flex flex-column align-items-center">
        
        <h2 class="fs-5">Create New Season</h2>
        <div class="">
            <a href="index.php" class="btn btn-secondary">Back</a>
        </div>    
    

        <?php if ($successMessage !== ""): ?>
            <div class="alert alert-success" role="alert">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ""): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST"> <!-- Define the method and also Action being empty means it submits to current page -->
            <div class="mb-3">
                <label for="season_number">Season Number</label> <!-- for needs to be the same as what I put down for input name and id -->
                <input id="season_number" type="number" name="season_number" min="1" class="form-control" required> <!--Reminder: Name is what -->
            </div>
            <div class="mb-3">
                <label for="season_name">Season Name</label>
                <input id="season_name" type="text" name="season_name" max="100" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="season_format">Format</label>
                <input id="season_format" type="text" name="season_format" max="100" class="form-control" required>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </main>
    <script src="javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>