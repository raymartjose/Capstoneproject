<?php
include('assets/databases/dbconfig.php');

// Fetch top 10 customers with the highest unpaid invoices
$sql = "SELECT 
            c.name AS customer_name, 
            SUM(i.total_amount) AS paid_amount
        FROM invoices i
        JOIN customers c ON i.customer_id = c.id
        WHERE i.payment_status IN ('paid')
        GROUP BY c.id
        ORDER BY paid_amount DESC
        LIMIT 10"; // Fetch only the top 10 customers

$result = $connection->query($sql);

$paidcustomers = [];
$paidamounts = [];
$totalpaid = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $paidcustomers[] = $row['customer_name'];
        $paidamounts[] = $row['paid_amount'];
        $totalpaid += $row['paid_amount']; // Calculate total unpaid for pie chart
    }
}

// Prepare data for pie chart (percentage of unpaid per customer)
$paidpercentages = [];
if ($totalpaid > 0) {
    foreach ($paidamounts as $paidamount) {
        $paidpercentages[] = round(($paidamount / $totalpaid) * 100, 1);
    }
}

// JSON output
echo json_encode([
    'paidcustomers' => $paidcustomers,
    'paidamounts' => $paidamounts,
    'paidpercentages' => $paidpercentages
]);
?>
