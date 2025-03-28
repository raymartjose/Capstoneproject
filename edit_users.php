<?php
include('assets/databases/dbconfig.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];


    // Fetch the current data of the user before updating
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $oldData = $result->fetch_assoc(); // Fetch old data

    // Prepare new data
    $newData = [
        'name' => $name,
        'email' => $email,
        'role' => $role,
    ];

    // Check if there are any changes
    $hasChanges = false;
    foreach ($newData as $key => $value) {
        if ($oldData[$key] !== $value) {
            $hasChanges = true;
            break;
        }
    }

    // If changes exist, update the user and log the action
    if ($hasChanges) {
        // Update query to modify user details
        $stmt = $connection->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $role, $id);

        if ($stmt->execute()) {
            // Audit log for the update
            $currentUserId = $_SESSION['user_id']; // Logged-in user ID

            // Prepare audit log data
            $action = "Updated";
            $recordType = "User";
            $recordId = $id;
            $oldDataJson = json_encode($oldData); // Convert old data to JSON format
            $newDataJson = json_encode($newData); // Convert new data to JSON format

            // Insert the action into the audit logs
            $auditQuery = "INSERT INTO audit_logs (user_id, action, record_type, record_id, old_data, new_data) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $auditStmt = $connection->prepare($auditQuery);
            $auditStmt->bind_param("ississ", $currentUserId, $action, $recordType, $recordId, $oldDataJson, $newDataJson);
            $auditStmt->execute();

            // Respond with success
            echo json_encode(['success' => true, 'message' => 'User updated and logged successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating user. Please try again.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes detected']);
    }

    $connection->close();
}
?>
