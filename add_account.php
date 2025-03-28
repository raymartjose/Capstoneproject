<?php
include "assets/databases/dbconfig.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account_code = trim($_POST['account_code']);
    $account_name = trim($_POST['account_name']);
    $category = trim($_POST['category']);

    // Validate inputs
    if (empty($account_code) || empty($account_name) || empty($category)) {
        die("Error: All fields are required!");
    }

    // Use prepared statements
    $stmt = $connection->prepare("INSERT INTO chart_of_accounts (account_code, account_name, category) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $account_code, $account_name, $category);

    if ($stmt->execute()) {
        echo "Account successfully added!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

?>
