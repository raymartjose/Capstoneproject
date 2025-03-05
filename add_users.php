<?php
include('assets/databases/dbconfig.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'The email address already exists.']);
    } else {
        $stmt = $connection->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password, $role);

        if ($stmt->execute()) {
            $newUserId = $stmt->insert_id; // Get the ID of the new user
            
            // Add an entry to the audit log
            $currentUserId = $_SESSION['user_id']; // Assuming logged-in user's ID is stored in the session
            $action = "Added";
            $recordType = "User";
            $recordId = $newUserId; // ID of the new user
            $newData = json_encode([
                'name' => $name,
                'email' => $email,
                'role' => $role
            ]);

            $auditQuery = "INSERT INTO audit_logs (user_id, action, record_type, record_id, new_data) 
                           VALUES (?, ?, ?, ?, ?)";
            $auditStmt = $connection->prepare($auditQuery);
            $auditStmt->bind_param("issis", $currentUserId, $action, $recordType, $recordId, $newData);
            $auditStmt->execute();
            $auditStmt->close();

            echo json_encode(['success' => true, 'message' => 'User added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding user. Please try again.']);
        }

        $stmt->close();
    }

    $connection->close();
}
?>
