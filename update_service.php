<?php
include('assets/databases/dbconfig.php'); // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data from the POST request
    $id = $_POST['id']; // Service ID to identify which record to update
    $name = mysqli_real_escape_string($connection, $_POST['name']); // Sanitize service name
    $description = mysqli_real_escape_string($connection, $_POST['description']); // Sanitize description
    $price = $_POST['price']; // Get the price

    // Validation (optional, you can add more validation here)
    if (empty($name) || empty($description) || empty($price)) {
        echo "All fields are required!";
        exit;
    }

    // Update query to modify the service in the database
    $updateQuery = "UPDATE additional_services 
                    SET name = '$name', description = '$description', price = '$price' 
                    WHERE id = '$id'";

    // Execute the query
    if (mysqli_query($connection, $updateQuery)) {
        // If the query was successful, redirect or send success response
        echo "Service updated successfully!";
        header("Location: add_product_services.php"); // Replace with the actual page to redirect after success
        exit;
    } else {
        // Handle error if the query fails
        echo "Error: " . mysqli_error($connection);
    }
}
?>
