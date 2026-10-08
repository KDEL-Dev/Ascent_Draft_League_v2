    <?php
    session_start();

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit;
    }

    require_once __DIR__ . '/../includes/connection.php';

    $userId = $_SESSION['user_id'];
    $seasonId = 1;

    // --------------------------------------------------
    // GET ACTIVE USER
    // --------------------------------------------------

    $activeUserSql = "
        SELECT id, transactions_left, team_name
        FROM active_users
        WHERE user_id = ?
        AND season_id = ?
    ";

    $stmt = $conn->prepare($activeUserSql);
    $stmt->bind_param("ii", $userId, $seasonId);
    $stmt->execute();

    $activeUserResult = $stmt->get_result();
    $activeUser = $activeUserResult->fetch_assoc();

    if (!$activeUser) {
        die("Active user not found.");
    }

    $activeUserId = (int)$activeUser['id'];
    $transactionsLeft = (int)$activeUser['transactions_left'];


    // --------------------------------------------------
    // GET SELECTED POKEMON
    // --------------------------------------------------

    $addPokemonId = filter_input(INPUT_GET, 'add', FILTER_VALIDATE_INT);

    if (!$addPokemonId) {
        header("Location: pokebox.php");
        exit;
    }

    $pokemonSql = "
        SELECT
            showdown_pokemon.id,
            showdown_pokemon.name,
            pokemon_tier_per_season.tier
        FROM showdown_pokemon

        JOIN pokemon_tier_per_season
            ON pokemon_tier_per_season.showdown_pokemon_id =
            showdown_pokemon.id

        WHERE showdown_pokemon.id = ?
        AND pokemon_tier_per_season.season_id = ?
    ";

    $stmt = $conn->prepare($pokemonSql);
    $stmt->bind_param("ii", $addPokemonId, $seasonId);
    $stmt->execute();

    $pokemonResult = $stmt->get_result();
    $selectedPokemon = $pokemonResult->fetch_assoc();

    if (!$selectedPokemon) {
        die("Pokémon not found.");
    }


    // --------------------------------------------------
    // TIER GROUP FUNCTION
    // --------------------------------------------------

    function getTierGroup($tier)
    {
        $groups = [
            'OU' => 'OU',
            'UUBL' => 'OU',

            'UU' => 'UU',
            'RUBL' => 'UU',

            'RU' => 'RU',
            'NUBL' => 'RU',

            'NU' => 'NU',
            'PUBL' => 'NU',
            'PU' => 'NU',
            'ZUBL' => 'NU',
            'ZU' => 'NU'
        ];

        return $groups[$tier] ?? null;
    }

    $selectedTierGroup = getTierGroup($selectedPokemon['tier']);


    // --------------------------------------------------
    // GET USER ROSTER
    // --------------------------------------------------

    $rosterSql = "
        SELECT
            roster_pkmn.id AS roster_pkmn_id,
            showdown_pokemon.id AS pokemon_id,
            showdown_pokemon.name,
            pokemon_tier_per_season.tier

        FROM roster_pkmn

        JOIN showdown_pokemon
            ON showdown_pokemon.id =
            roster_pkmn.showdown_pokemon_id

        JOIN pokemon_tier_per_season
            ON pokemon_tier_per_season.showdown_pokemon_id =
            showdown_pokemon.id

        WHERE roster_pkmn.active_user_id = ?
        AND roster_pkmn.season_id = ?
        AND roster_pkmn.status = 'active'

        ORDER BY showdown_pokemon.name
    ";

    $stmt = $conn->prepare($rosterSql);
    $stmt->bind_param("ii", $activeUserId, $seasonId);
    $stmt->execute();

    $rosterResult = $stmt->get_result();

    $userRoster = [];

    while ($pokemon = $rosterResult->fetch_assoc()) {

        $group = getTierGroup($pokemon['tier']);

        if ($group === $selectedTierGroup) {
            $userRoster[] = $pokemon;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <title>Swap Pkmn - Ascent</title>
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

    <main class="p-3 container">
        <div class="row p-3 bg-white">
            <h1>Swap Pokemon</h1>

            <!-- Transaction Counter -->
            <div class="alert alert-secondary">
                 Transactions remaining: <strong><?= $transactionsLeft ?></strong>
            </div>

            <!-- Pokemon from Pokebox -->
             <div>
                <p class="mb-0">Selected Pokemon:</p>
                    <select class="form-select" disabled>
                        <option value="<?= $selectedPokemon['id'] ?>">
                            <?= htmlspecialchars($selectedPokemon['name']) ?>
                        </option>
                    </select>
             </div>
             <!-- Your Pokemon Matching Tier of selected pokemon above -->
             <div class="mt-3">
                <p class="mb-0">Your <span>placeholder</span> Pokemon</p>
                <select id="dropPokemon" class="form-select">
                    <option value="">-- Select Pokémon to replace --</option>

                    <?php foreach ($userRoster as $pokemon): ?>
                        <option value="<?= $pokemon['roster_pkmn_id'] ?>">
                            <?= htmlspecialchars($pokemon['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
             </div>
             <!-- Confirm -->
              <div class="mt-3">
                <button
                    type="button"
                    id="confirmSwapBtn"
                    class="btn btn-primary">
                    Confirm Swap
                </button>

                <a
                    href="pokebox.php"
                    class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </div>
    </main>

    <script src="../javascript/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>