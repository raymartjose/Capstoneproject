document.querySelector('form').addEventListener('submit', function(event) {
    event.preventDefault();  // Prevent default form submission

    // Get form data
    const formData = new FormData(this);

    // Send AJAX request to add_users.php
    fetch('add_users.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        const errorMessageDiv = document.querySelector('.error-message');
        
        // Check if the email already exists
        if (!data.success) {
            errorMessageDiv.textContent = data.message;
            errorMessageDiv.style.display = 'block';
        } else {
            alert(data.message);
            closeModal();  // Close the modal on success
            location.reload();  // Reload the page or update the user list
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});

function openModal() {
    document.getElementById("addUserModal").style.display = "block";
}

function closeModal() {
    document.getElementById("addUserModal").style.display = "none";
    // Clear error message on modal close
    document.querySelector('.error-message').style.display = 'none';
}
