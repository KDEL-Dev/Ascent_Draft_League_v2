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
        <button 
            type="button" data-bs-toggle = "collapse" data-bs-target="#navbarSupportedContentHome" 
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation" 
            class="navbar-toggler"
        >
            <span class="navbar-toggler-icon"></span>
        </button>
        <div id="navbarSupportedContentHome" class="collapse navbar-collapse">
            <ul class="navbar-nav w-100 me-auto mb-2 mb-lg-0 d-flex justify-content-center" >
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
                    <a class="nav-link" href="#">Admin Settings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
   </nav>
    <main class="container p-3 bg-white rounded-2">
        <h2 class="fs-5">Create New Season</h2>
        <form action="">
            <div class="mb-3">
                <label for="">Season Number</label>
                <input type="text" class="form-control">
            </div>
            <div class="mb-3">
                <label for="">Season Name</label>
                <input type="text" class="form-control">
            </div>
            <div class="mb-3">
                <label for="">Format</label>
                <input type="text" class="form-control">
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