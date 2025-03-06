<?php
include('assets/databases/dbconfig.php');

// Fetch top 10 customers with the highest unpaid invoices
$sql = "SELECT 
            c.name AS customer_name, 
            SUM(i.total_amount) AS unpaid_amount
        FROM invoices i
        JOIN customers c ON i.customer_id = c.id
        WHERE i.payment_status IN ('pending', 'overdue')
        GROUP BY c.id
        ORDER BY unpaid_amount DESC
        LIMIT 10"; // Fetch only the top 10 customers

$result = $connection->query($sql);

$customers = [];
$amounts = [];
$totalUnpaid = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row['customer_name'];
        $amounts[] = $row['unpaid_amount'];
        $totalUnpaid += $row['unpaid_amount']; // Calculate total unpaid for pie chart
    }
}

// Prepare data for pie chart (percentage of unpaid per customer)
$percentages = [];
if ($totalUnpaid > 0) {
    foreach ($amounts as $amount) {
        $percentages[] = round(($amount / $totalUnpaid) * 100, 1);
    }
}

// JSON output
echo json_encode([
    'customers' => $customers,
    'amounts' => $amounts,
    'percentages' => $percentages
]);
?>
