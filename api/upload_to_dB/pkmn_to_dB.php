<?php

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/connection.php';


// --------------------------------------------------
// Load Showdown Pokémon data
// --------------------------------------------------

$jsonFile = __DIR__ . '/../../showdown/pokedex.json';

$showdownData = json_decode(
    file_get_contents($jsonFile),
    true
);

if ($showdownData === null) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Could not read or decode pokedex.json'
    ]);

    exit;
}


// --------------------------------------------------
// Prepare database statement
// --------------------------------------------------

$stmt = $conn->prepare("
    INSERT INTO showdown_pokemon
        (
            showdown_key,
            showdown_id,
            name,
            form,
            type1,
            type2,
            created_at
        )
    VALUES
        (?, ?, ?, ?, ?, ?, NOW())

    ON DUPLICATE KEY UPDATE
        showdown_id = VALUES(showdown_id),
        name = VALUES(name),
        form = VALUES(form),
        type1 = VALUES(type1),
        type2 = VALUES(type2),
        updated_at = NOW()
");

if (!$stmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare database statement',
        'error' => $conn->error
    ]);

    exit;
}


// --------------------------------------------------
// Counters
// --------------------------------------------------

$processed = 0;
$skipped = 0;


// --------------------------------------------------
// Process every Pokémon
// --------------------------------------------------

foreach ($showdownData as $pkmnKey => $pkmn) {

    // ----------------------------------------------
    // Skip nonstandard Pokémon
    // ----------------------------------------------

    if (
        isset($pkmn['isNonstandard']) &&
        $pkmn['isNonstandard'] !== false
    ) {
        $skipped++;
        continue;
    }


    // ----------------------------------------------
    // Skip cosmetic forms
    // ----------------------------------------------

    if (
        isset($pkmn['isCosmeticForme']) &&
        $pkmn['isCosmeticForme'] === true
    ) {
        $skipped++;
        continue;
    }


    // ----------------------------------------------
    // Get Pokémon ID
    // ----------------------------------------------

    if (isset($pkmn['num'])) {

        $showdownId = $pkmn['num'];

    } elseif (
        isset($pkmn['baseSpecies']) &&
        isset(
            $showdownData[strtolower($pkmn['baseSpecies'])]['num']
        )
    ) {

        $showdownId =
            $showdownData[strtolower($pkmn['baseSpecies'])]['num'];

    } else {

        error_log(
            "Skipping {$pkmnKey}: no Showdown ID available"
        );

        $skipped++;
        continue;
    }


    // ----------------------------------------------
    // Get Pokémon data
    // ----------------------------------------------

    // The JSON key is Showdown's unique identifier.
    $showdownKey = strtolower($pkmnKey);

    $name = $pkmn['name'] ?? 'Unknown';

    $form = $pkmn['forme']
        ?? ($pkmn['baseForme'] ?? 'Base');

    $type1 = $pkmn['types'][0] ?? null;

    $type2 = $pkmn['types'][1] ?? null;


    // ----------------------------------------------
    // Insert / update Pokémon
    // ----------------------------------------------

    $stmt->bind_param(
        "sissss",
        $showdownKey,
        $showdownId,
        $name,
        $form,
        $type1,
        $type2
    );


    if (!$stmt->execute()) {

        error_log(
            "Failed to import {$showdownKey}: {$stmt->error}"
        );

        $skipped++;
        continue;
    }


    $processed++;
}


// --------------------------------------------------
// Clean up
// --------------------------------------------------

$stmt->close();

$conn->close();


// --------------------------------------------------
// Return result
// --------------------------------------------------

echo json_encode([
    'status' => 'success',
    'processed' => $processed,
    'skipped' => $skipped
]);