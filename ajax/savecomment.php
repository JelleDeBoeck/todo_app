<?php
    include_once(__DIR__ . "/../classes/Comment.php");

    if(!empty($_POST)) {
        $c = new Comment();
        $c -> setTask_id($_POST['task_id']);
        $c -> setComment($_POST['comment']);
        $c -> setUser_id(1);

    }

    $c -> save();

    $reponse = [
        'status' => 'success',
        'body' => htmlspecialchars($c->getComment()),
        'message' => 'Comment saved'
    ];

    header('Content-Type: application/json');
    echo json_encode($reponse);

?>