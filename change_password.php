<?php
session_start();
include('assets/databases/dbconfig.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo "<script>alert('All fields are required.'); window.history.back();</script>";
        exit();
    }

    if ($new_password !== $confirm_password) {
        echo "<script>alert('New password and confirm password do not match.'); window.history.back();</script>";
        exit();
    }

    if (strlen($new_password) < 8) {
        echo "<script>alert('Password must be at least 8 characters long.'); window.history.back();</script>";
        exit();
    }

    // Fetch current password hash from the database
    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $stored_password);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!$stored_password || !password_verify($current_password, $stored_password)) {
        echo "<script>alert('Incorrect current password.'); window.history.back();</script>";
        exit();
    }

    // Hash the new password
    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in the database
    $update_query = "UPDATE users SET password = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($connection, $update_query);
    mysqli_stmt_bind_param($update_stmt, "si", $new_hashed_password, $user_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        echo "<script>alert('Password updated successfully. Please log in again.'); window.location.href='logout.php';</script>";
    } else {
        echo "<script>alert('Error updating password. Please try again.'); window.history.back();</script>";
    }

    mysqli_stmt_close($update_stmt);
}
?>
