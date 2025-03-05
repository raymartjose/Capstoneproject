<?php
// Database connection
include ('assets/databases/dbconfig.php');

// Fetch total rejected requests from `expense` and `budget_requests` tables
$rejectedExpenses = $connection->query("SELECT COUNT(*) AS count FROM expense WHERE status = 'Rejected'")->fetch_assoc()['count'];
$rejectedBudgetRequests = $connection->query("SELECT COUNT(*) AS count FROM budget_requests WHERE status = 'Rejected'")->fetch_assoc()['count'];
$totalRejectedRequests = $rejectedExpenses + $rejectedBudgetRequests;

// Fetch total approved requests from `expense_approved` and `budget_approved` tables
$approvedExpenses = $connection->query("SELECT COUNT(*) AS count FROM expense_approved")->fetch_assoc()['count'];
$approvedBudgetRequests = $connection->query("SELECT COUNT(*) AS count FROM budget_approved")->fetch_assoc()['count'];
$totalApprovedRequests = $approvedExpenses + $approvedBudgetRequests;
?>
