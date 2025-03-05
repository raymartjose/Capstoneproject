<?php
include 'assets/databases/dbconfig.php';

$sql = "SELECT id, customer_id, total_amount, payment_status, due_date FROM invoices";
$result = $connection->query($sql);

if ($result->num_rows > 0) {
    echo "
    <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer ID</th>
                    <th>Amount</th>
                    <th>Payment Status</th>
                    <th>Due Date</th>
                </tr>
            </thead>
            <tbody>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['id']) . "</td>
                <td>" . htmlspecialchars($row['customer_id']) . "</td>
                <td>₱" . number_format($row['total_amount'], 2) . "</td>
                <td>" . htmlspecialchars($row['payment_status']) . "</td>
                <td>" . htmlspecialchars($row['due_date']) . "</td>
            </tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>No data found.</p>";
}

$connection->close();
?>
