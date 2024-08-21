<?php
require 'db_config.php';
require 'classes/User.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $user = new User($pdo);
    $result = $user->login($email, $password);

    if ($result === true) {
        header("Location: todo.php");
        exit;
    } else {
        echo $result;
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