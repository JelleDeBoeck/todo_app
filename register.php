<?php
require 'db_config.php';
require 'classes/User.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $user = new User($pdo);
    $result = $user->register($firstname, $lastname, $email, $password);

    if ($result === true) {
        header("Location: login.php");
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
    <title>Registeren</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <form action="register.php" method="POST">
        <h1>Registeren</h1>
        <div class="name-container">
            <input type="text" name="firstname" placeholder="Voornaam" required>
            <input type="text" name="lastname" placeholder="Achternaam" required>
        </div>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Wachtwoord" required>
        <button type="submit">Registreren</button>
        <p>Heb je al een account? <a href="login.php">Inloggen</a></p>
    </form>
</body>
</html>
