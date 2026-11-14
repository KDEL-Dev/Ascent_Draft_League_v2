<?php
    require_once  __DIR__ . '/../includes/auth.php';

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

    // ----------------
    // GRAB ALL MEMBERS
    // ----------------
    
    $sql ="SELECT u.default_team_name, u.default_team_mascot, u.email, u.role
           FROM users u 
    ";


    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $stmtResults = $stmt->get_result();
    
    $members = [];

    while($row = $stmtResults->fetch_assoc())
    {
        $members[] = [
            'email' => $row['email'],
            'default_team_name' => $row['default_team_name'],
            'default_team_mascot' => $row['default_team_mascot'],
            'role' => $row['role']
        ];
    }

    $stmt->close()



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/4a4034fc29.js" crossorigin="anonymous"></script>

    <title>Season List - Admin</title>
</head>
<body class="bg-body-secondary">
    <nav class="px-3 mb-3 sticky-top navbar navbar-expand-lg bg-dark" data-bs-theme="dark">
        <?php include '../includes/nav-main.php' ?>
    </nav>
    <main id="memberListPage" class="p-3 bg-white container">

        <div class="w-100 justify-self-start">
            
            <!-- <a href="<?= BASE_URL ?>index.php" class="btn btn-secondary">Back</a> -->

             <a href="<?= BASE_URL ?>admin.php" class="d-flex align-items-center">
                
                <i class="backBtn fa-solid fa-arrow-left fa-2x"></i>

             </a>
             
        </div> 


        <h1 class="fs-3 text-center">Member List</h1>
        <hr>

        <div class="table-responsive">
        
            <table id="memberListTableAdmin" class="table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Team Name</th>
                        <th>Mascot</th>
                        <th>Role</th>
                        <th>Reset Password</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td><?= htmlspecialchars($member['email']) ?></td>
                            <td><?= htmlspecialchars($member['default_team_name']) ?></td>
                            <td><?= htmlspecialchars($member['default_team_mascot']) ?></td>
                            <td><?= htmlspecialchars($member['role']) ?></td>

                            <!-- Reset Password -->
                            <td>
                                <form method="POST" action="../api/admin_settings/reset_password-admin.php"
                                    onsubmit="return confirm('Are you sure you want to reset this member\'s password?');">

                                    <input type="hidden" name="email"
                                        value="<?= htmlspecialchars($member['email']) ?>">

                                    <button type="submit" class="btn btn-sm btn-primary">
                                        Reset
                                    </button>

                                </form>
                            </td>

                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

    </main>
<script src="javascript/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>