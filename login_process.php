<?php
session_start();
include('assets/databases/dbconfig.php');

// Debugging output to ensure the script reaches this point
error_log("Reached login_process.php");

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect user input
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Create a SQL query to retrieve user by email
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("s", $username);  // Bind email parameter
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();  // Fetch user data

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            $roleDisplayMap = [
                'staff' => 'Finance Staff',
                'administrator' => 'Administrator',
                'super_admin' => 'Super Admin'
            ];
            $_SESSION['role_display'] = $roleDisplayMap[$user['role']] ?? 'Unknown Role';

            // Update the user's status to active
            $updateStatus = "UPDATE users SET status = 'active' WHERE id = ?";
            $stmtUpdate = $connection->prepare($updateStatus);
            $stmtUpdate->bind_param("i", $user['id']);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            // Redirect based on role
            if ($_SESSION['role'] == 'super_admin') {
                // Redirect to the index page for super_admin
                header("Location: analytics.php");
            } elseif ($_SESSION['role'] == 'administrator') {
                // Redirect to the administrator dashboard
                header("Location: administrator_dashboard.php");
            } elseif ($_SESSION['role'] == 'staff') {
                // Redirect to the staff dashboard
                header("Location: staff_dashboard.php");
            }

            exit();  // Ensure no further code runs after redirect
        } else {
            $_SESSION['error_message'] = "Invalid password.";
            error_log("Incorrect password for user: " . $username);
            header("Location: login.php");  // Redirect back to login page
            exit();
        }
    } else {
        $_SESSION['error_message'] = "User not found.";
        error_log("No user found with email: " . $username);
        header("Location: login.php");  // Redirect back to login page
        exit();
    }

    // Close the statement and connection
    $stmt->close();
    $connection->close();
}
?>
