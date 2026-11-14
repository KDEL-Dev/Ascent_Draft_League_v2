<?php

require_once __DIR__ . '/../../includes/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: members.php");
    exit;
}

// Retrieve email
$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email address.");
}

// Generate random password
$newPassword = bin2hex(random_bytes(6));

// Hash password
$hashedPassword = password_hash(
    $newPassword,
    PASSWORD_DEFAULT
);

// Update database
$sql = "UPDATE users
        SET password_hash = ?
        WHERE email = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ss",
    $hashedPassword,
    $email
);

if ($stmt->execute() && $stmt->affected_rows === 1) {

    $success = true;

} else {

    $success = false;

}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <title>Password Reset</title>
</head>

<body class="bg-body-secondary">

<main id="resetPasswordAdmin" class="container bg-white p-4 mt-5 rounded">

    <?php if ($success): ?>

        <h1 class="fs-3">Password Successfully Reset!</h1>

        <p>
            New password for <?= htmlspecialchars($email) ?>:
        </p>

        <div class="alert alert-success">
            <strong><?= htmlspecialchars($newPassword) ?></strong>
        </div>

        <button class="btn btn-primary"
                onclick="navigator.clipboard.writeText('<?= htmlspecialchars($newPassword) ?>')">
            Copy Password
        </button>

    <?php else: ?>

        <h1 class="fs-3">Password Reset Failed</h1>

        <p>Unable to update the user's password.</p>

    <?php endif; ?>

    <a href="../../admin/member-list.php" class="btn btn-secondary">
        Back to Member List
    </a>

</main>

</body>
</html>