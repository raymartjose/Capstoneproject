<?php
include('assets/databases/dbconfig.php'); // Include your database connection
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data from the POST request
    $id = $_POST['id']; // Service ID to identify which record to update
    $name = $_POST['name']; 
    $description = $_POST['description']; 
    $price = $_POST['price']; 

    // Validation
    if (empty($name) || empty($description) || empty($price)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required!']);
        exit;
    }

    // Fetch the old data for audit logs
    $oldDataQuery = "SELECT * FROM additional_services WHERE id = ?";
    $stmt = $connection->prepare($oldDataQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $oldData = $result->fetch_assoc();

    if (!$oldData) {
        echo json_encode(['success' => false, 'message' => 'Service not found!']);
        exit;
    }

    // Update query to modify the service in the database
    $updateQuery = "UPDATE additional_services 
                    SET name = ?, description = ?, price = ? 
                    WHERE id = ?";
    $stmt = $connection->prepare($updateQuery);
    $stmt->bind_param("ssdi", $name, $description, $price, $id);

    if ($stmt->execute()) {
        // If update was successful, log the action in audit logs
        $currentUserId = $_SESSION['user_id']; // Logged-in user ID
        $action = "Edited";
        $recordType = "Service";
        $recordId = $id;
        $newData = json_encode(['name' => $name, 'description' => $description, 'price' => $price]);
        $oldDataJson = json_encode($oldData);

        $auditQuery = "INSERT INTO audit_logs (user_id, action, record_type, record_id, old_data, new_data) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $auditStmt = $connection->prepare($auditQuery);
        $auditStmt->bind_param("ississ", $currentUserId, $action, $recordType, $recordId, $oldDataJson, $newData);
        $auditStmt->execute();

        echo json_encode(['success' => true, 'message' => 'Service updated successfully!']);
    } else {
        // Handle error if the update query fails
        echo json_encode(['success' => false, 'message' => 'Error updating service. Please try again.']);
    }

    $stmt->close();
    $connection->close();
}
?>
