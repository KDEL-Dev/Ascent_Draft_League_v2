<?php
session_start();
require_once __DIR__ . '/includes/connection.php';

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| Fetch Pokémon list from PokéAPI
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{

    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $teamName        = strtoupper(trim($_POST['team_name'] ?? ''));
    $teamMascot      = trim($_POST['team_mascot'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        empty($email) ||
        empty($password) ||
        empty($passwordConfirm) ||
        empty($teamName) ||
        empty($teamMascot)
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif ($password !== $passwordConfirm) {

        $error = "Passwords do not match.";

    } elseif (strlen($teamName) > 5) {

        $error = "Team abbreviation must be 5 characters or fewer.";

    } elseif (!in_array($teamMascot, $pokemonList, true)) {

        $error = "Please select a valid Pokémon from the list.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Hash Password
        |--------------------------------------------------------------------------
        */

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        /*
        |--------------------------------------------------------------------------
        | Insert User
        |--------------------------------------------------------------------------
        */

        $sql = "
            INSERT INTO users (
                email,
                password_hash,
                default_team_name,
                default_team_mascot
            )
            VALUES (?, ?, ?, ?)
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $email,
                $hashedPassword,
                $teamName,
                $teamMascot
            );

            if (mysqli_stmt_execute($stmt)) 
            {

                mysqli_stmt_close($stmt);

                header("Location: login.php?registered=success");
                exit;

            } else {

                if (mysqli_errno($conn) === 1062) {
                    $error = "An account with that email already exists.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }

            mysqli_stmt_close($stmt);

        } else {

            $error = "Database query error.";
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
    
<title>Register</title>
</head>
<body class="bg-black min-vh-100 d-flex flex-column justify-content-center align-items-center">
<main id="registerCont" class="container text-center">
    <a href="login.php">
        <img id="registerLogo" src="img/Ascent Horizontal Text.svg" alt="site logo" class="img-fluid mb-4">
    </a>
    <form method="POST" action="register.php" id="registerForm" class="my-3 d-flex flex-column gap-2">
        <div class="text-start form-floating">
            <input type="email" name="email" class="form-control" placeholder="email" required>
            <label>Email</label>
        </div>
        <div class="text-start form-floating">
            <input type="password" name="password" class="form-control" placeholder="password" required>
            <label>Password</label>
        </div>
        <div class="text-start form-floating">
            <input type="password" name="password_confirm" class="form-control" placeholder="password" required>
            <label>Confirm Password</label>
        </div>
        <div>
            <div class="text-start form-floating">
                <input 
                    type="text" 
                    name="team_name" 
                    maxlength="5" 
                    style="text-transform: uppercase;" 
                    placeholder="Jolt"
                    class="form-control"
                    required
                    id="teamNameInput">
                <label>Team Abbreviation (ex: JOLT - 5 char limit)</label>
            </div>

            <!-- Pokémon Mascot Input with Datalist -->
            <div class="text-start form-floating my-3 position-relative">
                <input 
                    type="text" 
                    name="team_mascot" 
                    id="teamMascotInput"
                    list="pokemonDatalist"
                    maxlength="30" 
                    placeholder="Pikachu"
                    class="form-control"
                    autocomplete="off"
                    required>
                <label>Pokémon Mascot</label>
                
                <!-- HTML5 Native Dropdown List -->
                <datalist id="pokemonDatalist">
                    <?php foreach ($pokemonList as $pokemon): ?>
                        <option value="<?php echo htmlspecialchars($pokemon); ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <!-- Feedback error message -->
                <div id="mascotError" class="invalid-feedback text-start">
                    Please select a valid Pokémon from the list.
                </div>
            </div>
        </div>
        <div id="registerSubmitBtn">
            <button type="submit" class="btn btn-primary">Register</button>
        </div>    
    </form>
</main>
<!-- Pass array from PHP to window object for external JS file access -->
<script>
    window.validPokemon = <?php echo json_encode($pokemonList); ?>;
</script>
<script src="javascript/script.js"></script>
</body>
</html>