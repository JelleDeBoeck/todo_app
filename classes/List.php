<?php
class ListModel {
    private $pdo;
    private $id;
    private $title;
    private $user_id;

    public function __construct($pdo, $title = '', $user_id = '') {
        $this->pdo = $pdo;
        $this->title = $title;
        $this->user_id = $user_id;
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

    public function save() {
        if ($this->id) {
            $stmt = $this->pdo->prepare("UPDATE lists SET title = :title WHERE id = :id");
            $stmt->execute(['title' => $this->title, 'id' => $this->id]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO lists (title, user_id) VALUES (:title, :user_id)");
            $stmt->execute(['title' => $this->title, 'user_id' => $this->user_id]);
            $this->id = $this->pdo->lastInsertId();
        }
    }

    public function delete() {
        if ($this->id) {
            $stmt = $this->pdo->prepare("DELETE FROM lists WHERE id = :id");
            $stmt->execute(['id' => $this->id]);
        }
    }
}
?>
