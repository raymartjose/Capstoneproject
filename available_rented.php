<?php
// Include database connection
include('assets/databases/dbconfig.php');

// Query to count available products
$availableQuery = "SELECT COUNT(*) AS total_available FROM products WHERE status = 'available'";
$availableResult = mysqli_query($connection, $availableQuery);
$availableData = mysqli_fetch_assoc($availableResult);
$totalAvailable = $availableData['total_available'];

// Query to count rented products
$rentedQuery = "SELECT COUNT(*) AS total_rented FROM products WHERE status = 'rented'";
$rentedResult = mysqli_query($connection, $rentedQuery);
$rentedData = mysqli_fetch_assoc($rentedResult);
$totalRented = $rentedData['total_rented'];
?>