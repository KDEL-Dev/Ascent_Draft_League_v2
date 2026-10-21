<!-- navbar -->

    <!-- NavBar Toggle Icon -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"> 
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <!-- Nav bar -->
        <ul class="navbar-nav w-100 me-auto mb-2 mb-lg-0 d-flex justify-content-evenly">
            <!-- <li class="nav-item">
                <a class="nav-link" href="#">Overview</a>
            </li> -->
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>season/draft.php?seasonId=<?= (int)$seasonId ?>">Draft</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>season/roster.php?seasonId=<?= (int)$seasonId ?>">Roster</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>season/matchups.php?seasonId=<?= (int)$seasonId ?>">Matchups</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>season/standings.php?seasonId=<?= (int)$seasonId ?>">Standings</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>season/statistics.php?seasonId=<?= (int)$seasonId ?>">Statistics</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>season/pokebox.php?seasonId=<?= (int)$seasonId ?>">Pokebox</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>season/draft-recap.php?seasonId=<?= (int)$seasonId ?>">Draft Recap</a>
            </li>
            
            <?php if ($isOwner): ?>

                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>season/admin.php?seasonId=<?= (int)$seasonId ?>">Season Settings</a>
                </li>

            <?php endif ?>


            <!-- <li class="nav-item">
                <a class="nav-link" href="../index.php">Landing</a>
            </li> -->
        </ul>
    </div>
