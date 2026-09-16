
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

 <link rel="stylesheet" href="../css/styles.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
<title>Register</title>
</head>
<body>
<section id="registerLayout">
    <a href="login.php">
        <img id="registerLogo" src="img/Ascent Horizontal Text.svg" alt="site logo">
    </a>
    <form method="POST" action="register.php" id="registerForm">
        <div class="editTeamCol">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="editTeamCol">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="editTeamCol">
            <label>Confirm Password</label>
            <input type="password" name="password_confirm" required>
        </div>

        <div class="formFlex">
            <div class="editTeamCol">
                <label>Team Abbreviation (5 chars max)</label>
                <input 
                    type="text" 
                    name="team_name" 
                    maxlength="5" 
                    style="text-transform: uppercase;" 
                    placeholder="Jolt"
                    
                    required
                    id="teamNameInput">
            </div>
            <div class="editTeamCol">
                <label>Pokémon Mascot</label>
                <input 
                    type="text" 
                    name="team_mascot" 
                    maxlength="30" 
                    placeholder="Pikachu"
                    required>
            </div>
        </div>
        <div id="registerSubmitBtn">
            <button type="submit">Register</button>
        </div>    
    </form>
</section>
</body>
</html>