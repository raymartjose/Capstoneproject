<?php
include "assets/databases/dbconfig.php";

$sql = "SELECT id, request_type, staff_name, department, created_at 
        FROM requests 
        WHERE status = 'Pending' 
        ORDER BY created_at DESC";
$result = $connection->query($sql);

$notifications = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}

echo json_encode($notifications);
$connection->close();
?>
