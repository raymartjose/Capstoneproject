<?php
include('assets/databases/dbconfig.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST['action'] == 'add_asset') {
        $type = $_POST['type'];
        $value = $_POST['value'];
        $stmt = $connection->prepare("INSERT INTO assets (type, value, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("sd", $type, $value);
        $stmt->execute();
    }

    if ($_POST['action'] == 'add_liability') {
        $liability_name = $_POST['liability_name'];
        $amount = $_POST['amount'];
        $stmt = $connection->prepare("INSERT INTO liabilities (liability_name, amount, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("sd", $liability_name, $amount);
        $stmt->execute();
    }
}
?>
