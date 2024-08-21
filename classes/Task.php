<?php
class TaskModel {
    private $pdo;
    private $id;
    private $list_id;
    private $title;
    private $deadline;
    private $done;

    public function __construct($pdo, $list_id = '', $title = '', $deadline = '', $done = false) {
        $this->pdo = $pdo;
        $this->list_id = $list_id;
        $this->title = $title;
        $this->deadline = $deadline;
        $this->done = $done;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }

    public function setTitle($title) {
        if (empty($title)) {
            throw new Exception('Title cannot be empty.');
        }
        $this->title = $title;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setDeadline($deadline) {
        $this->deadline = $deadline;
    }

    public function getDeadline() {
        return $this->deadline;
    }

    public function setDone($done) {
        $this->done = $done;
    }

    public function isDone() {
        return $this->done;
    }

    public function save() {
        if ($this->id) {
            $stmt = $this->pdo->prepare("UPDATE tasks SET title = :title, deadline = :deadline, done = :done WHERE id = :id");
            $stmt->execute(['title' => $this->title, 'deadline' => $this->deadline, 'done' => $this->done, 'id' => $this->id]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO tasks (list_id, title, deadline, done) VALUES (:list_id, :title, :deadline, :done)");
            $stmt->execute(['list_id' => $this->list_id, 'title' => $this->title, 'deadline' => $this->deadline, 'done' => $this->done]);
            $this->id = $this->pdo->lastInsertId();
        }
    }

    public function delete() {
        if ($this->id) {
            $stmt = $this->pdo->prepare("DELETE FROM tasks WHERE id = :id");
            $stmt->execute(['id' => $this->id]);
        }
    }
}
?>
