<?php
require 'db_config.php';
require 'classes/User.php';
session_start();

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Ongeldig emailadres.";
    }

    if (empty($errors)) {
        $user = new User($pdo);
        $result = $user->login($email, $password);

        if ($result === true) {
            header("Location: todo.php");
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
    <title>Inloggen</title>
    <link rel="stylesheet" href="css/style-login.css">
</head>
<body>
    <form action="login.php" method="POST">
        <h1>Inloggen</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Wachtwoord" required>
        <button type="submit">Inloggen</button>
        <p>Heb je nog geen account? <a href="register.php">Registreren</a></p>
    </form>
</body>
</html>
