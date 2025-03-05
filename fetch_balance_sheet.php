<?php
include('assets/databases/dbconfig.php');

$currentYear = date("Y");
$previousYear = $currentYear - 1;

$query = "SELECT YEAR(created_at) AS year, 'Assets' AS category, type AS subcategory, SUM(value) AS total_amount 
          FROM assets 
          GROUP BY YEAR(created_at), type
          UNION ALL 
          SELECT YEAR(created_at), 'Liabilities', liability_name, SUM(amount) 
          FROM liabilities 
          GROUP BY YEAR(created_at), liability_name
          ORDER BY category ASC, subcategory ASC, year DESC";

$result = $connection->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[$row['category']][$row['subcategory']][$row['year']] = $row['total_amount'];
}

// Fetch cash separately from transactions and subtract expenses and payroll
$cashQuery = "SELECT YEAR(created_at) AS year, SUM(amount) AS total_income 
              FROM transactions 
              WHERE type = 'income' 
              GROUP BY YEAR(created_at)";
$cashResult = $connection->query($cashQuery);

while ($cashRow = $cashResult->fetch_assoc()) {
    $data['Assets']['Cash'][$cashRow['year']] = $cashRow['total_income'];
}

// Fetch employee expenses for the current year
$expenseQuery = "SELECT YEAR(expense_date) AS year, SUM(amount) AS total_expense 
                 FROM employee_expenses
                 GROUP BY YEAR(expense_date)";
$expenseResult = $connection->query($expenseQuery);

while ($expenseRow = $expenseResult->fetch_assoc()) {
    if (isset($data['Assets']['Cash'][$expenseRow['year']])) {
        $data['Assets']['Cash'][$expenseRow['year']] -= $expenseRow['total_expense'];
    }
}

// Fetch payroll expenses for the current year
$payrollQuery = "SELECT YEAR(processed_at) AS year, SUM(net_pay) AS total_payroll 
                 FROM payroll 
                 GROUP BY YEAR(processed_at)";
$payrollResult = $connection->query($payrollQuery);

while ($payrollRow = $payrollResult->fetch_assoc()) {
    if (isset($data['Assets']['Cash'][$payrollRow['year']])) {
        $data['Assets']['Cash'][$payrollRow['year']] -= $payrollRow['total_payroll'];
    }
}

// Insert or update "Cash" in the chart_of_accounts
$cash_on_hand = $data['Assets']['Cash'][$currentYear] ?? 0.00;

$checkQuery = "SELECT * FROM chart_of_accounts WHERE account_name = 'Cash'";
$checkResult = $connection->query($checkQuery);

if ($checkResult->num_rows > 0) {
    // Update existing Cash balance
    $updateQuery = "UPDATE chart_of_accounts SET balance = '$cash_on_hand' WHERE account_name = 'Cash'";
    $connection->query($updateQuery);
} else {
    // Insert new record for Cash
    $insertQuery = "INSERT INTO chart_of_accounts (account_name, category, balance) VALUES ('Cash', 'Assets', '$cash_on_hand')";
    $connection->query($insertQuery);
}

// Display the balance sheet
foreach ($data as $category => $subcategories) {
    echo "<tr class='bg-gray-200'><td class='border border-gray-300 p-2 font-bold' colspan='4'>$category</td></tr>";
    foreach ($subcategories as $subcategory => $years) {
        echo "<tr>";
        echo "<td class='border border-gray-300 p-2'></td>";
        echo "<td class='border border-gray-300 p-2'>$subcategory</td>";
        echo "<td class='border border-gray-300 p-2 text-right'>" . number_format($years[$currentYear] ?? 0, 2) . "</td>";
        echo "<td class='border border-gray-300 p-2 text-right'>" . number_format($years[$previousYear] ?? 0, 2) . "</td>";
        echo "</tr>";
    }
}
?>
