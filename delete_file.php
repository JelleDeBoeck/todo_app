<?php
require 'db_config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $taskId = $_POST['task_id'];
    $fileName = $_POST['file_name'];

    $filePath = "uploads/" . $fileName;
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    $stmt = $pdo->prepare("UPDATE tasks SET file_name = NULL WHERE id = :task_id");
    $stmt->execute(['task_id' => $taskId]);

    $_SESSION['success'] = "Bestand succesvol verwijderd.";
    header("Location: todo.php");
    exit;
}
