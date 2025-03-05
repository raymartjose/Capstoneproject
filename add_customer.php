<?php
include('assets/databases/dbconfig.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $address = mysqli_real_escape_string($connection, $_POST['address']);

    $query = "INSERT INTO customers (name, email, phone, address) VALUES ('$name', '$email', '$phone', '$address')";
    if (mysqli_query($connection, $query)) {
        header("Location: administrator_dashboard.php?success=Customer+added");
    } else {
        echo "Error: " . mysqli_error($connection);
    }
}
?>
