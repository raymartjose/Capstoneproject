<?php
// Include database connection
include('assets/databases/dbconfig.php');

if (isset($_GET['id'])) {
    $customer_id = $_GET['id'];

    // Query to get customer details
    $query = "SELECT * FROM customers WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        // Return customer data as JSON
        echo json_encode($row);
    } else {
        echo json_encode(["error" => "Customer not found"]);
    }

    mysqli_stmt_close($stmt);
}
?>
