<?php
require 'db_config.php';
require 'classes/User.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = htmlspecialchars(trim($_POST['firstname']), ENT_QUOTES, 'UTF-8');
    $lastname = htmlspecialchars(trim($_POST['lastname']), ENT_QUOTES, 'UTF-8');
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Ongeldig emailadres.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Wachtwoord moet minimaal 8 tekens lang zijn.";
    }

    if (empty($errors)) {
        $user = new User($pdo);
        $result = $user->register($firstname, $lastname, $email, $password);

        if ($result === true) {
            header("Location: login.php");
            exit;
        } else {
            $errors[] = htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registeren</title>
    <link rel="stylesheet" href="css/style-login.css">
</head>
<body>
    <form action="register.php" method="POST">
        <h1>Registeren</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

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
