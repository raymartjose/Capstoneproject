<?php
// Include database connection
include "assets/databases/dbconfig.php";

$query = "SELECT role, COUNT(*) as total FROM users GROUP BY role";
$result = $connection->query($query);

$role_counts = ['staff' => 0, 'administrator' => 0, 'super_admin' => 0];
while ($row = $result->fetch_assoc()) {
    $role_counts[$row['role']] = $row['total'];
}

$connection->close();
?>
