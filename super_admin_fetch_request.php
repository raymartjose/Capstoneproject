<?php
include ('assets/databases/dbconfig.php');


// Get pending budget request count
$pending_budget_query = "SELECT COUNT(*) as pending_budget_count FROM budget_requests WHERE status = 'Pending'";
$pending_budget_result = $connection->query($pending_budget_query);
$pending_budget_count = $pending_budget_result->fetch_assoc()['pending_budget_count'];

// Get pending expense request count
$pending_expense_query = "SELECT COUNT(*) as pending_expense_count FROM expense WHERE status = 'Pending'";
$pending_expense_result = $connection->query($pending_expense_query);
$pending_expense_count = $pending_expense_result->fetch_assoc()['pending_expense_count'];
?>
