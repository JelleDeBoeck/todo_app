<?php
require 'db_config.php';
require 'classes/List.php';
require 'classes/Task.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_list'])) {
        $title = $_POST['new_list_title'];
        $stmt = $pdo->prepare("INSERT INTO lists (title, user_id) VALUES (:title, :user_id)");
        $stmt->execute(['title' => $title, 'user_id' => $user_id]);
    }

    if (isset($_POST['add_task'])) {
        $listId = $_POST['list_id'];
        $taskTitle = $_POST['new_task_title'];
        $deadline = $_POST['deadline'];

        if (strtotime($deadline) < strtotime(date('Y-m-d'))) {
            $_SESSION['error'] = "De deadline kan niet in het verleden liggen.";
            header("Location: todo.php");
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO tasks (title, list_id, deadline) VALUES (:title, :list_id, :deadline)");
        $stmt->execute(['title' => $taskTitle, 'list_id' => $listId, 'deadline' => $deadline]);
    }

    header("Location: todo.php");
    exit;
}

$listsStmt = $pdo->prepare("SELECT * FROM lists WHERE user_id = :user_id");
$listsStmt->execute(['user_id' => $user_id]);
$lists = $listsStmt->fetchAll(PDO::FETCH_ASSOC);

$sortOrder = 'ASC';
$sortBy = 'deadline';

if (isset($_GET['sort']) && isset($_GET['type'])) {
    $sortOrder = $_GET['sort'] === 'descending' ? 'DESC' : 'ASC';
    $sortBy = in_array($_GET['type'], ['title', 'deadline']) ? $_GET['type'] : 'deadline';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo App</title>
    <link rel="stylesheet" href="css/style-todo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.4/css/boxicons.min.css">
</head>
<body>
<div class="container">
    <h1>Todo App</h1>

    <div class="list-header">
        <h2>Jouw Lijsten</h2>
        <button onclick="document.getElementById('addListModal').style.display='flex'">Toevoegen <i class="bx bx-plus"></i></button>
    </div>

    <div id="lists">
        <?php if (empty($lists)): ?>
            <div class="empty-state">
                <p>Je hebt momenteel geen lijsten.</p>
            </div>
        <?php else: ?>
            <?php foreach ($lists as $list): ?>
                <div class="list">
                    <div class="list-header">
                        <h3><?php echo htmlspecialchars($list['title']); ?></h3>
                        <div class="list-actions">
                            <a href="delete_list.php?id=<?php echo $list['id']; ?>" onclick="return confirm('Weet je zeker dat je deze lijst wilt verwijderen?');" class="icon">
                                <i class='bx bxs-trash'></i>
                            </a>
                            <button onclick="document.getElementById('taskModal_<?php echo $list['id']; ?>').style.display='block'" class="add-task-button">
                                <i class='bx bx-plus'></i>
                            </button>
                        </div>
                    </div>

                    <div id="taskModal_<?php echo $list['id']; ?>" class="modal">
                        <div class="modal-content">
                            <span class="close" onclick="document.getElementById('taskModal_<?php echo $list['id']; ?>').style.display='none'">&times;</span>
                            <h2>Voeg een taak toe aan lijst: <?php echo htmlspecialchars($list['title']); ?></h2>
                            <form method="POST" action="todo.php">
                                <input type="hidden" name="list_id" value="<?php echo $list['id']; ?>">
                                <input type="text" name="new_task_title" placeholder="Taak Titel" required>
                                <input type="date" name="deadline" min="<?php echo date('Y-m-d'); ?>" required>
                                <button type="submit" name="add_task">Toevoegen</button>
                            </form>
                        </div>
                    </div>

                    <h4>Sorteer op:</h4>
                    <a href="?sort=ascending&type=title">Titel oplopend</a> | 
                    <a href="?sort=descending&type=title">Titel aflopend</a> | 
                    <a href="?sort=ascending&type=deadline">Deadline oplopend</a> | 
                    <a href="?sort=descending&type=deadline">Deadline aflopend</a>

                    <h4>Taken</h4>
                    <?php
                    $tasksStmt = $pdo->prepare("SELECT * FROM tasks WHERE list_id = :list_id ORDER BY $sortBy $sortOrder");
                    $tasksStmt->execute(['list_id' => $list['id']]);
                    $tasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php foreach ($tasks as $task): ?>
                        <div class="task">
                            <form method="POST" action="toggle_task_done.php" style="display: inline;">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <input type="checkbox" name="done" <?php echo $task['done'] ? 'checked' : ''; ?> onchange="this.form.submit();">
                            </form>
                            <span><?php echo htmlspecialchars($task['title']); ?></span>
                            <?php if ($task['deadline']): ?>
                                <span>
                                    <?php
                                    $currentDate = new DateTime();
                                    $deadlineDate = new DateTime($task['deadline']);
                                    $interval = $currentDate->diff($deadlineDate);
                                    $daysRemaining = $interval->format('%r%a');

                                    if ($daysRemaining < 0) {
                                        echo "(Deadline verstreken: " . abs($daysRemaining) . " dagen geleden)";
                                    } elseif ($daysRemaining == 0) {
                                        echo "(Deadline: Vandaag)";
                                    } else {
                                        echo "(Deadline: $daysRemaining dagen resterend)";
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>
                            <a href="delete_task.php?id=<?php echo $task['id']; ?>" onclick="return confirm('Weet je zeker dat je deze taak wilt verwijderen?');" class="icon">
                                <i class='bx bxs-trash'></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="addListModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('addListModal').style.display='none'">&times;</span>
        <h2>Voeg een nieuwe lijst toe</h2>
        <form method="POST" action="todo.php">
            <input type="text" name="new_list_title" placeholder="Lijst Titel" required>
            <button type="submit" name="add_list">Toevoegen</button>
        </form>
    </div>
</div>

</body>
</html>
