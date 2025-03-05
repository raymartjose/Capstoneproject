<?php
// Include database connection
include('assets/databases/dbconfig.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get updated customer details
    $customer_id = $_POST['customer_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Input validation
    if (empty($name) || empty($email) || empty($phone) || empty($address)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit;
    }

    // Fetch old data for audit logs
    $oldDataQuery = "SELECT * FROM customers WHERE id = ?";
    $stmt = $connection->prepare($oldDataQuery);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $oldData = $result->fetch_assoc();

    if (!$oldData) {
        echo json_encode(['success' => false, 'message' => 'Customer not found.']);
        exit;
    }

    // Update customer details in the database
    $query = "UPDATE customers SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ssssi", $name, $email, $phone, $address, $customer_id);

    if ($stmt->execute()) {
        // Log changes to audit logs
        $currentUserId = $_SESSION['user_id']; // Assuming logged-in user ID
        $action = "Edited";
        $recordType = "Customer";
        $recordId = $customer_id;
        $newData = json_encode(['name' => $name, 'email' => $email, 'phone' => $phone, 'address' => $address]);
        $oldDataJson = json_encode($oldData);

        $auditQuery = "INSERT INTO audit_logs (user_id, action, record_type, record_id, old_data, new_data) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $auditStmt = $connection->prepare($auditQuery);
        $auditStmt->bind_param("ississ", $currentUserId, $action, $recordType, $recordId, $oldDataJson, $newData);
        $auditStmt->execute();

        echo json_encode(['success' => true, 'message' => 'Customer updated successfully.']);
    } else {
        // Handle query execution errors
        echo json_encode(['success' => false, 'message' => 'Error updating customer details.']);
    }

    $stmt->close();
    $connection->close();
}
?>
