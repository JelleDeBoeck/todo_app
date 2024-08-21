<?php
require 'db_config.php';
require 'classes/List.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $list_id = $_GET['id'];
    $list = new ListModel($pdo);
    $list->setId($list_id);
    $list->delete();
    header("Location: todo.php");
    exit;
}
?>
