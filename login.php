<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <title>Ascent - Login</title>
</head>
<body>
    <section id="loginLayout">
        <img id="loginLogo" src="img/Ascent Horizontal Text.svg" alt="site logo">
        <form id="loginForm" method="post" action="login.php">
            <label for="email">Email: </label>
            <input type="email" id="email" class="formInput" name="email" required>
            <label for="password">Password: </label>
            <input type="password" name="password" class="formInput" id="password" required>
            <input type="submit" class="loginPageBtn" value="Login">
        </form>
        <?php if (!empty($login_error)) : ?>
            <p style="color:red;"><?php echo $login_error; ?></p>
        <?php endif; ?>
        <button class="loginPageBtn"><a href="register.php">Register</a></button>
        <!-- <button>Forgot password</button> -->
    </section>

    <!-- <?php include 'includes/footer.php'; ?> -->
</body>
</html>