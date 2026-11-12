<div class="container-fluid position-relative">
    <!-- Logo -->
    <a href="<?= BASE_URL ?>index.php" class="navbar-brand">
        <img
            src="<?= BASE_URL ?>img/Ascent_Horizontal_Text_GRAD_2.png"
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
                <a class="nav-link" href="<?= BASE_URL ?>index.php">Home</a>
            </li>
            <li class="nav-item" href="#">
                <a class="nav-link" href="<?= BASE_URL ?>about.php">About</a>
            </li>
            <!-- <li class="nav-item" href="#">
                <a class="nav-link" href="profile.php">Profile</a>
            </li> -->
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>admin.php">Admin Settings</a>
            </li>
            <!-- <li class="nav-item">
                <a class="nav-link" href="logout.php">Logout</a>
            </li> -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= $teamName ?></a>
                <ul class="dropdown-menu px-3"> 
                    <li><a href="<?= BASE_URL ?>profile.php">Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</div>