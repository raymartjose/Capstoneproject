<?php
include "assets/databases/dbconfig.php";

if (isset($_GET['department'])) {
    $department = $_GET['department'];
    
    $query = "SELECT id, name FROM employees WHERE department = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $department);
    $stmt->execute();
    $result = $stmt->get_result();

    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }

    echo json_encode($employees);
}
?>
