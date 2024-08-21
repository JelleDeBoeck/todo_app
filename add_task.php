<?php
require 'db_config.php';
require 'classes/Task.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_task_title']) && isset($_POST['list_id'])) {
    $list_id = $_POST['list_id'];
    $title = $_POST['new_task_title'];
    $deadline = $_POST['deadline'] ?? null;
    $task = new TaskModel($pdo, $list_id, $title, $deadline);
    $task->save();
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}
?>
