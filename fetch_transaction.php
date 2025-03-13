<?php
include "assets/databases/dbconfig.php";

if (isset($_GET['date'])) {
    $date = $_GET['date'];
    $query = "SELECT 
                i.payment_date AS date, 
                'Income' AS type, 
                i.total_amount AS amount, 
                CONCAT('Payment from ', c.name) AS description
              FROM invoices i
              JOIN customers c ON i.customer_id = c.id
              WHERE i.payment_date = ?
              UNION 
              SELECT 
                e.expense_date AS date, 
                'Expense' AS type, 
                e.amount, 
                e.description 
              FROM employee_expenses e
              WHERE e.expense_date = ?";

    $stmt = $connection->prepare($query);
    $stmt->bind_param("ss", $date, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['date']}</td>
                    <td>{$row['type']}</td>
                    <td>₱" . number_format($row['amount'], 2) . "</td>
                    <td>{$row['description']}</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='4'>No transactions found for this date.</td></tr>";
    }
} else {
    echo "<tr><td colspan='4'>Invalid request.</td></tr>";
}
?>
