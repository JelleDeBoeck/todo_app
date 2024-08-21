<?php
require 'db_config.php';
require 'classes/List.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_list_title'])) {
    $title = $_POST['new_list_title'];
    $list = new ListModel($pdo, $title, $_SESSION['user_id']);
    $list->save();
    echo json_encode(['success' => true, 'title' => htmlspecialchars($title), 'id' => $pdo->lastInsertId()]);
    exit;
}
?>
