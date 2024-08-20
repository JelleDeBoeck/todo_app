<?php

require 'db_config.php';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo -> prepare("SELECT * FROM users WHERE email = :email");
        $stmt -> execute(['email' => $email]);
        if($stmt -> rowCount() > 0) {
            echo "E-mailadres is al geregistreerd.";
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, email, password) VALUES (:firstname, :lastname, :email, :password)");
        $stmt->execute([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        header("Location: login.php");
        exit;

    } catch (PDOException $e) {
        die("Er is een fout opgetreden: " . $e->getMessage());
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
