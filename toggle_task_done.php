<?php
require 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_id = $_POST['task_id'];
    $done = isset($_POST['done']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE tasks SET done = :done WHERE id = :id");
    $stmt->execute(['done' => $done, 'id' => $task_id]);

    header("Location: todo.php");
    exit;
}
