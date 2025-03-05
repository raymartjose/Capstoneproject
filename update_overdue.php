<?php
include('assets/databases/dbconfig.php');

$query = "SELECT COUNT(*) AS overdue_count FROM invoices WHERE payment_status = 'overdue'";
$result = mysqli_query($connection, $query);
$row = mysqli_fetch_assoc($result);

echo $row['overdue_count'];
?>
