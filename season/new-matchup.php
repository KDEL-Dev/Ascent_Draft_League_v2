<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <title>New Matchup</title>
</head>
<body class="bg-body-secondary">

    <!-- For now just banner image -->
    <header>
        <?php include '../includes/banner.php' ?>
    </header>

    <!-- Navbar Sticky -->
    <nav class="px-3 sticky-top navbar navbar-expand-lg" data-bs-theme="dark">
        <?php include '../includes/nav.php' ?>
    </nav>

    <main class="container p-3">
        <div class="p-3 bg-white">
            <div class="my-3 pb-3 border-bottom">
                <a href="matchups.php" class="btn btn-primary">Return to Matchups</a>
            </div>
            <div class="row">
                
            </div>  
            <div id="newMatchAddStats" class="row">
                <p class="text-danger">*Record the Kill/Deaths from Match</p>
                <div class="col-12 col-lg-6 p-3">
                    <div class="table-responsive">
                        <table class="table table-dark table-striped">
                            <thead>
                                <tr>
                                    <th>Pokemon</th>
                                    <th>Kills</th>
                                    <th>Deaths</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Garchomp</td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                </tr>
                                <tr>
                                    <td>Muk</td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12 col-lg-6 p-3">
                    <div class="table-responsive">
                        <table class="table table-dark table-striped">
                            <thead>
                                <tr>
                                    <th>Pokemon</th>
                                    <th>Kills</th>
                                    <th>Deaths</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Scrafty</td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                </tr>
                                <tr>
                                    <td>Clefable</td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="6" step="1" value="0">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    </main>
    <script src="../javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>