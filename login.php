<?php
    session_start();

    require_once __DIR__ . '/includes/connection.php';

    $login_error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') 
    {

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {

            $login_error = "Please enter your email and password.";

        } else {

            // We'll query the database here.

            $sql = "
            SELECT id, email, password_hash
            FROM users
            WHERE email = ?
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) 
        {

            $login_error = "Database query error.";

        } 
        else 
        {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            // We'll verify the password here.
            if ($user && password_verify($password, $user['password_hash'])) 
            {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];

                header("Location: index.php");
                exit;
            } 
            else 
            {
                $login_error = "Invalid email or password.";
            }


            $stmt->close();
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

    <title>Ascent - Login</title>
</head>
<body class="bg-black min-vh-100 d-flex flex-column justify-content-center align-items-center">

    <main class="container text-center" id="loginCont"> <!-- Place this in my css -->
        <img id="loginLogo" src="img/Ascent Horizontal Text.svg" alt="site logo" class="img-fluid mb-4">
        
        <form id="loginForm" method="post" action="login.php" class="my-3 d-flex flex-column gap-2">
            <div class="text-start form-floating">
                <input type="email" id="email" class="form-control" name="email" placeholder="email" required>
                <label for="email" class="form-label">Email: </label>
            </div>
            
            <div class="text-start form-floating">
                <input type="password" name="password" class="form-control" id="password" placeholder="password" required>
                <label for="password" class="form-label" >Password: </label>
            </div>

            <input type="submit" class="loginPageBtn btn btn-primary mt-3" value="Login">
        </form>

        <?php if (!empty($login_error)) : ?>
            <p style="color:red;"><?php echo $login_error; ?></p>
        <?php endif; ?>

        <div>
       
            <a href="register.php" class="loginPageBtn btn btn-secondary mt-2 w-100">Register</a>
        </div>
        
    </main>

    <!-- <?php include 'includes/footer.php'; ?> -->
</body>
</html>