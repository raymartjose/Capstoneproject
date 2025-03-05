<?php
include "assets/databases/dbconfig.php";

// Fetch previous month's income
$sql_previous_income = "SELECT SUM(amount) AS total_income FROM income WHERE MONTH(income_date) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(income_date) = YEAR(CURDATE())";
$result_previous_income = $connection->query($sql_previous_income);
$previous_income = $result_previous_income->fetch_assoc()['total_income'] ?? 0;

// Monthly goal can be fixed or from database, adjust as needed
$previous_goal = 1000000; // example goal

echo json_encode(['income' => $previous_income, 'goal' => $previous_goal]);
?>
