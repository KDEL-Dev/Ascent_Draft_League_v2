<?php

    session_start();

    require_once __DIR__ . '/connection.php';


    // ----------------------------------------
    // CHECK LOGIN
    // ----------------------------------------

    if (!isset($_SESSION['user_id'])) {

        header("Location: ../login.php");
        exit;
    }


    // ----------------------------------------
    // GET CURRENT USER
    // ----------------------------------------

    $userId = (int) $_SESSION['user_id'];

    $sql = "
        SELECT
            id,
            email,
            default_team_name,
            default_team_mascot,
            role,
            theme
        FROM users
        WHERE id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Authentication query failed: " . $conn->error);
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    $currentUser = $result->fetch_assoc();

    $stmt->close();


    // ----------------------------------------
    // USER DOES NOT EXIST
    // ----------------------------------------

    if (!$currentUser) {

        session_unset();
        session_destroy();

        header("Location: ../login.php");
        exit;
    }


    // ----------------------------------------
    // USER VARIABLES
    // ----------------------------------------

    $userId = (int) $currentUser['id'];

    $userRole = $currentUser['role'];

    $isOwner = ($userRole === 'owner');

    $isAdmin = (
        $userRole === 'admin' ||
        $userRole === 'owner'
    );

    $isRegularUser = ($userRole === 'user');