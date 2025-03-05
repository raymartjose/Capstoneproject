// JavaScript to open the modal
function openAllCustomersModal() {
    document.getElementById('allCustomersModal').style.display = 'block';
}

// JavaScript to close the modal if the user clicks outside the modal content
window.onclick = function(event) {
    const modal = document.getElementById('allCustomersModal');
    if (event.target === modal) {
        modal.style.display = "none";
    }
}
