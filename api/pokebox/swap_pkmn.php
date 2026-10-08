<?php

    session_start();

    header('Content-Type: application/json');

    require_once __DIR__ . '/../../includes/connection.php';


    // --------------------------------------------------
    // REQUIRE LOGIN
    // --------------------------------------------------

    if (!isset($_SESSION['user_id'])) {

        http_response_code(401);

        echo json_encode([
            'status' => 'error',
            'error' => 'You must be logged in.'
        ]);

        exit;
    }


    $userId = (int) $_SESSION['user_id'];
    $seasonId = 1;


    // --------------------------------------------------
    // GET JSON REQUEST
    // --------------------------------------------------

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($input)) {

        http_response_code(400);

        echo json_encode([
            'status' => 'error',
            'error' => 'Invalid request.'
        ]);

        exit;
    }


    // --------------------------------------------------
    // GET ADD / DROP IDS
    // --------------------------------------------------

    $addPokemonId = filter_var(
        $input['add'] ?? null,
        FILTER_VALIDATE_INT
    );

    $dropRosterId = filter_var(
        $input['drop'] ?? null,
        FILTER_VALIDATE_INT
    );


    if (!$addPokemonId || !$dropRosterId) {

        http_response_code(400);

        echo json_encode([
            'status' => 'error',
            'error' => 'Missing Pokémon information.'
        ]);

        exit;
    }


    // --------------------------------------------------
    // GET ACTIVE USER
    // --------------------------------------------------

    $activeUserSql = "
        SELECT
            id,
            transactions_left
        FROM active_users
        WHERE user_id = ?
        AND season_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($activeUserSql);
    $stmt->bind_param(
        "ii",
        $userId,
        $seasonId
    );

    $stmt->execute();

    $activeUserResult = $stmt->get_result();

    $activeUser = $activeUserResult->fetch_assoc();


    if (!$activeUser) {

        http_response_code(404);

        echo json_encode([
            'status' => 'error',
            'error' => 'Active user not found.'
        ]);

        exit;
    }


    $activeUserId = (int) $activeUser['id'];
    $transactionsLeft = (int) $activeUser['transactions_left'];


    // --------------------------------------------------
    // CHECK TRANSACTIONS REMAINING
    // --------------------------------------------------

    if ($transactionsLeft <= 0) {

        http_response_code(400);

        echo json_encode([
            'status' => 'error',
            'error' => 'You have no transactions remaining.'
        ]);

        exit;
    }


    // --------------------------------------------------
    // GET POKEMON BEING ADDED
    // --------------------------------------------------

    $addPokemonSql = "
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

        LIMIT 1
    ";

    $stmt = $conn->prepare($addPokemonSql);

    $stmt->bind_param(
        "ii",
        $addPokemonId,
        $seasonId
    );

    $stmt->execute();

    $addPokemonResult = $stmt->get_result();

    $addPokemon = $addPokemonResult->fetch_assoc();


    if (!$addPokemon) {

        http_response_code(400);

        echo json_encode([
            'status' => 'error',
            'error' => 'The Pokémon being added is not valid for this season.'
        ]);

        exit;
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


    $addTierGroup = getTierGroup(
        $addPokemon['tier']
    );


    if ($addTierGroup === null) {

        http_response_code(400);

        echo json_encode([
            'status' => 'error',
            'error' => 'Invalid Pokémon tier.'
        ]);

        exit;
    }


    // --------------------------------------------------
    // GET POKEMON BEING DROPPED
    // --------------------------------------------------

    $dropPokemonSql = "
        SELECT
            roster_pkmn.id AS roster_pkmn_id,
            roster_pkmn.showdown_pokemon_id,
            roster_pkmn.season_id,
            showdown_pokemon.name,
            pokemon_tier_per_season.tier

        FROM roster_pkmn

        JOIN showdown_pokemon
            ON showdown_pokemon.id =
            roster_pkmn.showdown_pokemon_id

        JOIN pokemon_tier_per_season
            ON pokemon_tier_per_season.showdown_pokemon_id =
            showdown_pokemon.id

        WHERE roster_pkmn.id = ?
        AND roster_pkmn.active_user_id = ?
        AND roster_pkmn.season_id = ?
        AND roster_pkmn.status = 'active'

        LIMIT 1
    ";

    $stmt = $conn->prepare($dropPokemonSql);

    $stmt->bind_param(
        "iii",
        $dropRosterId,
        $activeUserId,
        $seasonId
    );

    $stmt->execute();

    $dropPokemonResult = $stmt->get_result();

    $dropPokemon = $dropPokemonResult->fetch_assoc();


    if (!$dropPokemon) {

        http_response_code(400);

        echo json_encode([
            'status' => 'error',
            'error' => 'The Pokémon you selected to drop is not on your active roster.'
        ]);

        exit;
    }


    // --------------------------------------------------
    // CHECK TIER GROUPS MATCH
    // --------------------------------------------------

    $dropTierGroup = getTierGroup(
        $dropPokemon['tier']
    );


    if ($dropTierGroup !== $addTierGroup) {

        http_response_code(400);

        echo json_encode([
            'status' => 'error',
            'error' => 'You can only swap for a Pokémon from the same tier group.'
        ]);

        exit;
    }


    // --------------------------------------------------
    // MAKE SURE ADD POKEMON IS NOT ALREADY OWNED
    // --------------------------------------------------

    $ownedSql = "
        SELECT id
        FROM roster_pkmn
        WHERE active_user_id = ?
        AND season_id = ?
        AND showdown_pokemon_id = ?
        AND status = 'active'
        LIMIT 1
    ";

    $stmt = $conn->prepare($ownedSql);

    $stmt->bind_param(
        "iii",
        $activeUserId,
        $seasonId,
        $addPokemonId
    );

    $stmt->execute();

    $ownedResult = $stmt->get_result();


    if ($ownedResult->fetch_assoc()) {

        http_response_code(400);

        echo json_encode([
            'status' => 'error',
            'error' => 'You already have that Pokémon on your roster.'
        ]);

        exit;
    }


    // --------------------------------------------------
    // START DATABASE TRANSACTION
    // --------------------------------------------------

    $conn->begin_transaction();


    try {

        // --------------------------------------------------
        // DROP OLD POKEMON
        // --------------------------------------------------

        $dropSql = "
            UPDATE roster_pkmn
            SET
                status = 'dropped',
                dropped_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND active_user_id = ?
            AND season_id = ?
            AND status = 'active'
        ";

        $stmt = $conn->prepare($dropSql);

        $stmt->bind_param(
            "iii",
            $dropRosterId,
            $activeUserId,
            $seasonId
        );

        $stmt->execute();


        if ($stmt->affected_rows !== 1) {

            throw new Exception(
                'Failed to drop the selected Pokémon.'
            );
        }


        // --------------------------------------------------
        // ADD NEW POKEMON
        // --------------------------------------------------

        $addSql = "
            INSERT INTO roster_pkmn
            (
                season_id,
                active_user_id,
                showdown_pokemon_id,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                'active'
            )
        ";

        $stmt = $conn->prepare($addSql);

        $stmt->bind_param(
            "iii",
            $seasonId,
            $activeUserId,
            $addPokemonId
        );

        $stmt->execute();


        if ($stmt->affected_rows !== 1) {

            throw new Exception(
                'Failed to add the new Pokémon.'
            );
        }


        // --------------------------------------------------
        // DECREASE TRANSACTIONS
        // --------------------------------------------------

        $transactionSql = "
            UPDATE active_users
            SET transactions_left = transactions_left - 1
            WHERE id = ?
            AND transactions_left > 0
        ";

        $stmt = $conn->prepare($transactionSql);

        $stmt->bind_param(
            "i",
            $activeUserId
        );

        $stmt->execute();


        if ($stmt->affected_rows !== 1) {

            throw new Exception(
                'Failed to use the transaction.'
            );
        }


        // --------------------------------------------------
        // RECORD TRANSACTION
        // --------------------------------------------------

        $logSql = "
            INSERT INTO roster_transactions
            (
                active_user_id,
                added_pokemon_id,
                dropped_pokemon_id,
                transaction_type
            )
            VALUES
            (
                ?,
                ?,
                ?,
                'swap'
            )
        ";

        $stmt = $conn->prepare($logSql);

        $droppedPokemonId =
            (int) $dropPokemon['showdown_pokemon_id'];

        $stmt->bind_param(
            "iii",
            $activeUserId,
            $addPokemonId,
            $droppedPokemonId
        );

        $stmt->execute();


        if ($stmt->affected_rows !== 1) {

            throw new Exception(
                'Failed to record the transaction.'
            );
        }


        // --------------------------------------------------
        // EVERYTHING WORKED
        // --------------------------------------------------

        $conn->commit();


        echo json_encode([
            'status' => 'success',
            'message' => 'Pokémon swap completed.',
            'added_pokemon' => $addPokemon['name'],
            'dropped_pokemon' => $dropPokemon['name'],
            'transactions_left' => $transactionsLeft - 1
        ]);

        exit;


    } catch (Exception $e) {

        // --------------------------------------------------
        // SOMETHING FAILED
        // --------------------------------------------------

        $conn->rollback();

        http_response_code(500);

        echo json_encode([
            'status' => 'error',
            'error' => 'Swap failed. Nothing was changed.'
        ]);

        exit;
    }