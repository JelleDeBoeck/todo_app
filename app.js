document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll("[id^='btnAddComment_']").forEach(button => {
        button.addEventListener("click", function() {
            let task_id = this.dataset.task_id;
            let comment = document.querySelector(`#commentText_${task_id}`).value;

            let formData = new FormData();
            formData.append("comment", comment);
            formData.append("task_id", task_id);

            fetch("todo.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        let newComment = document.createElement('li');
                        newComment.innerHTML = result.body;
                        document.querySelector(`.comment_list_${task_id}`).appendChild(newComment);
                        document.querySelector(`#commentText_${task_id}`).value = '';
                    } else {
                        console.error("Error:", result.message);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                });
        });
    });
});
