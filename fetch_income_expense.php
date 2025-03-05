<?php
// Fetch total income
function getTotalIncome() {
    global $connection;
    $query = "SELECT SUM(amount) AS total_income FROM transactions";  // Adjust table name and column as per your database
    $result = mysqli_query($connection, $query);

    if ($result) {
        $data = mysqli_fetch_assoc($result);
        return $data['total_income'];
    }
    return 0;
}

// Fetch total expense
function getTotalExpense() {
    global $connection;
    $query = "SELECT SUM(amount) AS total_expense FROM expense_approved";  // Adjust table name and column as per your database
    $result = mysqli_query($connection, $query);

    if ($result) {
        $data = mysqli_fetch_assoc($result);
        return $data['total_expense'];
    }
    return 0;
}

// Fetch total income and expense
$totalIncome = getTotalIncome();
$totalExpense = getTotalExpense();
?>
