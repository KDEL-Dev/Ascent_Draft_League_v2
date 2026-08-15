<?php
    // Error Handling
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Set File Type and Connect to connection
    header('Content-Type: application/json');
    require_once __DIR__ . '/../../includes/connection.php';

    // Get JSON file
    $jsonData = __DIR__.'/../../showdown/pokedex.json';
    // Convert JSON Data into Php strings
    $showdownData = json_decode
    (
        file_get_contents($jsonData),true
    );
    
    if($showdownData === null)
    {
        echo json_encode([
            'status' => 'error',
            'message' => 'Could not read or decode JSON'
        ]);
        exit;
    }

    // TEMP VARIABLES - Will make Dynamic in future
    $seasonId = 1;

    // This is needed to get the id of each pokemon which I believe will be inserted into showdown_pokemon_id
    $findPokemon = $conn->prepare("
        SELECT id
        FROM showdown_pokemon
        WHERE showdown_key = ?
    ");

    // Need to insert into all 3 of  these fields even though from the JSON you're only grabbing the tier.

    $insert_stmt = $conn->prepare("
        INSERT INTO pokemon_tier_per_season
        (
            showdown_pokemon_id,
            tier,
            season_id
        )
        VALUES(?,?,?)
        ON DUPLICATE KEY UPDATE
        tier = VALUES(tier)
    ");

    // Count how many imports get processed
    $processed = 0;
    $skipped = 0;

    foreach ($showdownData as $pkmnKey => $pkmn)
    {   
        // Loops through each Pokémon in the JSON.
        // $pkmnKey is the JSON key (e.g. "bulbasaur").
        // $pkmn contains that Pokémon's data.
        $showDownKey = strtolower($pkmnKey);

        // Gets tiers from JSON
        $tier = $pkmn['tier'] ?? null;

        // If no tier, set to null
        if ($tier === null)
        {
            $skipped++;
            continue;
        }

        // Find Pokemon ID from dB using its showdown key (name)
        $findPokemon->bind_param("s",$showDownKey);
        $findPokemon->execute();

        // Gets the results from SELECT query
        $result = $findPokemon->get_result();
        //  Converts results into PHP associative array
        $pokemon = $result->fetch_assoc();

        if (!$pokemon)
        {
            // Skip if pokmemon doesnt exist in dB
            $skipped++;
            continue;
        }

        // Get the internal dB ID of the pokemon and store it
        $showdownPokemonId = $pokemon["id"];

        // Insert the Pokemon's tier for this season.
        // If this Pokemon alkready has tier for this season,
        // ON DUPLICATE KEY UPDATE will update the existing tier.
        $insert_stmt->bind_param(
            "isi",
            $showdownPokemonId,
            $tier,
            $seasonId
        );

        if($insert_stmt->execute())
        {
            $processed++;
        }
    }

    // Close statements/connections
    $findPokemon->close();
    $insert_stmt->close();
    $conn->close();

    echo json_encode([
        'status' => 'success',
        'processed' => $processed,
        'skipped' => $skipped
    ]);
?>