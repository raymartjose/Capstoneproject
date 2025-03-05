<?php
include "assets/databases/dbconfig.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['department'])) {
    $department = mysqli_real_escape_string($connection, $_POST['department']);

    $query = "SELECT employee_id, name, position FROM employees WHERE department = '$department' ORDER BY name ASC";
    $result = mysqli_query($connection, $query);

    $employees = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $employees[] = $row;
    }

    echo json_encode($employees);
    exit;
}
?>
