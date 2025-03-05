<?php
include "assets/databases/dbconfig.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account_code = $_POST['account_code'];
    $account_name = $_POST['account_name'];
    $category = $_POST['category'];
    $description = $_POST['description'];

    $sql = "INSERT INTO chart_of_accounts (account_code, account_name, category, description) 
            VALUES ('$account_code', '$account_name', '$category', '$description')";

    if (mysqli_query($connection, $sql)) {
        echo "Account successfully added!";
    } else {
        echo "Error: " . mysqli_error($connection);
    }
}
?>
