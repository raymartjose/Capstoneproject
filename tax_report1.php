<?php
include('assets/databases/dbconfig.php');

// Fetch tax report
$query = "
    SELECT t.name AS tax_type, 
           SUM(i.tax_amount) AS total_tax, 
           COUNT(i.id) AS total_invoices 
    FROM invoices i
    JOIN taxes t ON i.tax_id = t.id
    WHERE i.issue_date BETWEEN '2024-01-01' AND '2024-12-31'  -- Adjust dates as necessary
    GROUP BY t.name";

$result = mysqli_query($connection, $query);

echo "<h2>Tax Report - Trucking Rental Business</h2>";
echo "<table>
        <thead>
            <tr>
                <th>Tax Type</th>
                <th>Total Tax Applied</th>
                <th>Total Invoices</th>
            </tr>
        </thead>
        <tbody>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>
            <td>" . htmlspecialchars($row['tax_type']) . "</td>
            <td>₱" . number_format($row['total_tax'], 2) . "</td>
            <td>" . $row['total_invoices'] . "</td>
        </tr>";
}

echo "</tbody></table>";
?>
