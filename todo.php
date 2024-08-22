<?php
include_once(__DIR__ . "/classes/Comment.php");
require 'db_config.php';
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

        try {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE title = :title AND list_id = :list_id");
            $checkStmt->execute(['title' => $taskTitle, 'list_id' => $listId]);
            $taskExists = $checkStmt->fetchColumn() > 0;

            if ($taskExists) {
                $_SESSION['error'] = "Deze taak bestaat al in de lijst.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO tasks (title, list_id, deadline) VALUES (:title, :list_id, :deadline)");
                $stmt->execute([
                    'title' => $taskTitle,
                    'list_id' => $listId,
                    'deadline' => $deadline
                ]);
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Er is een fout opgetreden bij het toevoegen van de taak: " . $e->getMessage();
        }

        header("Location: todo.php");
        exit;
    }

    if (isset($_POST['upload_file'])) {
        $taskId = $_POST['task_id'];
        $file = $_FILES['task_file'];

        try {
            if ($file['size'] > 0) {
                $allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!in_array($file['type'], $allowedTypes)) {
                    $_SESSION['error'] = "Alleen .pdf en .docx bestanden zijn toegestaan.";
                    header("Location: todo.php");
                    exit;
                }

                $targetDir = "uploads/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $fileName = time() . "_" . basename($file["name"]);
                $targetFilePath = $targetDir . $fileName;

                if (!move_uploaded_file($file["tmp_name"], $targetFilePath)) {
                    $_SESSION['error'] = "Er is een fout opgetreden bij het uploaden van het bestand.";
                    header("Location: todo.php");
                    exit;
                }

                $stmt = $pdo->prepare("UPDATE tasks SET file_name = :file_name WHERE id = :task_id");
                $stmt->execute(['file_name' => $fileName, 'task_id' => $taskId]);
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Er is een fout opgetreden bij het uploaden van het bestand: " . $e->getMessage();
        }

        header("Location: todo.php");
        exit;
    }

    if (isset($_POST['comment']) && isset($_POST['task_id'])) {
        $comment = $_POST['comment'];
        $task_id = $_POST['task_id'];

        try {
            $c = new Comment();
            $c->setComment($comment);
            $c->setTask_id($task_id);
            $c->setUser_id($user_id);
            $c->save();

            echo json_encode([
                'status' => 'success',
                'body' => htmlspecialchars($c->getComment())
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
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
    <link rel="stylesheet" href="css/styling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.4/css/boxicons.min.css">
</head>
<body>
<div class="container">
    <h1>Todo App</h1>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

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
                    <div class="filter-container">
                        <a href="?sort=ascending&type=title">Titel oplopend</a> | 
                        <a href="?sort=descending&type=title">Titel aflopend</a> | 
                        <a href="?sort=ascending&type=deadline">Deadline oplopend</a> | 
                        <a href="?sort=descending&type=deadline">Deadline aflopend</a>
                    </div>

                    <div class="list-header">
                        <h2>Taken</h2>
                        <button onclick="document.getElementById('taskModal_<?php echo $list['id']; ?>').style.display='block'" class="add-task-button">
                            Toevoegen <i class='bx bx-plus'></i>
                        </button>
                    </div>
                    <?php
                    $tasksStmt = $pdo->prepare("SELECT * FROM tasks WHERE list_id = :list_id ORDER BY $sortBy $sortOrder");
                    $tasksStmt->execute(['list_id' => $list['id']]);
                    $tasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <div class="tasks-heading">
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-item">
                            <div class="task-header">
                            <span class="task-title"><?php echo htmlspecialchars($task['title']); ?></span>
                            <?php if ($task['deadline']): ?>
                                <span class="task-deadline">Deadline: <?php echo htmlspecialchars($task['deadline']); ?></span>
                            <?php endif; ?>
                            <a href="delete_task.php?id=<?php echo $task['id']; ?>" onclick="return confirm('Weet je zeker dat je deze taak wilt verwijderen?');" class="icon">
                                    <i class='bx bxs-trash'></i>
                                </a>
                            </div>
                            <div class="task-actions">
                                <?php if ($task['file_name']): ?>
                                    <a href="uploads/<?php echo htmlspecialchars($task['file_name']); ?>" target="_blank" class="icon">
                                        <i class='bx bx-file'></i>
                                    </a>
                                <?php endif; ?>
                                
                                <button id="file-btn" onclick="document.getElementById('uploadModal_<?php echo $task['id']; ?>').style.display='block'" class="icon">Bestand Uploaden
                                    <i class='bx bx-upload'></i>
                                </button>
                                <button id="comment-btn" onclick="document.getElementById('commentModal_<?php echo $task['id']; ?>').style.display='block'" class="icon">Comment Toevoegen
                                    <i class='bx bx-comment-add'></i>
                                </button>
                            </div>
                            <div id="uploadModal_<?php echo $task['id']; ?>" class="modal">
                                <div class="modal-content">
                                    <span class="close" onclick="document.getElementById('uploadModal_<?php echo $task['id']; ?>').style.display='none'">&times;</span>
                                    <h2>Upload bestand voor taak: <?php echo htmlspecialchars($task['title']); ?></h2>
                                    <form method="POST" action="todo.php" enctype="multipart/form-data">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <input type="file" name="task_file" accept=".pdf, .docx" required>
                                        <button type="submit" name="upload_file">Uploaden</button>
                                    </form>
                                </div>
                            </div>

                            <div id="commentModal_<?php echo $task['id']; ?>" class="modal">
                                <div class="modal-content">
                                    <span class="close" onclick="document.getElementById('commentModal_<?php echo $task['id']; ?>').style.display='none'">&times;</span>
                                    <h2>Voeg een opmerking toe aan taak: <?php echo htmlspecialchars($task['title']); ?></h2>
                                    <textarea id="commentText_<?php echo $task['id']; ?>" placeholder="Voeg een opmerking toe..."></textarea>
                                    <button id="btnAddComment_<?php echo $task['id']; ?>" data-task_id="<?php echo $task['id']; ?>">Voeg opmerking toe</button>
                                    <ul class="comment_list_<?php echo $task['id']; ?>">
                                        <?php
                                        $comments = Comment::getAll($task['id']);
                                        foreach ($comments as $comment): ?>
                                            <li><?php echo htmlspecialchars($comment['comment']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="addListModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addListModal').style.display='none'">&times;</span>
            <h2>Nieuwe lijst toevoegen</h2>
            <form method="POST" action="todo.php">
                <input type="text" name="new_list_title" placeholder="Lijst Titel" required>
                <button type="submit" name="add_list">Toevoegen</button>
            </form>
        </div>
    </div>

</div>

<script src="app.js"></script>
</body>
</html>
