<?php

    // Im able to do this because im grabbing info from the parent

    $sql ="SELECT seasons.season_number, seasons.season_name
           FROM seasons
           WHERE seasons.id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $seasonId);
    $stmt->execute();

    $result = $stmt->get_result();
    $seasonTitles = $result->fetch_assoc();

?>

<div class="seasonBanner position-relative overflow-hidden d-flex justify-content-center align-items-center position-relative">
   
<!-- Future Website Banner -->

    <div class="bannerCircle position-absolute">
        
    </div>

    <div class="py-4 d-flex justify-content-center flex-column align-items-center">
        <h2 id="bannerSeasonNum">SEASON <?= htmlspecialchars($seasonTitles['season_number']) ?></h2>
        <h2 id="bannerSeasonName"><?= htmlspecialchars($seasonTitles['season_name']) ?></h2>
    </div>
    
    <!-- <img src="<?= BASE_URL ?>img/tempBanner2.png" alt="temp banner" class="img-fluid h-100"> -->

    <a href="<?= BASE_URL ?>index.php" class="position-absolute top-0 start-0 m-2 text-white">
        <i class="fa-solid fa-house"></i>
        Main Menu
    </a>

    <a href="<?= BASE_URL ?>logout.php" class="position-absolute top-0 end-0 m-2 text-white">
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout
    </a>

</div>
