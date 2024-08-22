<?php

    class Comment {
        private $comment;
        private $task_id;
        private $user_id;

        
        /**
         * Get the value of comment
         */ 
        public function getComment()
        {
                return $this->comment;
        }

        /**
         * Set the value of comment
         *
         * @return  self
         */ 
        public function setComment($comment)
        {
                $this->comment = $comment;

                return $this;
        }
        

        /**
         * Get the value of task_id
         */ 
        public function getTask_id()
        {
                return $this->task_id;
        }

        /**
         * Set the value of task_id
         *
         * @return  self
         */ 
        public function setTask_id($task_id)
        {
                $this->task_id = $task_id;

                return $this;
        }

        /**
         * Get the value of user_id
         */ 
        public function getUser_id()
        {
                return $this->user_id;
        }

        /**
         * Set the value of user_id
         *
         * @return  self
         */ 
        public function setUser_id($user_id)
        {
                $this->user_id = $user_id;

                return $this;
        }

        public function save() {
            $conn = new PDO("mysql:host=localhost;dbname=todo_app;charset=utf8", "root", "");
            $stmt = $conn -> prepare("INSERT INTO comments ( comment, task_id, user_id) VALUES ( :comment, :task_id, :user_id);");

            $comment = $this->getComment();
            $task_id = $this->getTask_id();
            $user_id = $this->getUser_id();

            $stmt ->bindValue(":comment", $comment);
            $stmt ->bindValue(":task_id", $task_id);
            $stmt ->bindValue(":user_id", $user_id);

            $result = $stmt->execute();
            return $result;
        }

        public static function getAll($task_id) {
                $conn = new PDO("mysql:host=localhost;dbname=todo_app;charset=utf8", "root", "");
                $stmt = $conn->prepare('SELECT * FROM comments WHERE task_id = :task_id');
                $stmt->bindValue(':task_id', $task_id);
                $result = $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }            

    }

?>