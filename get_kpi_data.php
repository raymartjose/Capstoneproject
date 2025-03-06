<?php
// Include database connection
include('assets/databases/dbconfig.php');

// Fetch total receivables
$totalReceivablesQuery = "SELECT SUM(total_amount) AS total FROM invoices WHERE payment_status != 'paid'";
$totalReceivablesResult = mysqli_query($connection, $totalReceivablesQuery);
$totalReceivablesRow = mysqli_fetch_assoc($totalReceivablesResult);
$totalReceivables = $totalReceivablesRow['total'] ?? 0;

// Fetch past due %
$pastDueQuery = "SELECT COUNT(*) AS overdue_count, (SELECT COUNT(*) FROM invoices) AS total_count FROM invoices WHERE due_date < CURDATE() AND payment_status != 'paid'";
$pastDueResult = mysqli_query($connection, $pastDueQuery);
$pastDueRow = mysqli_fetch_assoc($pastDueResult);
$pastDuePercent = ($pastDueRow['total_count'] > 0) ? ($pastDueRow['overdue_count'] / $pastDueRow['total_count']) * 100 : 0;

// Fetch over 90 days receivables
$over90Query = "SELECT SUM(total_amount) AS over_90 FROM invoices WHERE due_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY) AND payment_status != 'paid'";
$over90Result = mysqli_query($connection, $over90Query);
$over90Row = mysqli_fetch_assoc($over90Result);
$over90 = $over90Row['over_90'] ?? 0;

// Return JSON response
echo json_encode([
    'totalReceivables' => number_format($totalReceivables, 2),
    'pastDuePercent' => number_format($pastDuePercent, 2),
    'over90' => number_format($over90, 2),
]);
?>
