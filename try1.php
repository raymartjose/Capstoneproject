<?php
include('assets/databases/dbconfig.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Sheet</title>
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Include your CSS -->
</head>
<body>

<h2>Balance Sheet</h2>
<table border="1">
    <thead>
        <tr>
            <th>Account Name</th>
            <th>Category</th>
            <th>Balance</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Fetch total income
        $income_query = "SELECT SUM(amount) AS total_income FROM transactions";
        $income_result = mysqli_query($connection, $income_query);
        $income_row = mysqli_fetch_assoc($income_result);
        $total_income = $income_row['total_income'] ?? 0;

        // Fetch total salaries (net pay)
        $payroll_query = "SELECT SUM(net_pay) AS total_salaries FROM payroll";
        $payroll_result = mysqli_query($connection, $payroll_query);
        $payroll_row = mysqli_fetch_assoc($payroll_result);
        $total_salaries = $payroll_row['total_salaries'] ?? 0;

        // Fetch total employee expenses
        $expenses_query = "SELECT SUM(amount) AS total_expenses FROM employee_expenses";
        $expenses_result = mysqli_query($connection, $expenses_query);
        $expenses_row = mysqli_fetch_assoc($expenses_result);
        $total_expenses = $expenses_row['total_expenses'] ?? 0;

        // Fetch total COGS
        $cogs_query = "SELECT SUM(total_cogs) AS total_cogs FROM cogs";
        $cogs_result = mysqli_query($connection, $cogs_query);
        $cogs_row = mysqli_fetch_assoc($cogs_result);
        $total_cogs = $cogs_row['total_cogs'] ?? 0;

        // Compute updated Cash balance
        $cash_balance = $total_income - $total_salaries - $total_expenses - $total_cogs;

        // Fetch Accounts Receivable (total unpaid invoices)
        $ar_query = "SELECT SUM(total_amount) AS total_ar FROM invoices WHERE payment_status = 'paid'";
        $ar_result = mysqli_query($connection, $ar_query);
        $ar_row = mysqli_fetch_assoc($ar_result);
        $total_ar = $ar_row['total_ar'] ?? 0;

        // Fetch total assets
        $assets_query = "SELECT SUM(value) AS total_assets FROM assets";
        $assets_result = mysqli_query($connection, $assets_query);
        $assets_row = mysqli_fetch_assoc($assets_result);
        $total_assets = $assets_row['total_assets'] ?? 0;

        // Fetch total liabilities
        $liabilities_query = "SELECT SUM(amount) AS total_liabilities FROM liabilities";
        $liabilities_result = mysqli_query($connection, $liabilities_query);
        $liabilities_row = mysqli_fetch_assoc($liabilities_result);
        $total_liabilities = $liabilities_row['total_liabilities'] ?? 0;

        // Compute Stockholders' Equity
        $stockholders_equity = $total_assets - $total_liabilities;

        // Display Assets
        echo "<tr><td colspan='3'><strong>ASSETS</strong></td></tr>";
        echo "<tr><td>Cash</td><td>Asset</td><td>" . number_format($cash_balance, 2) . "</td></tr>";
        echo "<tr><td>Accounts Receivable</td><td>Asset</td><td>" . number_format($total_ar, 2) . "</td></tr>";
        echo "<tr><td>Other Assets</td><td>Asset</td><td>" . number_format($total_assets, 2) . "</td></tr>";

        // Display Liabilities
        echo "<tr><td colspan='3'><strong>LIABILITIES</strong></td></tr>";
        echo "<tr><td>Total Liabilities</td><td>Liability</td><td>" . number_format($total_liabilities, 2) . "</td></tr>";

        // Display Stockholders' Equity
        echo "<tr><td colspan='3'><strong>STOCKHOLDERS' EQUITY</strong></td></tr>";
        echo "<tr><td>Equity</td><td>Equity</td><td>" . number_format($stockholders_equity, 2) . "</td></tr>";
        ?>
    </tbody>
</table>

</body>
</html>
