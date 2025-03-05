<?php
session_start(); // Start the session to access $_SESSION variables
include('assets/databases/dbconfig.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get updated customer details
    $customer_id = $_POST['customer_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Check if the user is logged in and if $_SESSION['user_id'] is set
    if (!isset($_SESSION['user_id'])) {
        echo "Error: User not logged in.";
        exit;
    }

    // Fetch the current customer data before updating
    $query = "SELECT * FROM customers WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $oldData = $result->fetch_assoc(); // Fetch old data

    // Prepare new data
    $newData = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'address' => $address
    ];

    // Check if there are any changes
    $hasChanges = false;
    foreach ($newData as $key => $value) {
        if ($oldData[$key] !== $value) {
            $hasChanges = true;
            break;
        }
    }

    // If changes exist, update the customer and log the action
    if ($hasChanges) {
        // Update customer details in the database
        $updateQuery = "UPDATE customers SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $updateQuery);
        mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $phone, $address, $customer_id);

        if (mysqli_stmt_execute($stmt)) {
            // Audit log for the update
            $currentUserId = $_SESSION['user_id']; // Get the logged-in user's ID from the session

            // Prepare audit log data
            $action = "Updated";
            $recordType = "Customer";
            $recordId = $customer_id;
            $oldDataJson = json_encode($oldData); // Convert old data to JSON format
            $newDataJson = json_encode($newData); // Convert new data to JSON format

            // Insert the action into the audit logs
            $auditQuery = "INSERT INTO audit_logs (user_id, action, record_type, record_id, old_data, new_data) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $auditStmt = $connection->prepare($auditQuery);
            $auditStmt->bind_param("ississ", $currentUserId, $action, $recordType, $recordId, $oldDataJson, $newDataJson);
            $auditStmt->execute();

            // Redirect or show success message
            header("Location: administrator_dashboard.php?success=Customer+updated");
            exit;
        } else {
            // Error handling
            echo "Error updating customer details.";
        }

        mysqli_stmt_close($stmt);
    } else {
        // If no changes detected
        echo "No changes detected to update.";
    }
}
?>
