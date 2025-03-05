<?php
// tax_report.php
include('assets/databases/dbconfig.php');

// Fetch tax report
$query = "SELECT SUM(tax_amount) AS total_tax, COUNT(id) AS total_invoices
          FROM invoices
          WHERE issue_date BETWEEN '2024-01-01' AND '2024-12-31'";  // Adjust dates as necessary
$result = mysqli_query($connection, $query);
$row = mysqli_fetch_assoc($result);

echo "<h2>Tax Report</h2>";
echo "<p>Total Tax Applied: ₱" . number_format($row['total_tax'], 2) . "</p>";
echo "<p>Total Invoices with Tax: " . $row['total_invoices'] . "</p>";
?>


?>