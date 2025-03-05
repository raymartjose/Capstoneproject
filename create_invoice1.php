<?php
include('assets/databases/dbconfig.php');

$sql = "SELECT invoices.*, contracts.company_name, contracts.client_name, contracts.rental_period 
        FROM invoices 
        JOIN contracts ON invoices.contract_id = contracts.contract_id";
$result = $connection->query($sql);

echo "<table border='1'>
<tr>
    <th>Invoice ID</th>
    <th>Client Name</th>
    <th>Product</th>
    <th>Amount</th>
    <th>Due Date</th>
    <th>Payment Status</th>
    <th>Rental Period</th>
</tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
        <td>{$row['id']}</td>
        <td>{$row['client_name']}</td>
        <td>{$row['product_name']}</td>
        <td>{$row['total_amount']}</td>
        <td>{$row['due_date']}</td>
        <td>{$row['payment_status']}</td>
        <td>{$row['rental_period']} days</td>
    </tr>";
}

echo "</table>";
$connection->close();
?>