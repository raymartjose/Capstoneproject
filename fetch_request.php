<?php
include ('assets/databases/dbconfig.php');


// Get pending budget request count
$returned_budget_query = "SELECT COUNT(*) as pending_budget_count FROM budget_requests WHERE status = 'Returned'";
$returned_budget_result = $connection->query($returned_budget_query);
$returned_budget_count = $returned_budget_result->fetch_assoc()['pending_budget_count'];

// Get pending expense request count
$returned_expense_query = "SELECT COUNT(*) as pending_expense_count FROM expense WHERE status = 'Returned'";
$returned_expense_result = $connection->query($returned_expense_query);
$returned_expense_count = $returned_expense_result->fetch_assoc()['pending_expense_count'];
?>
