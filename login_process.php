<?php
session_start();
include('assets/databases/dbconfig.php');

session_regenerate_id(true);

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // CSRF Token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    $username = filter_var($_POST['username'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Brute force protection variables
    $failed_attempts_key = "failed_attempts_" . $ip_address;
    $lockout_key = "lockout_" . $ip_address;

    if (!isset($_SESSION[$failed_attempts_key])) {
        $_SESSION[$failed_attempts_key] = 0;
    }

    if (isset($_SESSION[$lockout_key]) && $_SESSION[$lockout_key] > time()) {
        $_SESSION['error_message'] = "Too many failed attempts. Try again later.";
        header("Location: login.php");
        exit();
    }

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Reset failed attempts
            $_SESSION[$failed_attempts_key] = 0;

            // Generate new session token
            $session_token = bin2hex(random_bytes(32));

            // Store session token in the database
            $updateSession = "UPDATE users SET session_token = ? WHERE id = ?";
            $stmtUpdate = $connection->prepare($updateSession);
            $stmtUpdate->bind_param("si", $session_token, $user['id']);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
            
            // Convert role values to display-friendly names
            $role_display = [
                'super_admin' => 'Super Admin',
                'administrator' => 'Administrator',
                'staff' => 'Finance Staff'
            ];
            
            $_SESSION['role'] = $role_display[$user['role']] ?? $user['role'];
            $_SESSION['session_token'] = $session_token;

            // Update user status to 'active'
            $updateStatus = "UPDATE users SET status = 'active' WHERE id = ?";
            $stmtUpdate = $connection->prepare($updateStatus);
            $stmtUpdate->bind_param("i", $user['id']);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            // Redirect user based on role
            if ($user['role'] == 'super_admin') {
                header("Location: analytics.php");
            } elseif ($user['role'] == 'administrator') {
                header("Location: administrator_dashboard.php");
            } elseif ($user['role'] == 'staff') {
                header("Location: staff_dashboard.php");
            }
            exit();
        } else {
            $_SESSION['error_message'] = "Invalid password.";
            $_SESSION[$failed_attempts_key] += 1;

            if ($_SESSION[$failed_attempts_key] >= 5) {
                $_SESSION[$lockout_key] = time() + 300; // Lock for 5 minutes
            }

            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "User not found.";
        $_SESSION[$failed_attempts_key] += 1;

        if ($_SESSION[$failed_attempts_key] >= 5) {
            $_SESSION[$lockout_key] = time() + 300;
        }

        header("Location: login.php");
        exit();
    }

    $stmt->close();
    $connection->close();
}
?>
