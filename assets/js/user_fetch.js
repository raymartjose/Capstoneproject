function filterUsers(role) {
    // Fetch filtered users via AJAX
    fetch(`fetch_users.php?role=${role}`)
        .then(response => response.text())
        .then(data => {
            const modalTableBody = document.querySelector("#allUsersTableBody");
            const mainTableBody = document.querySelector(".recent-grid1 .card-body .table-responsive tbody");

            if (modalTableBody) {
                modalTableBody.innerHTML = data;
            } else {
                console.warn("Modal table body not found.");
            }

            if (mainTableBody) {
                mainTableBody.innerHTML = data;
            } else {
                console.warn("Main table body not found.");
            }
        })
        .catch(error => console.error('Error fetching users:', error));
}
