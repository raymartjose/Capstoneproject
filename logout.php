<?php
/*
session_start();  // Start the session
session_unset();  // Unset all session variables
session_destroy();  // Destroy the session
header("Location: login.php");  // Redirect to login page after logging out
exit();*/
session_start();
include('assets/databases/dbconfig.php');

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Update user status to inactive
    $updateStatus = "UPDATE users SET status = 'inactive' WHERE id = ?";
    $stmtUpdate = $connection->prepare($updateStatus);
    $stmtUpdate->bind_param("i", $userId);
    $stmtUpdate->execute();
    $stmtUpdate->close();
}

// Destroy session and redirect to login page
session_destroy();
header("Location: login.php");
exit();
?>
