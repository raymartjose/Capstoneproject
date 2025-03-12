<!DOCTYPE html>
<html lang="en">
    <?php
    session_start();  // Start the session
    ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="icon" href="img/logo-sm.png">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/request_form.css">
    <link rel= "stylesheet" href= "https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css" >
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    
</head>
<body>


<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['name'];  // User name from session
$user_role = $_SESSION['role_display'];  // User role from session

include "assets/databases/dbconfig.php";

// Determine the selected month and year
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Format the selected month
$currentMonth = sprintf("%04d-%02d", $year, $month);

// Get total sales goal for the selected month
$goalQuery = "SELECT SUM(goal_amount) AS total_goal FROM income_goals WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?";
$goalStmt = $connection->prepare($goalQuery);
$goalStmt->bind_param("ii", $month, $year);
$goalStmt->execute();
$goalResult = $goalStmt->get_result();
$goalRow = $goalResult->fetch_assoc();
$totalGoal = $goalRow['total_goal'] ?? 0;

// Get sales data for the selected month
$incomeQuery = "SELECT DATE_FORMAT(transaction_date, '%Y-%m-%d') AS date, SUM(amount) AS total_income 
                FROM transactions 
                WHERE MONTH(transaction_date) = ? AND YEAR(transaction_date) = ? 
                GROUP BY date";
$incomeStmt = $connection->prepare($incomeQuery);
$incomeStmt->bind_param("ii", $month, $year);
$incomeStmt->execute();
$incomeResult = $incomeStmt->get_result();

$actualIncome = [];
while ($row = $incomeResult->fetch_assoc()) {
    $actualIncome[$row['date']] = $row['total_income'];
}

// Get number of days and first weekday of the selected month
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$firstDayOfMonth = date('w', strtotime("$year-$month-01"));

// Calculate previous and next months
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth == 0) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth == 13) {
    $nextMonth = 1;
    $nextYear++;
}



// New query to get income by payment method from the invoices table
// New query to get income by payment method from the transactions table
$paymentMethodQuery = "SELECT payment_method, SUM(amount) AS total_income
                       FROM transactions
                       WHERE DATE_FORMAT(transaction_date, '%Y-%m') = '$currentMonth'
                       GROUP BY payment_method";

$paymentMethodResult = $connection->query($paymentMethodQuery);

$paymentMethods = [];
while ($row = $paymentMethodResult->fetch_assoc()) {
    $paymentMethods[$row['payment_method']] = $row['total_income'];
}


$customerQuery = "SELECT 
    c.id AS customer_id,
    c.name AS customer_name,
    c.email,
    SUM(t.amount) AS total_amount,
    SUM(i.discount_amount) AS total_discount
FROM transactions t
JOIN invoices i ON t.description LIKE CONCAT('%', i.id, '%')  
JOIN customers c ON i.customer_id = c.id  
WHERE MONTH(t.transaction_date) = ? AND YEAR(t.transaction_date) = ?
GROUP BY c.id, c.name, c.email
LIMIT 25;";

$customerStmt = $connection->prepare($customerQuery);
$customerStmt->bind_param("ii", $month, $year);
$customerStmt->execute();
$customerResult = $customerStmt->get_result();





// Fetch total revenue from paid invoices
$incomeQuery = $connection->prepare("SELECT SUM(amount) AS total_income 
                                      FROM transactions 
                                      WHERE MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?");
$incomeQuery->bind_param("ii", $month, $year);
$incomeQuery->execute();
$incomeResult = $incomeQuery->get_result();
$incomeData = $incomeResult->fetch_assoc();
$totalIncome = $incomeData['total_income'] ?? 0;


// Fetch total Cost of Goods Sold (COGS)
$cogsQuery = "SELECT SUM(total_cogs) AS amount 
              FROM cogs 
              WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?";

$cogsStmt = $connection->prepare($cogsQuery);
$cogsStmt->bind_param("ii", $month, $year);
$cogsStmt->execute();
$cogsResult = $cogsStmt->get_result();
$cogsRow = $cogsResult->fetch_assoc();
$cogs = $cogsRow['amount'] ?? 0;

// Set COGS Breakdown to a single category
$cogsBreakdown = [['name' => 'Equipment', 'amount' => $cogs]];

// Calculate Gross Profit
$grossProfit = $totalIncome - $cogs;

// Fetch breakdown of Operating Expenses
// Get current month and year

// Fetch breakdown of Operating Expenses for the current month
$expensesBreakdownQuery = "SELECT category, SUM(amount) AS amount 
                           FROM employee_expenses 
                           WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ?
                           GROUP BY category";
$expensesStmt = $connection->prepare($expensesBreakdownQuery);
$expensesStmt->bind_param("ii", $month, $year);
$expensesStmt->execute();
$expensesBreakdownResult = $expensesStmt->get_result();

$expensesBreakdown = [];
$operatingExpenses = 0;
while ($row = $expensesBreakdownResult->fetch_assoc()) {
    $expensesBreakdown[] = $row;
    $operatingExpenses += $row['amount'];
}

// Fetch total Salaries & Wages from payroll for the current month
$salariesQuery = "SELECT SUM(net_pay) AS salaries 
                  FROM payroll 
                  WHERE MONTH(processed_at) = ? AND YEAR(processed_at) = ?";
$salariesStmt = $connection->prepare($salariesQuery);
$salariesStmt->bind_param("ii", $month, $year);
$salariesStmt->execute();
$salariesResult = $salariesStmt->get_result();
$salariesRow = $salariesResult->fetch_assoc();
$salaries = $salariesRow['salaries'] ?? 0;


// Add Salaries & Wages to expenses breakdown
$expensesBreakdown[] = ['category' => 'Salaries & Wages', 'amount' => $salaries];
$operatingExpenses += $salaries;

// Calculate Net Income
$netIncome = $grossProfit - $operatingExpenses;
?>

<style>
#calendar-container {
    max-width: auto;
    padding: 30px;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed; /* Ensure equal spacing */
}

th, td {

    height: 80px;
    text-align: center;
    vertical-align: middle;
    border: 1px solid #ddd;
}

th {
    background-color: #f1f1f1;
    font-weight: bold;
    padding: 10px;
    text-transform: uppercase;
}

td {
    background-color: #fff;
    font-size: 16px;
    cursor: pointer;
    position: relative;
    transition: background-color 0.3s ease;
    padding: 5px;
}

td:hover {
    background-color: #e9e9e9;
}

td.weekend {
    background-color: #f9f9f9;
    color: #888;
}

td.current-day {
    background-color: #4CAF50;
    color: white;
    font-weight: bold;
    border-radius: 50%;
}

.sales-summary {
    text-align: center;
    padding: 20px;
    background: #ed6978;
    color: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

/* Tooltip for income display */
.tooltip {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    background-color: rgba(0, 0, 0, 0.75);
    color: white;
    padding: 5px;
    border-radius: 5px;
    visibility: hidden;
}

td:hover .tooltip {
    visibility: visible;
}

.sales-chart {
    width: 100%;
    height: 300px;
    margin-top: 30px;
    text-align: center;
}


.customer-summary {
    margin: 30px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    text-align: center;
    max-width: 90%;

}

.chart-summary-container {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap; /* Ensures responsiveness */
    gap: 20px;
    margin-top: 30px;
}



.customer-summary h3 {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 15px;
}

.customer-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.customer-table th, .customer-table td {
    border: 1px solid #ed6978;
    padding: 10px;
    text-align: center;
    height: 50px;
}

.customer-table th {
    background-color: #ed6978;
}

.customer-table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.customer-table tr:hover {
    background-color: #e1e8ff;
    transition: background 0.3s ease;
}
.calendar-navigation {
    text-align: center;
    margin: 20px 0;
}

.calendar-navigation a {
    display: inline-block;
    padding: 10px 15px;
    margin: 0 10px;
    text-decoration: none;
    color: white;
    background-color: #ed6978;
    border-radius: 5px;
    transition: background 0.3s ease;
}

.calendar-navigation a:hover {
    background-color: #c44f5d;
}

.calendar-navigation span {
    font-size: 18px;
    font-weight: bold;
}

</style>


    <input type="checkbox" id="nav-toggle">
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="img/logo1.png" alt="">
    </div>

    <div class="sidebar-menu">
    <ul>
        <li>
                <a href="analytics.php" class="active"><span class="las la-tachometer-alt"></span>
                <span>Dashboard</span></a>
            </li>
            <li>
            <a href="super_financial.php"><span class="las la-balance-scale"></span>
            <span>Financial Request</span></a>
</li>
<li class="submenu">
            <a href="#"><span class="las la-sitemap"></span>
            <span>Financial Reports</span></a>
            <ul class="submenu-items">
                <li><a href="coa.php"><span class="las la-folder"></span> Chart of Accounts</a></li>
                <li><a href="balance_sheet.php"><span class="las la-chart-line"></span> Balance Sheet</a></li>
                <li><a href="account_receivable.php"><span class="las la-file-invoice"></span> Accounts Receivable</a></li>
            </ul>
        </li>
        <li>
                <a href="index.php"><span class="las la-file-invoice"></span>
                <span>Invoice</span></a>
            </li>
            <li>
            <a href="add_user.php"><span class="las la-users"></span>
                <span>User Management</span></a>
            </li>
            <li>
                <a href="audit_log.php"><span class="las la-file-invoice"></span>
                <span>Audit Logs</span></a>
            </li>
        </ul>
    </div>
</div>


    <div class="main-content">
        <header>
            <div class="header-title">
                <h2>
                <label for="nav-toggle">
                    <span class="las la-bars"></span>
                </label>
                Monthly Income Report
                </h2>
                </div>

                <div class="user-wrapper">

                <span class="las la-bell" id="notification-bell" style="cursor:pointer; position:relative;">
    <span id="pending-count" style="
        position: absolute;
        top: 0;
        right: 0;
        background: red;
        color: white;
        border-radius: 50%;
        padding: 5px;
        font-size: 12px;
        display: none;">
    </span>
</span>

    <div class="user-info">
    <h4><?php echo htmlspecialchars($user_name); ?></h4>
    <small><?php echo htmlspecialchars($user_role); ?></small>
    <div class="dropdown">
        <button class="settings-btn">
            <span class="las la-cog"></span>
        </button>
        <div class="dropdown-content">
        <a href="#" id="openChangePasswordModal"><span class="las la-key"></span> Change Password</a>
            <a href="logout.php"><span class="las la-sign-out-alt"></span> Logout</a>
        </div>
    </div>
</div>
                </div>
        </header>
        
        <style>
 /* Hide modal initially with smooth fade-in effect */
#changePasswordModal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

/* Show modal smoothly */
#changePasswordModal.show {
    display: block;
    opacity: 1;
}

/* Center modal content */
.modal-dialog {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 420px;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0px 10px 25px rgba(0, 0, 0, 0.3);
    transition: transform 0.3s ease-in-out;
}

/* When modal is opened, add a small bounce effect */
#changePasswordModal.show .modal-dialog {
    transform: translate(-50%, -50%) scale(1.05);
}

/* Modal Header */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.modal-title {
    font-size: 20px;
    font-weight: bold;
    color: #333;
}

/* Close Button */
.close {
    font-size: 22px;
    font-weight: bold;
    background: none;
    border: none;
    cursor: pointer;
    transition: color 0.3s;
}

.close:hover {
    color: red;
}

/* Modal Body */
.modal-body {
    margin-top: 15px;
}

/* Form Styles */
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    font-weight: bold;
    color: #555;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 16px;
}

/* Submit Button */
.btn-primary {
    width: 100%;
    padding: 10px;
    font-size: 16px;
    border-radius: 6px;
    background: #ed6978;
    border: none;
    color: white;
    transition: background 0.3s;
    cursor: pointer;
}

.btn-primary:hover {
    background: #d1697b;
}

/* Responsive */
@media screen and (max-width: 480px) {
    .modal-dialog {
        width: 90%;
        padding: 20px;
    }
}

        </style>
<!-- Change Password Modal -->
<div id="changePasswordModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Change Password</h5>
                        <button type="button" class="close" id="closeModal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="changePasswordForm" method="POST" action="change_password.php">
                            <div class="form-group">
                                <label for="currentPassword">Current Password</label>
                                <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label for="newPassword">New Password</label>
                                <input type="password" class="form-control" id="newPassword" name="new_password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
<style>
/* Tab and Filter Alignment */
.tabs-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.tabs {
    display: flex;
    gap: 5px;
}

.tab-button {
    background: #0a1d4e;
    color: white;
    padding: 12px 18px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    font-size: 14px;
    transition: background 0.3s ease, transform 0.2s ease;
}

.tab-button.active {
    background: #0056b3;
}

/* KPI Metrics */
.kpi-metrics {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    width: 100%; /* Matches table width */
}

.kpi-card {
    background: linear-gradient(135deg, #0a1d4e, #003080);
    color: white;
    padding: 10px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    transition: transform 0.3s ease;
}

@media (max-width: 768px) {
    .dashboard-container {
        flex-direction: column;
        align-items: center;
    }
    .filter-container {
        width: 100%;
        text-align: center;
    }
    .kpi-metrics {
        width: 100%;
    }
}

.kpi-value {
    font-size: 24px;
    color: #fff;
}

.amount {
    font-size: 28px;
    font-weight: bold;
    color: #fff;
}
.chart-table-container {
    display: flex;
    justify-content: space-between;
    align-items: stretch;
    margin-top: 10px;
    gap: 10px;
    height: 550px; /* Decrease overall container height */
}

.chart-stack {
    display: flex;
    flex-direction: column;
    width: 50%;
    gap: 10px;
    justify-content: space-between;
    height: 100%;
}

.chart-container {
    padding: 10px; /* Reduce padding */
    background: #fff;
    border-radius: 8px;
    box-shadow: 0px 3px 8px rgba(0, 0, 0, 0.15);
    transition: transform 0.3s ease;
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    max-height: 300px; /* Decrease height */
}

.table-container {
    width: 80%;
    background: white;
    padding: 10px; /* Reduce padding */
    border-radius: 8px;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    max-height: 550px; /* Set height similar to charts */
    overflow-y: auto; /* Scroll if content overflows */
}

#state {
    width: 100%;
    border-collapse: collapse;
    overflow: auto;
    font-size: 13px;
    border: 1px solid #ccc; /* Excel-like border */
}

/* Table Headers */
#state th {
    background: #0a1d4e; /* Light gray like Excel */
    color: #fff;
    font-size: 13px;
    font-weight: bold;
    padding: 8px;
    text-align: left;
    border: 1px solid #bbb; /* Darker border for headers */
    position: sticky;
    top: 0;
    z-index: 2;
}

/* Table Rows */
#state td {
    font-size: 12px;
    color: #333;
    padding: 8px;
    text-align: left;
    border: 1px solid #ddd; /* Excel-like grid lines */
}

/* Alternating Row Colors */
#state tr:nth-child(even) {
    background: #f9f9f9;
}

/* Hover Effect */
#state tr:hover {
    background: #d3e1f5; /* Light blue Excel hover effect */
}

/* Highlighted Rows (Total, Subtotals) */
#state tr.highlight {
    background: #ffffcc; /* Light yellow Excel-style highlight */
    font-weight: bold;
}

/* Responsive Table */
@media (max-width: 768px) {
    #state th, #state td {
        padding: 6px;
        font-size: 11px;
    }
}


/* Ensure responsiveness */
@media (max-width: 768px) {
    .chart-table-container {
        flex-direction: column;
        height: auto;
    }

    .chart-stack, .table-container {
        width: 100%;
        max-height: none; /* Allow content to adjust on small screens */
    }

    .chart-container {
        width: 100%;
        max-height: 200px; /* Slightly higher for mobile */
    }
}

</style>
        <main>
        <div class="tabs-container">
        <div class="tabs">
        <button class="tab-button active" onclick="showTab('report')">Report</button>
    <button class="tab-button" onclick="showTab('statement')">Statement</button>
</div>
            </div>

<div id="statement" class="tab-content" style="display: block;">
<div class="dashboard-container">



        <div class="content">
        <div class="kpi-metrics">
        <div class="kpi-card">
            <div class="kpi-title">Total Revenue</div>
            <div class="kpi-value"><?php echo '₱' . number_format($totalIncome, 2); ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Gross Profit</div>
            <div class="kpi-value"><?php echo '₱' . number_format($grossProfit, 2); ?></div>
        </div>

        <div class="kpi-card">
            <div class="kpi-title">Net Income</div>
            <div class="kpi-value"><?php echo '₱' . number_format($netIncome, 2); ?></div>
        </div>
    </div>


        <div class="chart-table-container">
        <div class="table-container">
    <table id="state">
            <tr><th>profit & loss</th><th>Amount (₱)</th></tr>
            <tr><td>Total Revenue</td><td><?php echo number_format($totalIncome, 2); ?></td></tr>
            <tr><td colspan="2" class="highlight">Cost of Goods Sold (COGS) Breakdown</td>
            <?php foreach ($cogsBreakdown as $cogsItem): ?>
                <tr><td><?php echo htmlspecialchars($cogsItem['name']); ?></td><td><?php echo number_format($cogsItem['amount'], 2); ?></td></tr>
            <?php endforeach; ?></tr>
            <tr><td><strong>Total COGS</strong></td><td><strong><?php echo number_format($cogs, 2); ?></strong></td></tr>
            <tr class="highlight"><td><strong>Gross Profit</strong></td><td><strong><?php echo number_format($grossProfit, 2); ?></strong></td></tr>
            <tr><td colspan="2" class="highlight">Operating Expenses Breakdown</td>
            <?php foreach ($expensesBreakdown as $expenseItem): ?>
                <tr><td><?php echo htmlspecialchars($expenseItem['category']); ?></td><td><?php echo number_format($expenseItem['amount'], 2); ?></td></tr>
            <?php endforeach; ?></tr>
            <tr><td><strong>Total Operating Expenses</strong></td><td><strong><?php echo number_format($operatingExpenses, 2); ?></strong></td></tr>
            <tr class="highlight"><td><strong>Net Income</strong></td><td><strong><?php echo number_format($netIncome, 2); ?></strong></td></tr>
        </table>
    </div>

    <div class="chart-stack">
<h3>Profit & Loss</h3>
    <div class="chart-container">
    <canvas id="profitLossChart"></canvas>
</div>
<h3>Income Goal</h3>
<div class="chart-container">
    <canvas id="incomeChart"></canvas>
</div>

    </div>

</div>
        </div>
    </div>
    </div>

    <script>
        var ctx = document.getElementById('profitLossChart').getContext('2d');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Total Revenue', 'COGS', 'Operating Expenses', 'Net Income'],
        datasets: [{
            label: 'Amount (₱)',
            data: [
                <?php echo $totalIncome; ?>, 
                <?php echo $cogs; ?>, 
                <?php echo $operatingExpenses; ?>, 
                <?php echo $netIncome; ?>
            ],
            backgroundColor: ['#0a1d4e', '#0a1d4e', '#0a1d4e', '#0a1d4e'], // Green, Red, Yellow, Blue
            borderColor: ['#0a1d4e', '#0a1d4e', '#0a1d4e', '#0a1d4e'], 
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }, // Hide legend since it's self-explanatory
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return '₱' + tooltipItem.raw.toLocaleString();
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString(); // Format numbers with commas
                    }
                }
            }
        }
    }
});


       var ctx = document.getElementById('incomeChart').getContext('2d');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Actual Income', 'Income Goal'],
        datasets: [{
            label: 'Amount (₱)',
            data: [
                <?php echo $totalIncome; ?>, 
                <?php echo $totalGoal; ?>
            ],
            backgroundColor: ['#0a1d4e', '#0a1d4e'], // Green for actual, Yellow for goal
            borderColor: ['#0a1d4e', '#0a1d4e'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }, // Hide legend to keep it clean
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return '₱' + tooltipItem.raw.toLocaleString();
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

    </script>



<div id="report" class="tab-content" style="display: none;">
        <div id="calendar-container">
        <div class="sales-summary">
        <h3>Sales Summary for <?php echo date("F Y", strtotime($currentMonth)); ?></h3>
        <p>Sales Completed: <strong>₱<?php echo number_format(array_sum($actualIncome), 2); ?></strong> | Sales Plan: ₱<?php echo number_format($totalGoal, 2); ?></p>
    </div>

    <!-- Navigation for Calendar -->
    <div class="calendar-navigation">
    <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="prev-month">« Previous</a>
<span><?php echo date("F Y", strtotime($currentMonth)); ?></span>
<a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="next-month">Next »</a>

    </div>
            <table>
                <thead>
                    <tr>
                        <th>Sun</th>
                        <th>Mon</th>
                        <th>Tue</th>
                        <th>Wed</th>
                        <th>Thu</th>
                        <th>Fri</th>
                        <th>Sat</th>
                    </tr>
                </thead>
                <tbody>
                <?php
            $currentDay = 1;
            $totalRows = ceil(($daysInMonth + $firstDayOfMonth) / 7);
            
            for ($row = 0; $row < $totalRows; $row++) {
                echo '<tr>';
                
                for ($col = 0; $col < 7; $col++) {
                    if ($row == 0 && $col < $firstDayOfMonth) {
                        echo '<td></td>';
                    } elseif ($currentDay <= $daysInMonth) {
                        $currentDate = sprintf("%04d-%02d-%02d", $year, $month, $currentDay);
                        $currentClass = '';
                        $incomeDisplay = '';

                        if (isset($actualIncome[$currentDate])) {
                            $income = $actualIncome[$currentDate];
                            $percentage = ($totalGoal > 0) ? ($income / $totalGoal) * 100 : 0;
                            $incomeDisplay = "<br>Sales: <small>₱" . number_format($income, 2) . "</small>";
                        }

                        $weekendClass = ($col == 0 || $col == 6) ? ' weekend' : '';
                        echo "<td class='$weekendClass' $currentClass>$currentDay $incomeDisplay</td>";

                        $currentDay++;
                    } else {
                        echo '<td></td>';
                    }
                }
                echo '</tr>';
            }
            ?>
                </tbody>
            </table>

            

            
        </div>
        <div class="chart-summary-container">
    <!-- Payment Method Income Breakdown Chart (Pie Chart) -->
    <div class="sales-chart">
        <canvas id="paymentMethodChart"></canvas>
    </div>

    <!-- Customer Summary Table -->
    <div class="customer-summary">
        <h3>Customer Purchases Summary</h3>
        <table class="customer-table">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Total Spent (₱)</th>
                    <th>Total Discount (₱)</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $customerResult->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td>₱<?php echo number_format($row['total_discount'], 2); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
                </div>

        </main>
    </div>
    
<script>
// Prepare the payment method data for the pie chart
const paymentLabels = <?php echo json_encode(array_keys($paymentMethods)); ?>;
const paymentData = <?php echo json_encode(array_values($paymentMethods)); ?>;
const ctxPayment = document.getElementById('paymentMethodChart').getContext('2d');

const paymentMethodChart = new Chart(ctxPayment, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_keys($paymentMethods)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($paymentMethods)); ?>,
            backgroundColor: [
                '#1E88E5', // Corporate Blue
                '#43A047', // Professional Green
                '#FB8C00', // Orange for contrast
                '#6D4C41', // Earthy Brown
                '#8E24AA', // Elegant Purple
                '#D81B60'  // Deep Pink
            ],
            borderColor: '#ffffff',
            borderWidth: 2,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        cutout: '80%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: {
                        size: 14,
                        weight: 'bold'
                    },
                    color: '#333'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.85)',
                titleColor: '#ffffff',
                bodyColor: '#ffffff',
                callbacks: {
                    label: function(tooltipItem) {
                        const value = tooltipItem.raw;
                        const total = paymentData.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(2);
                        return `₱${parseFloat(value).toLocaleString()} (${percentage}%)`;
                    }
                }
            }
        },
        animation: {
            animateRotate: true,
            animateScale: true
        }
    }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const openModalBtn = document.getElementById("openChangePasswordModal");
    const closeModalBtn = document.getElementById("closeModal");
    const modal = document.getElementById("changePasswordModal");

    if (openModalBtn && closeModalBtn && modal) {
        openModalBtn.addEventListener("click", function(event) {
            event.preventDefault(); // Prevent default anchor action
            modal.classList.add("show"); // Add 'show' class to display modal
        });

        closeModalBtn.addEventListener("click", function() {
            modal.classList.remove("show"); // Remove 'show' class to hide modal
        });

        // Close modal when clicking outside the modal dialog
        window.addEventListener("click", function(event) {
            if (event.target === modal) {
                modal.classList.remove("show");
            }
        });
    } else {
        console.error("Modal or trigger elements not found.");
    }
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const bellIcon = document.getElementById("notification-bell");
    const pendingCount = document.getElementById("pending-count");

    // Create notification dropdown
    const notificationDropdown = document.createElement("div");
    notificationDropdown.id = "notification-dropdown";
    notificationDropdown.style.cssText = `
        display: none;
        position: absolute;
        top: 50px;
        right: 0;
        background: #fff;
        width: 340px;
        box-shadow: 0px 5px 15px rgba(0,0,0,0.2);
        border-radius: 10px;
        padding: 10px;
        z-index: 1000;
        font-family: 'Inter', Arial, sans-serif;
        animation: fadeIn 0.3s ease-in-out;
    `;

    const arrow = document.createElement("div");
    arrow.style.cssText = `
        position: absolute;
        top: -10px;
        right: 15px;
        width: 0;
        height: 0;
        border-left: 10px solid transparent;
        border-right: 10px solid transparent;
        border-bottom: 10px solid white;
    `;
    notificationDropdown.appendChild(arrow);

    const header = document.createElement("div");
    header.innerHTML = "<strong style='font-size: 16px; color: #222;'>Notifications</strong>";
    header.style.cssText = `
        padding: 12px;
        border-bottom: 1px solid #ddd;
        font-size: 15px;
        font-weight: 600;
        color: #222;
    `;
    notificationDropdown.appendChild(header);

    bellIcon.appendChild(notificationDropdown);

    function timeAgo(timestamp) {
    const now = new Date();
    const past = new Date(timestamp);
    const diff = Math.floor((now - past) / 1000);

    if (diff < 60) return `${diff} sec${diff !== 1 ? "s" : ""} ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)} min${Math.floor(diff / 60) !== 1 ? "s" : ""} ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} hour${Math.floor(diff / 3600) !== 1 ? "s" : ""} ago`;
    if (diff < 2592000) return `${Math.floor(diff / 86400)} day${Math.floor(diff / 86400) !== 1 ? "s" : ""} ago`;
    return past.toLocaleDateString(); // Fallback to date format
}


    function fetchNotifications() {
        fetch('super_fetch_notifications.php')
        .then(response => response.json())
        .then(data => {
            notificationDropdown.innerHTML = "";
            notificationDropdown.appendChild(arrow);
            notificationDropdown.appendChild(header);

            if (data.length > 0) {
                data.forEach(item => {
                    let notificationItem = document.createElement("div");
                    notificationItem.innerHTML = `
    <a href="request_form.php?id=${item.id}" class="notification-item" data-id="${item.id}" style="
        text-decoration: none;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px;
        border-bottom: 1px solid #eee;
        transition: background 0.2s;
        font-size: 14px;
    " onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='transparent'">
        <div style="flex-grow: 1;">
            <strong style="font-size: 15px; color: #222;">Request #${item.id}</strong><br>
            <span style="color: #555; font-size: 14px;">${item.request_type} request from ${item.staff_name} (${item.department})</span><br>
            <span style="
                display: inline-block;
                background: orange;
                color: white;
                font-size: 12px;
                font-weight: bold;
                padding: 3px 8px;
                border-radius: 5px;
                margin-top: 5px;
            ">Pending</span><br>
            <span style="color: #777; font-size: 12px;">${timeAgo(item.created_at)}</span>
        </div>
    </a>
`;
                    notificationDropdown.appendChild(notificationItem);
                });

                pendingCount.innerText = data.length;
                pendingCount.style.display = data.length > 0 ? "inline-block" : "none";
            } else {
                notificationDropdown.innerHTML += "<p style='text-align:center; padding: 20px; font-size: 14px; color: #555;'>No pending requests</p>";
                pendingCount.style.display = "none";
            }
        });
    }

    bellIcon.addEventListener("click", function(event) {
        event.stopPropagation();
        fetchNotifications();
        notificationDropdown.style.display = (notificationDropdown.style.display === "block") ? "none" : "block";
    });

    document.addEventListener("click", function(event) {
        if (!bellIcon.contains(event.target)) {
            notificationDropdown.style.display = "none";
        }
    });

    fetchNotifications();
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    showTab('report'); // Set the default visible tab on page load
});

function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    document.getElementById(tabId).style.display = 'block';

    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });

    document.querySelector(`.tab-button[onclick="showTab('${tabId}')"]`).classList.add('active');
}
</script>

</body>
</html>