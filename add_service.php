<?php
// Include database connection
include('assets/databases/dbconfig.php');

// Start the session
session_start();

// Check if the user is logged in and user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    // Handle the case where the user is not logged in (e.g., redirect to login page)
    echo "You need to be logged in to perform this action.";
    exit; // Stop the execution of the script
}

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];

    // Prepare SQL query to insert data into additional_services table
    $sql = "INSERT INTO additional_services (name, description, price) 
            VALUES (?, ?, ?)";

    // Prepare statement
    if ($stmt = $connection->prepare($sql)) {
        // Bind parameters to the SQL query
        $stmt->bind_param("ssd", $name, $description, $price);

        // Execute the statement
        if ($stmt->execute()) {
            // Service added successfully, now log the action

            // Get current logged-in user ID
            $currentUserId = $_SESSION['user_id']; // Logged-in user ID

            // Prepare audit log data
            $action = "Added";
            $recordType = "Service";
            $recordId = $stmt->insert_id; // Get the ID of the newly added service
            $oldDataJson = json_encode([]); // No previous data since it's an add action
            $newData = [
                'name' => $name,
                'description' => $description,
                'price' => $price
            ];
            $newDataJson = json_encode($newData); // Convert new data to JSON format

            // Insert the action into the audit logs
            $auditQuery = "INSERT INTO audit_logs (user_id, action, record_type, record_id, old_data, new_data) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $auditStmt = $connection->prepare($auditQuery);
            $auditStmt->bind_param("ississ", $currentUserId, $action, $recordType, $recordId, $oldDataJson, $newDataJson);
            $auditStmt->execute();

            // Success message
            echo "Service added successfully!";
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    }

    // Close the database connection
    $connection->close();
}
?>
