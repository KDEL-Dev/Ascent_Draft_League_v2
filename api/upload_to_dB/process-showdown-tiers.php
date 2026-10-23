
<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/auth.php';

// ------------------------------------
// 1. Check request and admin access
// ------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]));
}

if (!$isAdmin) {
    http_response_code(403);
    exit(json_encode([
        'status' => 'error',
        'message' => 'Access denied.'
    ]));
}

// ------------------------------------
// 2. Get and validate season ID
// ------------------------------------

$seasonId = filter_input(
    INPUT_GET,
    'seasonId',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($seasonId === false || $seasonId === null) {
    http_response_code(400);
    exit(json_encode([
        'status' => 'error',
        'message' => 'Invalid season ID.'
    ]));
}

// Check that the season exists
$seasonStmt = $conn->prepare(
    "SELECT id FROM seasons WHERE id = ? LIMIT 1"
);

$seasonStmt->bind_param("i", $seasonId);
$seasonStmt->execute();

$seasonResult = $seasonStmt->get_result();
$season = $seasonResult->fetch_assoc();

$seasonStmt->close();

if (!$season) {
    http_response_code(404);
    exit(json_encode([
        'status' => 'error',
        'message' => 'Season not found.'
    ]));
}

// ------------------------------------
// 3. Validate uploaded file
// ------------------------------------

if (
    !isset($_FILES['uploadNewTiers']) ||
    $_FILES['uploadNewTiers']['error'] !== UPLOAD_ERR_OK
) {
    http_response_code(400);
    exit(json_encode([
        'status' => 'error',
        'message' => 'Please upload a valid JSON file.'
    ]));
}

$upload = $_FILES['uploadNewTiers'];

if (
    !is_uploaded_file($upload['tmp_name']) ||
    $upload['size'] <= 0 ||
    $upload['size'] > 20 * 1024 * 1024
) {
    http_response_code(400);
    exit(json_encode([
        'status' => 'error',
        'message' => 'Invalid file or file exceeds 20 MB.'
    ]));
}

// Get the original filename
$filename = basename(
    str_replace('\\', '/', $upload['name'])
);

// Only allow simple JSON filenames
if (!preg_match('/\A[A-Za-z0-9][A-Za-z0-9._ -]*\.json\z/i', $filename)) {
    http_response_code(400);
    exit(json_encode([
        'status' => 'error',
        'message' => 'Please use a valid .json filename.'
    ]));
}

// ------------------------------------
// 4. Read and validate JSON
// ------------------------------------

$jsonContents = file_get_contents($upload['tmp_name']);

try {
    $showdownData = json_decode(
        $jsonContents,
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException $e) {
    http_response_code(400);
    exit(json_encode([
        'status' => 'error',
        'message' => 'The uploaded file contains invalid JSON.'
    ]));
}

if (!is_array($showdownData) || count($showdownData) === 0) {
    http_response_code(400);
    exit(json_encode([
        'status' => 'error',
        'message' => 'The JSON file is empty or has an invalid structure.'
    ]));
}

// ------------------------------------
// 5. Save JSON in showdown folder
// ------------------------------------

$showdownFolder = __DIR__ . '/../../showdown';

if (!is_dir($showdownFolder) || !is_writable($showdownFolder)) {
    http_response_code(500);
    exit(json_encode([
        'status' => 'error',
        'message' => 'The showdown folder is missing or not writable.'
    ]));
}

$destination = $showdownFolder . DIRECTORY_SEPARATOR . $filename;

// Reject existing filenames
// if (file_exists($destination)) {
//     http_response_code(409);
//     exit(json_encode([
//         'status' => 'error',
//         'message' => 'A file with this name already exists.'
//     ]));
// }

// Save the file
if (!move_uploaded_file($upload['tmp_name'], $destination)) {
    http_response_code(500);
    exit(json_encode([
        'status' => 'error',
        'message' => 'Could not save the uploaded file.'
    ]));
}

// ------------------------------------
// 6. Prepare database statements
// ------------------------------------

$findPokemon = $conn->prepare("
    SELECT id
    FROM showdown_pokemon
    WHERE showdown_key = ?
");

$insert_stmt = $conn->prepare("
    INSERT INTO pokemon_tier_per_season
    (
        showdown_pokemon_id,
        tier,
        season_id
    )
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE
        tier = VALUES(tier)
");

// ------------------------------------
// 7. Import Pokémon tiers
// ------------------------------------

$processed = 0;
$skipped = 0;

try {
    $conn->begin_transaction();

    foreach ($showdownData as $pkmnKey => $pkmn) {

        // Skip invalid entries
        if (!is_string($pkmnKey) || !is_array($pkmn)) {
            $skipped++;
            continue;
        }

        // Get tier from JSON
        $tier = $pkmn['tier'] ?? null;

        if (
            !is_string($tier) ||
            trim($tier) === '' ||
            strlen(trim($tier)) > 25
        ) {
            $skipped++;
            continue;
        }

        $tier = trim($tier);
        $showDownKey = strtolower($pkmnKey);

        // Find Pokémon ID
        $findPokemon->bind_param("s", $showDownKey);
        $findPokemon->execute();

        $result = $findPokemon->get_result();
        $pokemon = $result->fetch_assoc();

        if (!$pokemon) {
            $skipped++;
            continue;
        }

        $showdownPokemonId = (int)$pokemon['id'];

        // Insert or update tier for this season
        $insert_stmt->bind_param(
            "isi",
            $showdownPokemonId,
            $tier,
            $seasonId
        );

        $insert_stmt->execute();
        $processed++;
    }

    $conn->commit();

} catch (Throwable $e) {

    $conn->rollback();

    error_log('Showdown import error: ' . $e->getMessage());

    $findPokemon->close();
    $insert_stmt->close();
    $conn->close();

    http_response_code(500);

    exit(json_encode([
        'status' => 'error',
        'message' => 'Database import failed. The database changes were rolled back, but the JSON file remains saved.',
        'filename' => $filename
    ]));
}

// ------------------------------------
// 8. Close database resources
// ------------------------------------

$findPokemon->close();
$insert_stmt->close();
$conn->close();

// ------------------------------------
// 9. Return results
// ------------------------------------

// echo json_encode([
//     'status' => 'success',
//     'season_id' => $seasonId,
//     'filename' => $filename,
//     'processed' => $processed,
//     'skipped' => $skipped
// ]);

header(
    'Location: ../../season/settings/load-showdown-tiers.php?seasonId='
    . (int)$seasonId
    . '&import=success'
);
exit;

?>
