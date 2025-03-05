<?php
include "assets/databases/dbconfig.php";

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Fetch expenses with categories
$expenseQuery = "SELECT e.expense_date, SUM(e.amount) as total_expenses
                 FROM expense e
                 GROUP BY MONTH(e.expense_date), YEAR(e.expense_date)";
$expenseResult = $connection->query($expenseQuery);

// Fetch income with categories
$incomeQuery = "SELECT i.income_date, SUM(i.amount) as total_income
                FROM income i
                GROUP BY MONTH(i.income_date), YEAR(i.income_date)";
$incomeResult = $connection->query($incomeQuery);

// Initialize arrays to store data
$expenseData = [];
$incomeData = [];

// Process expense data
while ($row = $expenseResult->fetch_assoc()) {
    $expenseData[] = $row['total_expenses'];
}

// Process income data
while ($row = $incomeResult->fetch_assoc()) {
    $incomeData[] = $row['total_income'];
}

// Output the JSON response
header('Content-Type: application/json');
echo json_encode([
    'income' => $incomeData,
    'expenses' => $expenseData
]);
?>
