<?php

require 'db_config.php';
session_start();

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];

            header("Location: todo.php");
            exit;

        } else {
            echo "Ongeldig e-mailadres of wachtwoord.";
    }

    } catch (PDOException $e) {
        die("Er is een fout opgetreden: " . $e->getMessage());
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <form action="login.php" method="POST">
        <h1>Inloggen</h1>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Wachtwoord" required>
        <button type="submit">Inloggen</button>
        <p>Heb je nog geen account? <a href="register.php">Registreren</a></p>
    </form>
</body>
</html>