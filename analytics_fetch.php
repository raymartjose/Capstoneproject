<?php
include('assets/databases/dbconfig.php');

$response = [
    "paid" => 0,
    "overdue" => 0,
    "pending" => 0,
    "months" => [],
    "paidMonthly" => [],
    "overdueMonthly" => [],
    "unpaidMonthly" => []
];

// Fetch Total Paid, Overdue, and Unpaid (for KPI Cards)
$paidQuery = "SELECT SUM(total_amount) AS total FROM invoices WHERE payment_status = 'Paid'";
$paidResult = $connection->query($paidQuery);
if ($paidRow = $paidResult->fetch_assoc()) {
    $response["paid"] = $paidRow["total"] ?? 0;
}

$overdueQuery = "SELECT SUM(total_amount) AS total FROM invoices WHERE payment_status = 'Overdue'";
$overdueResult = $connection->query($overdueQuery);
if ($overdueRow = $overdueResult->fetch_assoc()) {
    $response["overdue"] = $overdueRow["total"] ?? 0;
}

$unpaidQuery = "SELECT SUM(total_amount) AS total FROM invoices WHERE payment_status = 'Unpaid'";
$unpaidResult = $connection->query($unpaidQuery);
if ($unpaidRow = $unpaidResult->fetch_assoc()) {
    $response["pending"] = $unpaidRow["total"] ?? 0;
}

// Fetch Monthly Data ensuring all categories are included
$monthlyQuery = "
    SELECT 
        DATE_FORMAT(months.date, '%b') AS month_name, 
        MONTH(months.date) AS month_number,
        COALESCE(SUM(CASE WHEN invoices.payment_status = 'Paid' THEN invoices.total_amount ELSE 0 END), 0) AS paid,
        COALESCE(SUM(CASE WHEN invoices.payment_status = 'Overdue' THEN invoices.total_amount ELSE 0 END), 0) AS overdue,
        COALESCE(SUM(CASE WHEN invoices.payment_status = 'Unpaid' THEN invoices.total_amount ELSE 0 END), 0) AS unpaid
    FROM 
        (SELECT DATE_FORMAT(CONCAT(YEAR(CURDATE()), '-', m, '-01'), '%Y-%m-%d') AS date FROM (SELECT 1 AS m UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) AS months) AS months
    LEFT JOIN invoices 
        ON MONTH(invoices.payment_date) = MONTH(months.date) 
        OR MONTH(invoices.due_date) = MONTH(months.date) 
        OR MONTH(invoices.issue_date) = MONTH(months.date)
    WHERE YEAR(months.date) = YEAR(CURDATE())
    GROUP BY month_name, month_number
    ORDER BY month_number ASC
";


$monthlyResult = $connection->query($monthlyQuery);

while ($row = $monthlyResult->fetch_assoc()) {
    $response["months"][] = $row["month_name"];
    $response["paidMonthly"][] = $row["paid"] ?? 0;
    $response["overdueMonthly"][] = $row["overdue"] ?? 0;
    $response["unpaidMonthly"][] = $row["unpaid"] ?? 0;
}

echo json_encode($response);
?>
