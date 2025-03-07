<?php
include('assets/databases/dbconfig.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $month = $_POST['month'];
    $year = $_POST['year'];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=transactions_' . $month . '_' . $year . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Type', 'Amount', 'Description']);

    $sql = "SELECT transaction_date AS date, 'Income' AS type, amount, description 
            FROM transactions 
            WHERE MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?
            UNION 
            SELECT expense_date AS date, 'Expense' AS type, amount, description 
            FROM employee_expenses 
            WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ?";

    $stmt = $connection->prepare($sql);
    $stmt->bind_param("iiii", $month, $year, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}
?>
