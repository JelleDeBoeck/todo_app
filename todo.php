<?php
require 'db_config.php';
require 'classes/List.php';
require 'classes/Task.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_list_title'])) {
    $title = $_POST['new_list_title'];
    $list = new ListModel($pdo, $title, $_SESSION['user_id']);
    $list->save();
    header("Location: todo.php");
    exit;
}

if (isset($_GET['delete_list_id'])) {
    $list_id = $_GET['delete_list_id'];
    $list = new ListModel($pdo);
    $list->setId($list_id);
    $list->delete();
    header("Location: todo.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_task_title']) && isset($_POST['list_id'])) {
    $list_id = $_POST['list_id'];
    $title = $_POST['new_task_title'];
    $deadline = $_POST['deadline'] ?? null;
    $task = new TaskModel($pdo, $list_id, $title, $deadline);
    $task->save();
    header("Location: todo.php");
    exit;
}

if (isset($_GET['delete_task_id'])) {
    $task_id = $_GET['delete_task_id'];
    $task = new TaskModel($pdo);
    $task->setId($task_id);
    $task->delete();
    header("Location: todo.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$listsStmt = $pdo->prepare("SELECT * FROM lists WHERE user_id = :user_id");
$listsStmt->execute(['user_id' => $user_id]);
$lists = $listsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo App</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</head>
<body>
<div class="container">
    <h1>Todo App</h1>

    <h2>Voeg een nieuwe lijst toe</h2>
    <form method="POST">
        <input type="text" name="new_list_title" placeholder="Lijst Titel" required>
        <button type="submit">Lijst toevoegen</button>
    </form>

    <h2>Jouw Lijsten</h2>
    <?php foreach ($lists as $list): ?>
        <div class="list">
            <h3><?php echo htmlspecialchars($list['title']); ?></h3>

            <a href="?delete_list_id=<?php echo $list['id']; ?>" onclick="return confirm('Weet je zeker dat je deze lijst wilt verwijderen?');">Verwijder lijst</a>

            <h4>Voeg een taak toe aan deze lijst</h4>
            <form method="POST" class="task-form">
                <input type="hidden" name="list_id" value="<?php echo $list['id']; ?>">
                <input type="text" name="new_task_title" placeholder="Taak Titel" required>
                <div class="task-form-controls">
                    <i class="fas fa-calendar-alt"></i>
                    <input type="date" name="deadline">
                    <button type="submit"><i class="fas fa-plus"></i></button>
                </div>
            </form>

            <h4>Taaklijst</h4>
            <?php
            $tasksStmt = $pdo->prepare("SELECT * FROM tasks WHERE list_id = :list_id ORDER BY deadline ASC");
            $tasksStmt->execute(['list_id' => $list['id']]);
            $tasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php foreach ($tasks as $task): ?>
                <div class="task">
                    <input type="checkbox" <?php echo $task['done'] ? 'checked' : ''; ?> onclick="toggleTaskDone(<?php echo $task['id']; ?>)">
                    <span><?php echo htmlspecialchars($task['title']); ?></span>
                    <?php if ($task['deadline']): ?>
                        <span>(Deadline: <?php echo htmlspecialchars($task['deadline']); ?>)</span>
                    <?php endif; ?>

                    <a href="?delete_task_id=<?php echo $task['id']; ?>" onclick="return confirm('Weet je zeker dat je deze taak wilt verwijderen?');">Verwijder taak</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
