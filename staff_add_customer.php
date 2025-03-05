<?php
include('assets/databases/dbconfig.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $address = mysqli_real_escape_string($connection, $_POST['address']);

    // Insert query for adding a new customer
    $query = "INSERT INTO customers (name, email, phone, address) VALUES ('$name', '$email', '$phone', '$address')";
    
    if (mysqli_query($connection, $query)) {
        // If the customer is added successfully, log the action in the audit logs
        $currentUserId = $_SESSION['user_id']; // Assuming the user is logged in and their ID is stored in session
        
        // Prepare data for audit logging
        $action = "Added";
        $recordType = "Customer";
        $recordId = mysqli_insert_id($connection); // Get the last inserted ID (new customer ID)
        $newData = json_encode([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address
        ]);

        // Prepare NULL variable for old_data (as it’s a new record)
        $oldData = NULL;

        // Audit log query
        $auditQuery = "INSERT INTO audit_logs (user_id, action, record_type, record_id, old_data, new_data) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($auditQuery);
        $stmt->bind_param("ississ", $currentUserId, $action, $recordType, $recordId, $oldData, $newData);
        $stmt->execute();

        // Redirect with success message
        header("Location: staff_dashboard.php?success=Customer+added");
        exit;
    } else {
        // Handle any errors in inserting the customer
        echo "Error: " . mysqli_error($connection);
    }
}
?>
