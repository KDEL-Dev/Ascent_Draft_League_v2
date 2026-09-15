<?php
    include_once 'includes/connection.php'
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <title>Admin</title>
</head>
<body>
    <!-- header and navbar -->
    <?php include '../includes/header.php' ?>

    <main class="p-3">
        <h1>Admin Settings</h1>
        <div id="admin_settings_cont" class="d-flex">
            
            <div class="mx-3 p-3 border">
                <h2>Pre-Draft Settings</h2>
                <div class="d-flex justify-content-center">
                    <button id="resetDraft">Reset Draft</button>
                </div>
            </div>
            <div class="mx-3 p-3 border">
                <h2>Roster Settings</h2>
                <div class="d-flex justify-content-center">
                    <button>placeholder</button>
                </div>
            </div>
        </div>
    </main>

    <script src="../javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>