<?php
include_once(__DIR__ . "/classes/Comment.php");
require 'db_config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_file'])) {
    $taskId = (int)$_POST['task_id'];
    $fileName = htmlspecialchars($_POST['file_name'], ENT_QUOTES, 'UTF-8');

    $stmt = $pdo->prepare("SELECT file_name FROM tasks WHERE id = :task_id");
    $stmt->execute(['task_id' => $taskId]);
    $currentFileName = $stmt->fetchColumn();

    if ($currentFileName === $fileName) {
        $filePath = 'uploads/' . $fileName;

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $stmt = $pdo->prepare("UPDATE tasks SET file_name = NULL WHERE id = :task_id");
        $stmt->execute(['task_id' => $taskId]);
    }

    header("Location: todo.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_list'])) {
        $title = htmlspecialchars($_POST['new_list_title'], ENT_QUOTES, 'UTF-8');
        $stmt = $pdo->prepare("INSERT INTO lists (title, user_id) VALUES (:title, :user_id)");
        $stmt->execute(['title' => $title, 'user_id' => $user_id]);
    }

    if (isset($_POST['add_task'])) {
        $listId = (int)$_POST['list_id'];
        $taskTitle = htmlspecialchars($_POST['new_task_title'], ENT_QUOTES, 'UTF-8');
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
            $_SESSION['error'] = "Er is een fout opgetreden bij het toevoegen van de taak: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }

        header("Location: todo.php");
        exit;
    }

    if (isset($_POST['upload_file'])) {
        $taskId = (int)$_POST['task_id'];
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
            $_SESSION['error'] = "Er is een fout opgetreden bij het uploaden van het bestand: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }

        header("Location: todo.php");
        exit;
    }

    if (isset($_POST['comment']) && isset($_POST['task_id'])) {
        $comment = htmlspecialchars($_POST['comment'], ENT_QUOTES, 'UTF-8');
        $task_id = (int)$_POST['task_id'];

        try {
            $c = new Comment();
            $c->setComment($comment);
            $c->setTask_id($task_id);
            $c->setUser_id($user_id);
            $c->save();

            echo json_encode([
                'status' => 'success',
                'body' => htmlspecialchars($c->getComment(), ENT_QUOTES, 'UTF-8')
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
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
            <?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="list-header">
        <h2>Jouw Lijsten</h2>
        <button onclick="document.getElementById('addListModal').style.display='flex'">Toevoegen <i class="bx bx-plus"></i></button>
    </div>

    <div id="lists" class="scrollable-container"> 
        <?php if (empty($lists)): ?>
            <div class="empty-state">
                <p>Je hebt momenteel geen lijsten.</p>
            </div>
        <?php else: ?>
            <?php foreach ($lists as $list): ?>
                <div class="list">
                    <div class="list-header">
                        <h3><?php echo htmlspecialchars($list['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="list-actions">
                            <a href="delete_list.php?id=<?php echo (int)$list['id']; ?>" onclick="return confirm('Weet je zeker dat je deze lijst wilt verwijderen?');" class="icon">
                                <i class='bx bxs-trash'></i>
                            </a>
                        </div>
                    </div>
                    <div id="taskModal_<?php echo (int)$list['id']; ?>" class="modal">
                        <div class="modal-content">
                            <span class="close" onclick="document.getElementById('taskModal_<?php echo (int)$list['id']; ?>').style.display='none'">&times;</span>
                            <h2>Voeg een taak toe aan lijst: <?php echo htmlspecialchars($list['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <form method="POST" action="todo.php">
                                <input type="hidden" name="list_id" value="<?php echo (int)$list['id']; ?>">
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
                        <button onclick="document.getElementById('taskModal_<?php echo (int)$list['id']; ?>').style.display='block'" class="add-task-button">
                            Toevoegen <i class='bx bx-plus'></i>
                        </button>
                    </div>
                    <?php
                    $tasksStmt = $pdo->prepare("SELECT * FROM tasks WHERE list_id = :list_id ORDER BY $sortBy $sortOrder");
                    $tasksStmt->execute(['list_id' => (int)$list['id']]);
                    $tasks = $tasksStmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <div class="tasks-heading">
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-item">
                            <div class="task-header">
                                <span class="task-title"><?php echo htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if ($task['deadline']): ?>
                                    <span class="task-deadline">Deadline: <?php echo htmlspecialchars($task['deadline'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                                <a href="delete_task.php?id=<?php echo (int)$task['id']; ?>" onclick="return confirm('Weet je zeker dat je deze taak wilt verwijderen?');" class="icon">
                                    <i class='bx bxs-trash'></i>
                                </a>
                            </div>
                            <div class="task-actions">
                                <?php if ($task['file_name']): ?>
                                    <div class="file-info">
                                        <a href="uploads/<?php echo htmlspecialchars($task['file_name'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                            <?php echo htmlspecialchars($task['file_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                        <form method="POST" action="todo.php" style="display:inline;">
                                            <input type="hidden" name="task_id" value="<?php echo (int)$task['id']; ?>">
                                            <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($task['file_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <button id="delete-file-btn" type="submit" name="delete_file" onclick="return confirm('Weet je zeker dat je dit bestand wilt verwijderen?');"><i class='bx bxs-trash'></i></button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                                
                                <button id="file-btn" onclick="document.getElementById('uploadModal_<?php echo (int)$task['id']; ?>').style.display='block'" class="icon">Bestand Uploaden
                                    <i class='bx bx-upload'></i>
                                </button>
                                <button id="comment-btn" onclick="document.getElementById('commentModal_<?php echo (int)$task['id']; ?>').style.display='block'" class="icon">Comment Toevoegen
                                    <i class='bx bx-comment-add'></i>
                                </button>
                            </div>
                            <div id="uploadModal_<?php echo (int)$task['id']; ?>" class="modal">
                                <div class="modal-content">
                                    <span class="close" onclick="document.getElementById('uploadModal_<?php echo (int)$task['id']; ?>').style.display='none'">&times;</span>
                                    <h2>Upload een bestand voor taak: <?php echo htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                    <form method="POST" action="todo.php" enctype="multipart/form-data">
                                        <input type="hidden" name="task_id" value="<?php echo (int)$task['id']; ?>">
                                        <input type="file" name="task_file" accept=".pdf, .docx" required>
                                        <button type="submit" name="upload_file">Uploaden</button>
                                    </form>
                                </div>
                            </div>
                            <div id="commentModal_<?php echo (int)$task['id']; ?>" class="modal">
                                <div class="modal-content">
                                    <span class="close" onclick="document.getElementById('commentModal_<?php echo (int)$task['id']; ?>').style.display='none'">&times;</span>
                                    <h2>Voeg een comment toe aan taak: <?php echo htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                    <form id="commentForm_<?php echo (int)$task['id']; ?>" method="POST">
                                        <input type="hidden" name="task_id" value="<?php echo (int)$task['id']; ?>">
                                        <textarea name="comment" placeholder="Voeg je comment hier toe..." required></textarea>
                                        <button type="submit">Toevoegen</button>
                                    </form>
                                    <div id="comments_<?php echo (int)$task['id']; ?>">
                                    </div>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form[id^="commentForm_"]').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const formData = new FormData(form);
                    const taskId = formData.get('task_id');

                    fetch('todo.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const commentsDiv = document.getElementById('comments_' + taskId);
                            const newComment = document.createElement('div');
                            newComment.innerHTML = data.body;
                            commentsDiv.appendChild(newComment);
                        } else {
                            alert('Er is een fout opgetreden: ' + data.message);
                        }
                    });
                });
            });
        });
    </script>
</div>
</body>
</html>
