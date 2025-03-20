<?php
// Include database connection
include('assets/databases/dbconfig.php');

// Fetch total receivables
$totalPaidQuery = "SELECT SUM(total_amount) AS total FROM invoices WHERE payment_status = 'paid'";
$totalPaidResult = mysqli_query($connection, $totalPaidQuery);
$totalPaidRow = mysqli_fetch_assoc($totalPaidResult);
$totalPaid = $totalPaidRow['total'] ?? 0;

// Fetch past due %
$paidQuery = "SELECT 
                COUNT(*) AS paid_count, 
                (SELECT COUNT(*) FROM invoices) AS total_count 
              FROM invoices 
              WHERE payment_status = 'paid'";
$paidResult = mysqli_query($connection, $paidQuery);
$paidRow = mysqli_fetch_assoc($paidResult);
$paidPercent = ($paidRow['total_count'] > 0) ? ($paidRow['paid_count'] / $paidRow['total_count']) * 100 : 0;

// Fetch over 90 days receivables
$totalQuery = "SELECT COUNT(*) AS total_inv FROM invoices WHERE payment_status = 'paid'";
$totalResult = mysqli_query($connection, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$total = $totalRow['total_inv'] ?? 0;

// Return JSON response
echo json_encode([
    'totalPaid' => number_format($totalPaid, 2), // ✅ Now matches JavaScript
    'paidPercent' => number_format($paidPercent, 2),
    'total' => number_format($total),
]);
?>
