<?php
require 'db_config.php';
require 'classes/Task.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $task_id = $_GET['id'];
    $task = new TaskModel($pdo);
    $task->setId($task_id);
    $task->delete();
    header("Location: todo.php");
    exit;
}
?>
