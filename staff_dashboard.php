<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="icon" href="img/logo-sm.png">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/chart.css">
    <link rel= "stylesheet" href= "https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css" >
</head>
<body>

<?php
include "assets/databases/dbconfig.php";
session_start();  // Start the session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect to login if not logged in
    exit();
}

$user_name = $_SESSION['name'];  // User name from session
$user_role = $_SESSION['role_display'];  // User role from session

$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

$currentBudget = 0;

// Fetch monthly income and expenses comparison
$sql_income_expenses = "SELECT 
    period AS month, 
    SUM(total_income) AS total_income, 
    SUM(total_expenses) AS total_expenses 
FROM (
    -- Income per month
    SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS period, 
           SUM(amount) AS total_income, 
           0 AS total_expenses 
    FROM transactions 
    GROUP BY period
    
    UNION

    -- Expenses per month (even if no income)
    SELECT DATE_FORMAT(expense_date, '%Y-%m') AS period, 
           0 AS total_income, 
           SUM(amount) AS total_expenses 
    FROM employee_expenses 
    GROUP BY period
) AS combined 
GROUP BY period 
ORDER BY period DESC
";

$result_income_expenses = $connection->query($sql_income_expenses);
$income_expenses_data = [];
while ($row = $result_income_expenses->fetch_assoc()) {
    $income_expenses_data[] = [
        'month' => $row['month'],
        'total_income' => $row['total_income'],
        'total_expenses' => $row['total_expenses']
    ];
}


// Fetch total income for the given month and year
$sql_income = "SELECT SUM(amount) AS total_income 
               FROM transactions 
               WHERE MONTH(transaction_date) = ? 
               AND YEAR(transaction_date) = ?";

$stmt = $connection->prepare($sql_income);
$stmt->bind_param("ii", $currentMonth, $currentYear);
$stmt->execute();
$result_income = $stmt->get_result();
$row_income = $result_income->fetch_assoc();

$total_income = $row_income['total_income'] ?? 0; // Default to 0 if null

// Define tax rate (25% in this example)
$taxRate = 0.25;

// Calculate tax deduction
$taxDeduction = $total_income * $taxRate;

// Initialize total expenses to 0
$totalExpenses = 0;

// Fetch total expenses from employee_expenses table
$expensesQuery = "SELECT SUM(amount) AS total_expenses 
                  FROM employee_expenses 
                  WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ?";
$expensesResult = $connection->prepare($expensesQuery);
$expensesResult->bind_param("ii", $currentMonth, $currentYear);
$expensesResult->execute();
$expensesRow = $expensesResult->get_result()->fetch_assoc();

// Check if result is not empty and 'total_expenses' is set
if ($expensesRow && isset($expensesRow['total_expenses'])) {
    $totalExpenses = $expensesRow['total_expenses'];
}

// Fetch net pay from payroll table
$payrollQuery = "SELECT SUM(net_pay) AS total_netpay 
                 FROM payroll 
                 WHERE MONTH(processed_at) = ? AND YEAR(processed_at) = ?";
$payrollResult = $connection->prepare($payrollQuery);
$payrollResult->bind_param("ii", $currentMonth, $currentYear);
$payrollResult->execute();
$payrollRow = $payrollResult->get_result()->fetch_assoc();

// Check if result is not empty and 'total_netpay' is set
$totalNetPay = $payrollRow['total_netpay'] ?? 0;

// Add total expenses and total netpay
$totalExpenses += $totalNetPay;

// Calculate net income after tax
$netIncome = $total_income - $totalExpenses;

// Fetch income vs expenses data
$sql_income_expenses = "WITH months AS (
    SELECT DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL -n MONTH), '%Y-%m') AS month
    FROM (SELECT 0 AS n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) AS nums
),
current_year AS (
    SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS month, SUM(amount) AS total_income
    FROM transactions
    WHERE YEAR(transaction_date) = YEAR(CURDATE())
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
),
last_year AS (
    SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS month, SUM(amount) AS total_income
    FROM transactions
    WHERE YEAR(transaction_date) = YEAR(CURDATE()) - 1
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
),
current_expenses AS (
    SELECT DATE_FORMAT(expense_date, '%Y-%m') AS month, SUM(amount) AS total_expenses
    FROM employee_expenses
    WHERE YEAR(expense_date) = YEAR(CURDATE())
    GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
),
last_expenses AS (
    SELECT DATE_FORMAT(expense_date, '%Y-%m') AS month, SUM(amount) AS total_expenses
    FROM employee_expenses
    WHERE YEAR(expense_date) = YEAR(CURDATE()) - 1
    GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
),
current_payroll AS (
    SELECT DATE_FORMAT(processed_at, '%Y-%m') AS month, SUM(net_pay) AS total_payroll
    FROM payroll
    WHERE YEAR(processed_at) = YEAR(CURDATE())
    GROUP BY DATE_FORMAT(processed_at, '%Y-%m')
),
last_payroll AS (
    SELECT DATE_FORMAT(processed_at, '%Y-%m') AS month, SUM(net_pay) AS total_payroll
    FROM payroll
    WHERE YEAR(processed_at) = YEAR(CURDATE()) - 1
    GROUP BY DATE_FORMAT(processed_at, '%Y-%m')
)
SELECT 
    m.month,
    COALESCE(c.total_income, 0) AS current_income,
    COALESCE(l.total_income, 0) AS last_year_income,
    COALESCE(c.total_income, 0) - COALESCE(l.total_income, 0) AS income_difference,
    CASE 
        WHEN COALESCE(l.total_income, 0) = 0 THEN NULL
        ELSE ((COALESCE(c.total_income, 0) - COALESCE(l.total_income, 0)) / COALESCE(l.total_income, 1)) * 100 
    END AS income_percentage_change,
    
    -- Total Expenses (Including Payroll)
    COALESCE(ce.total_expenses, 0) + COALESCE(cp.total_payroll, 0) AS current_expenses,
    COALESCE(le.total_expenses, 0) + COALESCE(lp.total_payroll, 0) AS last_year_expenses,
    (COALESCE(ce.total_expenses, 0) + COALESCE(cp.total_payroll, 0)) - (COALESCE(le.total_expenses, 0) + COALESCE(lp.total_payroll, 0)) AS expense_difference,
    CASE 
        WHEN (COALESCE(le.total_expenses, 0) + COALESCE(lp.total_payroll, 0)) = 0 THEN NULL
        ELSE (((COALESCE(ce.total_expenses, 0) + COALESCE(cp.total_payroll, 0)) - (COALESCE(le.total_expenses, 0) + COALESCE(lp.total_payroll, 0))) / (COALESCE(le.total_expenses, 1) + COALESCE(lp.total_payroll, 1))) * 100 
    END AS expense_percentage_change,

    -- Separate Payroll Expense Columns
    COALESCE(cp.total_payroll, 0) AS current_payroll_expenses,
    COALESCE(lp.total_payroll, 0) AS last_year_payroll_expenses

FROM months m
LEFT JOIN current_year c ON m.month = c.month
LEFT JOIN last_year l ON m.month = l.month
LEFT JOIN current_expenses ce ON m.month = ce.month
LEFT JOIN last_expenses le ON m.month = le.month
LEFT JOIN current_payroll cp ON m.month = cp.month
LEFT JOIN last_payroll lp ON m.month = lp.month
ORDER BY m.month ASC;
";

$result_cash_flow = $connection->query($sql_income_expenses);

$cash_flow_data = [];
$forecast_data = []; // New array for forecast data

$forecast_months = 6; // Forecast for next 6 months
$last_actual_month = end($cash_flow_data)['month'] ?? date('Y-m');

// Fetch actual cash flow data from the database
if ($result_cash_flow->num_rows > 0) {
    while ($row = $result_cash_flow->fetch_assoc()) {
        $cash_flow_data[] = [
            'month' => $row['month'],
            'current_income' => $row['current_income'],
            'last_year_income' => $row['last_year_income'],
            'income_difference' => $row['income_difference'],
            'income_percentage_change' => $row['income_percentage_change'] !== null ? number_format($row['income_percentage_change'], 2) . '%' : 'N/A',

            'current_expenses' => $row['current_expenses'],
            'last_year_expenses' => $row['last_year_expenses'],
            'expense_difference' => $row['expense_difference'],
            'expense_percentage_change' => $row['expense_percentage_change'] !== null ? number_format($row['expense_percentage_change'], 2) . '%' : 'N/A',

            'current_payroll_expenses' => $row['current_payroll_expenses'],
            'last_year_payroll_expenses' => $row['last_year_payroll_expenses'],
        ];
    }
    // Get the last actual recorded month
    $last_actual_month = end($cash_flow_data)['month'] ?? date('Y-m');
} else {
    echo "No data found.";
}

// Generate Forecast Data Separately
for ($i = 1; $i <= $forecast_months; $i++) {
    $forecast_month = date('Y-m', strtotime("+$i months", strtotime($last_actual_month)));

    // Simple forecast using last year’s percentage change
    $last_year_income = end($cash_flow_data)['last_year_income'] ?? 0;
    $current_income = end($cash_flow_data)['current_income'] ?? 0;
    $income_growth_rate = ($last_year_income > 0) ? ($current_income - $last_year_income) / $last_year_income : 0;
    $forecast_income = $current_income + ($current_income * $income_growth_rate);

    $last_year_expenses = end($cash_flow_data)['last_year_expenses'] ?? 0;
    $current_expenses = end($cash_flow_data)['current_expenses'] ?? 0;
    $expense_growth_rate = ($last_year_expenses > 0) ? ($current_expenses - $last_year_expenses) / $last_year_expenses : 0;
    $forecast_expenses = $current_expenses + ($current_expenses * $expense_growth_rate);

    $forecast_data[] = [
        'month' => $forecast_month,
        'current_income' => round($forecast_income, 2),
        'current_expenses' => round($forecast_expenses, 2),
        'is_forecast' => true
    ];
}

// Merge forecast data into cash flow data for full dataset
$cash_flow_data = array_merge($cash_flow_data, $forecast_data);

// Fetch total company budget from session or database
$totalBudget = $_SESSION['totalBudget'] ?? 0;
if ($totalBudget === 0) {
    $budgetQuery = $connection->prepare("SELECT amount FROM company_budget WHERE month = ? AND year = ?");
    $budgetQuery->bind_param("ii", $currentMonth, $currentYear);
    $budgetQuery->execute();
    $budgetResult = $budgetQuery->get_result();
    $budgetData = $budgetResult->fetch_assoc();
    $totalBudget = $budgetData['amount'] ?? 0;
    $budgetQuery->close();
}

// Fetch total approved budget requests from session or database
$approvedBudget = $_SESSION['approvedBudget'] ?? 0;
if ($approvedBudget === 0) {
    $approvedQuery = $connection->prepare("SELECT SUM(amount) AS approved_budget FROM requests WHERE status = 'Approved' AND MONTH(created_at) = ? AND YEAR(created_at) = ?");
    $approvedQuery->bind_param("ii", $currentMonth, $currentYear);
    $approvedQuery->execute();
    $approvedResult = $approvedQuery->get_result();
    $approvedData = $approvedResult->fetch_assoc();
    $approvedBudget = $approvedData['approved_budget'] ?? 0;
    $approvedQuery->close();
}

// Calculate usage percentage
$budgetUsage = ($totalBudget > 0) ? ($approvedBudget / $totalBudget) * 100 : 0;
$remainingBudget = max(0, 100 - $budgetUsage);

// Fetch total income for the current month
$incomeQuery = $connection->prepare("SELECT SUM(amount) AS total_income 
                                      FROM transactions 
                                      WHERE MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?");
$incomeQuery->bind_param("ii", $currentMonth, $currentYear);
$incomeQuery->execute();
$incomeResult = $incomeQuery->get_result();
$incomeData = $incomeResult->fetch_assoc();
$totalIncome = $incomeData['total_income'] ?? 0;

// Fetch income goal for the current month
$goalQuery = $connection->prepare("SELECT goal_amount FROM income_goals WHERE month = ? AND year = ?");
$goalQuery->bind_param("ii", $currentMonth, $currentYear);
$goalQuery->execute();
$goalResult = $goalQuery->get_result();
$goalData = $goalResult->fetch_assoc();
$incomeGoal = $goalData['goal_amount'] ?? 0;

// Calculate income progress
$incomeProgress = ($incomeGoal > 0) ? ($totalIncome / $incomeGoal) * 100 : 0;

// Handle budget update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['budget_amount'])) {
    $newBudget = floatval($_POST['budget_amount']);

    // Check if a record exists for this month
    $checkQuery = $connection->prepare("SELECT * FROM company_budget WHERE month = ? AND year = ?");
    $checkQuery->bind_param("ii", $currentMonth, $currentYear);
    $checkQuery->execute();
    $result = $checkQuery->get_result();

    if ($result->num_rows > 0) {
        $stmt = $connection->prepare("UPDATE company_budget SET amount = ? WHERE month = ? AND year = ?");
        $stmt->bind_param("dii", $newBudget, $currentMonth, $currentYear);
    } else {
        $stmt = $connection->prepare("INSERT INTO company_budget (amount, month, year) VALUES (?, ?, ?)");
        $stmt->bind_param("dii", $newBudget, $currentMonth, $currentYear);
    }

    $stmt->execute();
    header("Location: analytics.php?success=1");
    exit();
}

// Handle income goal update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['income_goal_amount'])) {
    $newGoal = floatval($_POST['income_goal_amount']);

    // Check if a record exists for this month
    $checkQuery = $connection->prepare("SELECT * FROM income_goals WHERE month = ? AND year = ?");
    $checkQuery->bind_param("ii", $currentMonth, $currentYear);
    $checkQuery->execute();
    $result = $checkQuery->get_result();

    if ($result->num_rows > 0) {
        $stmt = $connection->prepare("UPDATE income_goals SET goal_amount = ? WHERE month = ? AND year = ?");
        $stmt->bind_param("dii", $newGoal, $currentMonth, $currentYear);
    } else {
        $stmt = $connection->prepare("INSERT INTO income_goals (goal_amount, month, year) VALUES (?, ?, ?)");
        $stmt->bind_param("dii", $newGoal, $currentMonth, $currentYear);
    }

    $stmt->execute();
    header("Location: analytics.php?success=1");
    exit();
}


// Fetch income breakdown by payment method
$paymentMethodQuery = "SELECT payment_method, SUM(amount) AS total_income FROM transactions GROUP BY payment_method";
$paymentMethodResult = $connection->query($paymentMethodQuery);

$paymentMethods = [];
$paymentMethodLabels = [
    'cash' => 'Cash',
    'credit' => 'Credit Card',
    'bank_transfer' => 'Bank Transfer',
    'online' => 'E-Wallet'
];

while ($row = $paymentMethodResult->fetch_assoc()) {
    // Use mapped labels instead of raw enum values
    $displayLabel = $paymentMethodLabels[$row['payment_method']] ?? ucfirst($row['payment_method']);
    $paymentMethods[$displayLabel] = $row['total_income'];
}

echo "<script>const paymentMethods = " . json_encode($paymentMethods) . ";</script>";


// Fetch customer transaction data
$customerQuery = "SELECT c.id AS customer_id, c.name AS customer_name, c.email, SUM(t.amount) AS total_amount, SUM(i.discount_amount) AS total_discount FROM transactions t JOIN invoices i ON t.description LIKE CONCAT('%', i.id, '%') JOIN customers c ON i.customer_id = c.id GROUP BY c.id, c.name, c.email LIMIT 25";
$customerResult = $connection->query($customerQuery);

?>


<?php

// Initialize arrays for quarterly data
$quarters = [1, 2, 3, 4]; // 4 quarters
$income_data = array_fill(0, 4, 0); // Default quarterly income = 0
$tax_data = array_fill(0, 4, 0); // Default quarterly tax = 0

// Fetch monthly income from the database
$sql_income = "SELECT MONTH(transaction_date) AS month, SUM(amount) AS total_income 
               FROM transactions
               GROUP BY MONTH(transaction_date)
               ORDER BY MONTH(transaction_date)";

$result_income = $connection->query($sql_income);

// Tax rate (adjust as needed)
$tax_rate = 0.25; // 25% Tax deduction

// Populate actual data
while ($row = $result_income->fetch_assoc()) {
    $month = (int)$row['month'];
    $quarter = ceil($month / 3) - 1; // Determine quarter (0 = Q1, 1 = Q2, etc.)
    
    $income_data[$quarter] += (float)$row['total_income']; // Add monthly income to the respective quarter
    $tax_data[$quarter] += $income_data[$quarter] * $tax_rate; // Calculate tax for the quarter
}

// Convert PHP arrays to JSON for JavaScript
$quarters_json = json_encode($quarters);
$income_json = json_encode($income_data);
$tax_json = json_encode($tax_data);
?>

<?php
function getTopCustomers($connection, $limit = 10) {
    $currentMonth = date('m'); // Get current month (01-12)
    $currentYear = date('Y');  // Get current year (e.g., 2025)

    $sql = "SELECT c.name AS customer_name, SUM(i.total_amount) AS total_spent
            FROM invoices i
            INNER JOIN customers c ON i.customer_id = c.id
            WHERE LOWER(i.payment_status) = 'paid' 
            AND MONTH(i.issue_date) = ? 
            AND YEAR(i.issue_date) = ?
            GROUP BY c.name
            ORDER BY total_spent DESC
            LIMIT ?";

    $stmt = $connection->prepare($sql);
    $stmt->bind_param("iii", $currentMonth, $currentYear, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $customers = [];
    $spending = [];

    while ($row = $result->fetch_assoc()) {
        $customers[] = $row['customer_name'];
        $spending[] = $row['total_spent'];
    }

    return [
        'customers' => $customers,
        'spending' => $spending,
    ];
}

$customerData = getTopCustomers($connection);

$customerNames = json_encode($customerData['customers'], JSON_HEX_TAG);
$totalSpending = json_encode($customerData['spending'], JSON_HEX_TAG);

?>


<input type="checkbox" id="nav-toggle">
<div class="sidebar">
    <div class="sidebar-brand">
    <img src="img/logo1.png" alt="">
    </div>

    <div class="sidebar-menu">
    <ul>
        <li>
                <a href="staff_dashboard.php" class="active"><span class="las la-tachometer-alt"></span>
                <span>Dashboard</span></a>
            </li>
            <li>
            <a href="staff_financial.php"><span class="las la-balance-scale"></span>
            <span>Financial Request</span></a>
            </li>
            <li class="submenu">
            <a href="#"><span class="las la-sitemap"></span>
            <span>Financial Reports</span></a>
            <ul class="submenu-items">
                <li><a href="staff_coa.php"><span class="las la-folder"></span> Chart of Accounts</a></li>
                <li><a href="staff_balance_sheet.php"><span class="las la-chart-line"></span> Balance Sheet</a></li>
                <li><a href="staff_account_receivable.php"><span class="las la-file-invoice"></span> Accounts Receivable</a></li>
            </ul>
        </li>
            <li>
                <a href="staff_payroll.php"><span class="las la-users"></span>
                <span>Staffing & Payroll</span></a>
            </li>
            <li>
                <a href="staff_audit_log.php"><span class="las la-file-invoice"></span>
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
                Dashboard
                </h2>
                </div>

                <div class="form-container">
    <form id="filter-form" method="GET" action="">
        <!-- Filter fields here -->
    <label for="month">Month:</label>
    <select name="month" id="month">
        <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= ($m == $currentMonth) ? 'selected' : '' ?>>
                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
            </option>
        <?php endfor; ?>
    </select>

    <label for="year">Year:</label>
    <select name="year" id="year">
        <?php for ($y = date('Y') - 5; $y <= date('Y'); $y++): ?>
            <option value="<?= $y ?>" <?= ($y == $currentYear) ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
    </select>

    <button type="submit">Filter</button>
</form>


    <form id="export-form" method="POST" action="export_transactions.php">
        <!-- Export button here -->
         <!-- Export Button -->
    <input type="hidden" name="month" value="<?= $currentMonth ?>">
    <input type="hidden" name="year" value="<?= $currentYear ?>">
    <button type="submit">Export CSV</button>

    </form>
</div>


                <div class="user-wrapper">

                <span class="las la-bell" id="notification-bell" style="cursor:pointer; position:relative;">
        <span id="overdue-count" style="
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
        
        <div id="transactionHistoryPanel" class="transaction-panel">
    <div class="panel-header">
        <h3>Transaction History</h3>
        <div>
            <button onclick="downloadTransactionHistory()">📥 Download</button>
            <button id="maximizeBtn" onclick="toggleMaximize()">🗖</button> 
            <button onclick="closePanel()">✖</button>
        </div>
    </div>
    <div id="transactionContent">
        <p>Select an invoice to view transaction history.</p>
    </div>
</div>

<style>
.transaction-panel {
    position: fixed;
    top: 0;
    right: -600px; /* Initially hidden */
    width: 600px; /* Default width */
    height: 100%;
    background: white;
    box-shadow: -2px 0 5px rgba(0,0,0,0.2);
    transition: right 0.3s ease-in-out, width 0.3s ease-in-out;
    padding: 20px;
    overflow-y: auto;
    z-index: 10000;
}
.transaction-panel.open {
    right: 0;
}
.transaction-panel table {
    width: 100%;
    border-collapse: collapse;
}

.transaction-panel th, .paid-transaction-panel td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

.transaction-panel-invoices th {
    background-color: #0a1d4e;
    color:#fff;
    font-size: 13px;
}

/* Maximized Mode */
.transaction-panel.maximized {
    width: 100vw; /* Full-screen width */
}

/* Panel Header */
.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

/* Button Styling */
.panel-header button {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    margin-left: 8px;
    padding: 5px 10px;
    border-radius: 5px;
}

.panel-header button:hover {
    background-color: #f0f0f0;
}
</style>



        <style>
.kpi-metrics {
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* 3 cards per row */
    gap: 10px;
}
.kpi-card {
    background: #0a1d4e;
    color: white;
    padding: 10px;
    border-radius: 8px;
    text-align: center;
}
.kpi-title {
    font-size: 15px;
    font-weight: bold;
    color: #fff;
}
.kpi-value {
    font-size: 24px;
    color: #fff;
}
/* Main container to hold both charts */
.payment-tax-container {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    width: 100%;
    margin: auto;
}

/* Styles for both income-expenses and tax containers */
.income-expenses-container,
.tax-container {
    width: 50%; /* Each takes half the width */
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.15);
    padding: 20px;
    display: flex;
    flex-direction: column;
}

/* Headings */
.income-expenses-container h3,
.tax-container h3 {
    font-size: 1em;
    font-weight: bold;
    color: #0a1d4e;
}

/* Align chart and legend */
.chart-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Adjust Payment Method Chart */
#paymentMethodChart {
    max-width: 220px; /* Keep chart smaller */
    max-height: 220px;
}

/* Ensure full width for Tax Chart */
#taxDeductionChart {
    width: 100%; /* Fully expands */
    height: auto;
    max-height: 280px;
}

/* Payment Method Legend */
.payment-method-legend {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    margin-left: 15px; /* Reduce margin to save space */
}


/* Payment Method Legend */
.payment-method-legend {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    margin-left: 20px;
}

.payment-method-legend div {
    display: flex;
    align-items: center;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
}

.payment-method-legend span {
    margin-left: 10px;
    color: #333;
}

.payment-color-box {
    width: 15px;
    height: 15px;
    border-radius: 3px;
}

.top-customer-container {
    display: flex;
    gap: 10px; /* Space between chart and table */
    justify-content: space-between;
    width: 100%;
    margin-top: 10px;
}

.top-customer, .paid-invoices {
    width: 50%; /* Each takes almost half the width */
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.15);
    padding: 20px;
    display: flex;
    flex-direction: column;
}

.customer-wrapper {
    display: flex;
    flex-direction: column;
    align-items: left;
    width: 100%;
    margin: auto;
}

.customer-wrapper h3, .paid-invoices h3 {
    font-size: 1rem;
    font-weight: bold;
    color: #0a1d4e;
    text-align: left;

}

/* Chart Styling */
#customerOverviewChart {
    width: 100% !important;
    max-width: 100%;
    height: auto !important;
    max-height: 300px;
}

/* Table Styling */
.paid-invoices table {
    width: 100%;
    border-collapse: collapse;
}

.paid-invoices th, .paid-invoices td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

.paid-invoices th {
    background-color: #0a1d4e;
    color:#fff;
    font-size: 13px;
}

.invoice-container {
    width: 100%;
    margin: 10px auto;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
}

.all-invoices table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.all-invoices th, .all-invoices td {
    padding: 5px;
    border: 1px solid #ddd;
    text-align: center;
}

.all-invoices th {
    background-color: #0a1d4e;
    color: white;
}

.invoice-row:hover {
    background-color: #f1b0b7 !important;
    cursor: pointer;
}

/* Pagination */
.pagination {
    text-align: center;
    margin-top: 20px;
}

.pagination-btn {
    display: inline-block;
    padding: 8px 15px;
    margin: 0 5px;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}

.pagination-btn:hover {
    background-color: #0056b3;
}

.page-number {
    font-weight: bold;
}


</style>


        <main>



        <style>
    /* Container for both forms */
    .form-container {
        display: flex;
        justify-content: flex-end; /* Align forms to the right */
        align-items: center;
        gap: 10px;
    }

    /* Styling for filter form */
    #filter-form label {
        font-weight: bold;
    }

    #filter-form select, #filter-form button, 
    #export-form button {
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 14px;
    }

    #filter-form select {
        background-color: #f9f9f9;
        cursor: pointer;
    }

    #filter-form button, #export-form button {
        background-color: #0a1d4e;
        color: white;
        border: none;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    #filter-form button:hover, #export-form button:hover {
        background-color: #0056b3;
    }

    /* Responsive adjustments */
    @media (max-width: 600px) {
        .form-container {
            flex-direction: column;
            align-items: stretch;
        }

        #filter-form select, #filter-form button,
        #export-form button {
            width: 100%;
        }
    }
</style>


        <div class="kpi-metrics">
        <div class="kpi-card">
            <div class="kpi-title">Total Income (Current Month)</div>
            <div class="kpi-value"><?php echo '₱' . number_format($totalIncome, 2); ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Total Expenses (Current Month)</div>
            <div class="kpi-value"><?php echo '₱' . number_format($totalExpenses, 2); ?></div>
        </div>

        <div class="kpi-card">
            <div class="kpi-title">Net Income (Current Month)</div>
            <div class="kpi-value"><?php echo '₱' . number_format($netIncome, 2); ?></div>
        </div>

    
    <div class="kpi-card">
        <div class="kpi-title">Total Customers</div>
        <div class="kpi-value">
            <?php 
            // Fetch the total number of customers
            $customerQuery = "SELECT COUNT(DISTINCT id) AS total_customers FROM customers";
            $customerResult = $connection->query($customerQuery);
            $customerData = $customerResult->fetch_assoc();
            $totalCustomers = $customerData['total_customers'];
            echo number_format($totalCustomers);
            ?>
        </div>
    </div>
</div>

<br>
<div class="dashboard-container">

<div class="chart-container">
                <h3 style="color: #0a1d4e;">Cash Flow</h3>
                        <canvas id="cashFlowChart" height="100"></canvas>
                        <table border="1" width="100%">
    <thead style="background-color: #0a1d4e; color: #fff;">
        <tr>
            <th>Category</th>
            <?php 
            $visibleData = array_filter($cash_flow_data, fn($row) => empty($row['is_forecast'])); // Exclude forecast
            $months = array_column($visibleData, 'month');
            foreach (array_slice($months, 0, 6) as $month) : ?>
                <th><?php echo $month; ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <!-- Income Row -->
        <tr>
            <td>Income</td>
            <?php foreach (array_slice($visibleData, 0, 6) as $row) : ?>
                <td><?php echo number_format($row['current_income'], 2); ?></td>
            <?php endforeach; ?>
        </tr>

        <!-- Expenses Row -->
        <tr>
            <td>Expenses</td>
            <?php foreach (array_slice($visibleData, 0, 6) as $row) : ?>
                <td><?php echo number_format($row['current_expenses'], 2); ?></td>
            <?php endforeach; ?>
        </tr>
    </tbody>
</table>

<!-- Pagination -->
<div id="pagination" style="text-align: center; margin-top: 10px;">
    <button onclick="changePage(-1)" id="prevBtn" style="background: none; border: none; font-size: 18px; cursor: pointer;">⬅</button>
    <span id="pageNumber" style="font-weight: bold; margin: 0 10px;">1</span>
    <button onclick="changePage(1)" id="nextBtn" style="background: none; border: none; font-size: 18px; cursor: pointer;">➡</button>
</div>

                    </div>
            <div class="meter-container" style="width: 30%;">
    <!-- Budget Meter -->
    <div class="budget-meter">
    <h3>Monthly Budget</h3>
    <p>Total Budget: <?= number_format($totalBudget, 2); ?></p> <!-- Always the same -->
    
    <div class="meter">
        <div class="fill" style="width: <?= $budgetUsage; ?>%">
            <span><?= number_format($approvedBudget, 2); ?></span> <!-- Display usage -->
        </div>
    </div>

    <p>Used: <?= number_format($budgetUsage, 2); ?>% | Remaining: <?= number_format($remainingBudget, 2); ?>%</p>
    <button class="update-budget-btn" onclick="openUpdateBudgetModal()">Update Budget</button>
    <button class="budget-report-btn" onclick="redirectToBudgetReport()">Expense Budget</button>
</div>

<!-- Monthly Income Goal Meter -->
<div class="income-goal-meter">
    <h3>Monthly Income Goal</h3>
    <p>Target Income: <?= number_format($incomeGoal, 2); ?></p>
    <div class="meter">
        <div class="fill" style="width: <?= $incomeProgress; ?>%">
            <span><?= number_format($totalIncome, 2); ?></span> <!-- Display income amount inside the bar -->
        </div>
    </div>
    <p>Achieved: <?= number_format($incomeProgress, 2); ?>% | Remaining: <?= number_format(max(0, 100 - $incomeProgress), 2); ?>%</p>
    <button class="update-income-goal-btn" onclick="openUpdateIncomeGoalModal()">Update Goal</button>
    <button class="income-report-btn">Income Report</button>
    </div>
</div>  
</div>

<div class="payment-tax-container">
    <div class="income-expenses-container">
        <h3>Income Breakdown by Payment Method</h3>
        <div class="chart-wrapper">
            <canvas id="paymentMethodChart"></canvas>
            <div id="paymentMethodLegend" class="payment-method-legend"></div>
        </div>
    </div>

    <div class="tax-container">
        <h3>Tax Report</h3>
        <canvas id="taxDeductionChart" widht="100%;"></canvas>
    </div>
</div>

<div class="top-customer-container">
    <!-- Customer Overview Chart -->
    <div class="top-customer">
        <div class="customer-wrapper">
            <h3>Customer Overview</h3>
            <canvas id="customerOverviewChart"></canvas>
        </div>
    </div>

    <div class="paid-invoices">
    <h3>Paid Invoices (Current Month)</h3>
    <table id="paidInvoicesTable">
        <thead>
            <tr>
                <th>Invoice ID</th>
                <th>Customer Name</th>
                <th>Total Amount</th>
                <th>Paid Date</th>
            </tr>
        </thead>
        <tbody id="invoiceTableBody">
            <?php

            $currentMonth = date('m');
            $currentYear = date('Y');

            $sql = "SELECT i.id AS invoice_id, c.id AS customer_id, c.name AS customer_name, 
                           i.total_amount, i.payment_date 
                    FROM invoices i
                    INNER JOIN customers c ON i.customer_id = c.id
                    WHERE i.payment_status = 'paid' 
                    AND MONTH(i.payment_date) = ? 
                    AND YEAR(i.payment_date) = ?";

            $stmt = $connection->prepare($sql);
            $stmt->bind_param("ii", $currentMonth, $currentYear);
            $stmt->execute();
            $result = $stmt->get_result();

            $invoices = [];

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $invoices[] = $row;
                    echo "<tr class='invoice-row' 
                data-customer-id='{$row['customer_id']}' 
                data-invoice-id='{$row['invoice_id']}'
                onclick='fetchTransactionHistory({$row['customer_id']})' 
                style='cursor:pointer;' 
                onmouseover=\"this.style.backgroundColor='#f1b0b7';\" 
                onmouseout=\"this.style.backgroundColor='';\">
                <td>{$row['invoice_id']}</td>
                <td>{$row['customer_name']}</td>
                <td>₱" . number_format($row['total_amount'], 2) . "</td>
                <td>" . date("M d, Y", strtotime($row['payment_date'])) . "</td>
            </tr>";
                }
            } else {
                echo "<tr><td colspan='4' style='text-align: center;'>No paid invoices</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div id="pagination" style="text-align: center; margin-top: 10px;">
        <button onclick="changePage(-1)" id="prevBtn" style="background: none; border: none; font-size: 18px; cursor: pointer;">⬅</button>
        <span id="pageNumber" style="font-weight: bold; margin: 0 10px;">1</span>
        <button onclick="changePage(1)" id="nextBtn" style="background: none; border: none; font-size: 18px; cursor: pointer;">➡</button>
    </div>
</div>
        </div>
        <div class="invoice-container">
    <h3>All Invoices</h3>
    <div class="all-invoices">
    <table id="allInvoicesTable">
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    <th>Customer Name</th>
                    <th>Total Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="invoiceTableBody">
            <?php
    // Update overdue status for unpaid invoices
    $updateStatusSql = "UPDATE invoices 
                        SET payment_status = 'overdue' 
                        WHERE payment_status != 'paid' 
                        AND due_date < CURDATE()";
    $connection->query($updateStatusSql);

    $limit = 5; // Invoices per page
    $page = isset($_GET['page']) ? $_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Count total invoices for pagination
    $countSql = "SELECT COUNT(*) AS total FROM invoices";
    $countResult = $connection->query($countSql);
    $totalInvoices = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalInvoices / $limit);

    // Fetch invoices with LIMIT for pagination
    $sql = "SELECT i.id AS invoice_id, c.id AS customer_id, c.name AS customer_name, 
                   i.total_amount, i.due_date, i.payment_status 
            FROM invoices i
            INNER JOIN customers c ON i.customer_id = c.id
            ORDER BY i.payment_date DESC
            LIMIT ?, ?";
    
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr class='invoice-row' 
                    data-customer-id='{$row['customer_id']}' 
                    data-invoice-id='{$row['invoice_id']}'
                    onclick='fetchTransactionHistory({$row['customer_id']})' 
                    style='cursor:pointer;' 
                    onmouseover=\"this.style.backgroundColor='#f1b0b7';\" 
                    onmouseout=\"this.style.backgroundColor='';\">
                    <td>{$row['invoice_id']}</td>
                    <td>{$row['customer_name']}</td>
                    <td>₱" . number_format($row['total_amount'], 2) . "</td>
                    <td>" . date("M d, Y", strtotime($row['due_date'])) . "</td>
                    <td>{$row['payment_status']}</td>
                </tr>";
        }
    } else {
        echo "<tr><td colspan='5' style='text-align: center;'>No invoices found</td></tr>";
    }
?>

            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="pagination-btn">⬅ Prev</a>
        <?php endif; ?>

        <span class="page-number">Page <?= $page ?> of <?= $totalPages ?></span>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="pagination-btn">Next ➡</a>
        <?php endif; ?>
    </div>
</div>




              
<style>
    #updateIncomeGoalModal, #updateBudgetModal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }
    #updateIncomeGoalModal .modal-content, #updateBudgetModal .modal-content{
        background-color: #fff;
        margin: 15% auto;
        padding: 20px;
        border-radius: 10px;
        width: 50%;
        text-align: center;
    }
    #updateIncomeGoalModal .close, #updateBudgetModal .close {
        float: right;
        font-size: 24px;
        cursor: pointer;
    }
    #updateIncomeGoalModal form, #updateBudgetModal form {
    max-width: auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
}
#updateIncomeGoalModal input, #updateBudgetModal input[type="number"] {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ccc;
}
#updateIncomeGoalModal button, #updateBudgetModal button {
    background-color: #ed6978;
    color: white;
    padding: 10px;
    border: none;
    cursor: pointer;
}
button:hover {
    background-color: #d1697b;
}
</style>

<!-- Update Income Goal Modal -->
<div id="updateIncomeGoalModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeUpdateIncomeGoalModal()">&times;</span>

        <form method="post">
            <label for="income_goal_amount">Set New Income Goal:</label>
            <input type="number" step="0.01" name="income_goal_amount" id="income_goal_amount" value="<?= number_format($incomeGoal, 2) ?>" required>
            <button type="submit">Update Goal</button>
        </form>
    </div>
</div>

<!-- JavaScript for Income Goal Modal -->
<script>
    function openUpdateIncomeGoalModal() {
        document.getElementById("updateIncomeGoalModal").style.display = "block";
    }

    function closeUpdateIncomeGoalModal() {
        document.getElementById("updateIncomeGoalModal").style.display = "none";
    }
</script>


<!-- Update Budget Modal -->
<div id="updateBudgetModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeUpdateBudgetModal()">&times;</span>
        

<form method="post">
    <label for="budget_amount">Set New Budget:</label>
    <input type="number" step="0.01" name="budget_amount" id="budget_amount" value="<?= number_format($currentBudget, 2) ?>" required>
    <button type="submit">Update Budget</button>
</form>
    </div>
</div>

<!-- JavaScript for Modal -->
<script>
    function openUpdateBudgetModal() {
        document.getElementById("updateBudgetModal").style.display = "block";
    }

    function closeUpdateBudgetModal() {
        document.getElementById("updateBudgetModal").style.display = "none";
    }
</script>

    </main>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/countup.js/2.0.7/countUp.umd.js"></script>

<script>
function redirectToBudgetReport() {
    let currentMonth = new Date().getMonth() + 1; // JS months are 0-based
    let currentYear = new Date().getFullYear();
    window.location.href = `budget_report.php?month=${currentMonth}&year=${currentYear}`;
}
</script>
<script>
  const paymentLabels = <?php echo json_encode(array_keys($paymentMethods)); ?>;
  const paymentData = <?php echo json_encode(array_values($paymentMethods)); ?>;
  const ctxPayment = document.getElementById('paymentMethodChart').getContext('2d');

  // Calculate total income safely
  const totalIncome = paymentData.reduce((acc, val) => acc + parseFloat(val || 0), 0);

  // Create Doughnut Chart with Percentage Inside
  const paymentMethodChart = new Chart(ctxPayment, {
      type: 'doughnut',
      data: {
          labels: paymentLabels,
          datasets: [{
              label: 'Total Income (₱)',
              data: paymentData,
              backgroundColor: ['#1E88E5', '#43A047', '#FB8C00', '#6D4C41', '#8E24AA', '#D81B60'],
              borderColor: '#ffffff',
              borderWidth: 2
          }]
      },
      options: {
          responsive: true,
          cutout: '30%', // Creates a thinner ring
          plugins: {
              legend: {
                  display: false // Hide default legend (we'll create a custom one)
              },
              tooltip: {
                  callbacks: {
                      label: function(tooltipItem) {
                          const value = tooltipItem.raw;
                          const percentage = totalIncome > 0 ? ((value / totalIncome) * 100).toFixed(1) : "0";
                          return `₱${parseFloat(value).toLocaleString()} (${percentage}%)`;
                      }
                  }
              },
              datalabels: {
                  color: '#fff',
                  font: {
                      weight: 'bold',
                      size: 14
                  },
                  formatter: function(value) {
                      const percentage = totalIncome > 0 ? ((value / totalIncome) * 100).toFixed(1) : "0";
                      return percentage + '%'; // Show percentage inside chart
                  }
              }
          }
      },
      plugins: [ChartDataLabels] // Activate DataLabels Plugin
  });

  // **💡 Styled Custom Legend**
  const legendContainer = document.getElementById('paymentMethodLegend');
  legendContainer.innerHTML = paymentLabels.map((label, index) => {
      const value = paymentData[index];
      const percentage = totalIncome > 0 ? ((value / totalIncome) * 100).toFixed(1) : "0";
      return `
          <div style="display: flex; align-items: center; margin-bottom: 8px;">
              <div class="payment-color-box" style="width: 14px; height: 14px; background-color: ${paymentMethodChart.data.datasets[0].backgroundColor[index]}; margin-right: 10px; border-radius: 50%;"></div>
              <span>${label}: <strong>₱${parseFloat(value).toLocaleString()} (${percentage}%)</strong></span>
          </div>
      `;
  }).join('');
</script>

<script>
   // Prepare the data for Chart.js
var quarters = <?php echo $quarters_json; ?>;
var incomeData = <?php echo $income_json; ?>;
var taxData = <?php echo $tax_json; ?>;

// Create the chart
var ctx = document.getElementById('taxDeductionChart').getContext('2d');
var taxDeductionChart = new Chart(ctx, {
    type: 'bar', // Change to 'line' if you prefer
    data: {
        labels: quarters.map(function(quarter) {
            switch(quarter) {
                case 0: return 'Q1';
                case 1: return 'Q2';
                case 2: return 'Q3';
                case 3: return 'Q4';
            }
        }),
        datasets: [
            {
                label: 'Quarterly Income (₱)',
                data: incomeData,
                backgroundColor: '#30c0dd',
                borderColor: '#30c0dd',
                borderWidth: 1
            },
            {
                label: 'Tax Deduction (₱)',
                data: taxData,
                backgroundColor: '#FF6384',
                borderColor: '#FF6384',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return '₱ ' + tooltipItem.raw.toFixed(2); // Format tooltips
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱ ' + value.toFixed(2);
                    }
                }
            }
        }
    }
});
</script>




<script>
// Parse the PHP data into JavaScript


var incomeExpensesData = <?php echo json_encode($cash_flow_data); ?> || [];

if (!Array.isArray(incomeExpensesData)) {
    incomeExpensesData = [];
}

console.log(incomeExpensesData); // Debugging

const monthLabels = incomeExpensesData.map(data => data.month || "No Date");

// Actual Cash Flow Data (Null where forecast starts)
const cashFlowValues = incomeExpensesData.map(data => 
    data.is_forecast ? null : (data.current_income || 0) - (data.current_expenses || 0)
);
const lastYearCashFlowValues = incomeExpensesData.map(data => (data.last_year_income || 0) - (data.last_year_expenses || 0));

// Forecasted Cash Flow (Extends from last actual data)
let forecastCashFlowValues = [];
let lastActualCashFlow = null;

incomeExpensesData.forEach((data, index) => {
    if (data.is_forecast) {
        let forecastValue = (data.current_income || 0) - (data.current_expenses || 0);
        forecastCashFlowValues.push(forecastValue);
    } else {
        lastActualCashFlow = (data.current_income || 0) - (data.current_expenses || 0);
        forecastCashFlowValues.push(lastActualCashFlow);
    }
});

console.log(forecastCashFlowValues); // Debugging

let warningFlags = []; // Array to store warning flags

incomeExpensesData.forEach((data, index) => {
    let currentCashFlow = (data.current_income || 0) - (data.current_expenses || 0);
    let lastMonthCashFlow = index > 0 ? (incomeExpensesData[index - 1].current_income || 0) - (incomeExpensesData[index - 1].current_expenses || 0) : null;

    if (data.is_forecast) {
        let forecastValue = (data.current_income || 0) - (data.current_expenses || 0);
        forecastCashFlowValues.push(forecastValue);
        warningFlags.push(false); // No warning for forecast
    } else {
        forecastCashFlowValues.push(currentCashFlow);
        
        // Check for warning conditions
        if (currentCashFlow < 0 || (lastMonthCashFlow && currentCashFlow <= lastMonthCashFlow * 0.5)) {
            warningFlags.push(true); // Add warning if cash flow is negative or <= 50% of last month
        } else {
            warningFlags.push(false); // No warning
        }
    }
});

// **🟢 Updated Cash Flow Chart with Forecast Shade**
const cashFlowChart = new Chart(document.getElementById('cashFlowChart'), {
    type: 'line',
    data: {
        labels: monthLabels,
        datasets: [
            {
                label: 'Current Year Cash Flow',
                data: cashFlowValues,
                fill: true,
                backgroundColor: (context) => {
                    let index = context.dataIndex;
                    return warningFlags[index] ? 'rgba(255, 99, 132, 0.2)' : 'rgba(10, 29, 78, 0.2)'; // Red shade for warning
                },
                borderColor: (context) => {
                    let index = context.dataIndex;
                    return warningFlags[index] ? '#ff6384' : '#0a1d4e'; // Red border for warning
                },
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: (context) => {
                    let index = context.dataIndex;
                    return warningFlags[index] ? '#ff6384' : '#30c0dd'; // Red points for warning
                },
                tension: 0.4
            },
            {
                label: 'Last Year Cash Flow',
                data: lastYearCashFlowValues,
                fill: true,
                backgroundColor: 'rgba(99, 247, 255, 0.2)',
                borderColor: '#rgb(99, 247, 255)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: 'rgb(99, 247, 255)',
                tension: 0.4
            },
            {
                label: 'Forecasted Cash Flow',
                data: forecastCashFlowValues,
                fill: true,
                backgroundColor: 'rgba(0, 255, 0, 0.2)',
                borderColor: 'rgba(0, 255, 0, 1)',
                borderDash: [5, 5],
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#00ff00',
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<script>
document.querySelector(".income-report-btn").addEventListener("click", function() {
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth() + 1; // JavaScript months are 0-based
    const currentYear = currentDate.getFullYear();

    window.location.href = `income_report.php?month=${currentMonth}&year=${currentYear}`;
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
    const overdueCount = document.getElementById("overdue-count");

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
        const seconds = Math.floor((now - past) / 1000);

        if (seconds < 60) return `${seconds} seconds ago`;
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes} minutes ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours} hours ago`;
        const days = Math.floor(hours / 24);
        if (days < 7) return `${days} days ago`;
        const weeks = Math.floor(days / 7);
        if (weeks < 4) return `${weeks} weeks ago`;
        const months = Math.floor(days / 30);
        if (months < 12) return `${months} months ago`;
        const years = Math.floor(days / 365);
        return `${years} years ago`;
    }

    function fetchNotifications() {
        fetch('fetch_notifications.php')
            .then(response => response.json())
            .then(data => {
                notificationDropdown.innerHTML = "";
                notificationDropdown.appendChild(arrow);
                notificationDropdown.appendChild(header);

                if (data.length > 0) {
                    data.forEach(item => {
                        let link = "";
                        let statusLabel = "";
                        let labelColor = "";

                        if (item.type === "invoice") {
                            link = `invoice.php?id=${item.id}`;
                            statusLabel = "Overdue";
                            labelColor = "red";
                        } else if (item.type === "request") {
                            link = item.status === "Pending" ? `request_form.php?id=${item.id}` : `staff_update_request.php?id=${item.id}`;
                            statusLabel = item.status;
                            labelColor = item.status === "Pending" ? "orange" : "blue";
                        }

                        let notificationItem = document.createElement("div");
                        notificationItem.innerHTML = `
                            <a href="${link}" class="notification-item" data-id="${item.id}" style="
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
                                    <strong style="font-size: 15px; color: #222;">${item.type === "invoice" ? "Invoice #" + item.id : "Request #" + item.id}</strong><br>
                                    ${item.type === "invoice" ? `<span style="color: #555; font-size: 13px;">Customer: ${item.customer_name}</span><br>` : `<span style="color: #555; font-size: 13px;">Type: ${item.request_type}</span><br>`}
                                    <span style="
                                        display: inline-block;
                                        background: ${labelColor};
                                        color: white;
                                        font-size: 12px;
                                        font-weight: bold;
                                        padding: 3px 8px;
                                        border-radius: 5px;
                                        margin-top: 5px;
                                    ">${statusLabel}</span>
                                    <br>
                                    <span style="font-size: 12px; color: #888;">${timeAgo(item.created_at)}</span>
                                </div>
                            </a>
                        `;
                        notificationDropdown.appendChild(notificationItem);
                    });

                    document.querySelectorAll(".notification-item").forEach(item => {
                        item.addEventListener("click", function(event) {
                            let currentCount = parseInt(overdueCount.innerText);
                            if (currentCount > 0) {
                                overdueCount.innerText = currentCount - 1;
                                if (currentCount - 1 === 0) {
                                    overdueCount.style.display = "none";
                                }
                            }
                        });
                    });

                } else {
                    notificationDropdown.innerHTML += "<p style='text-align:center; padding: 20px; font-size: 14px; color: #555;'>No notifications</p>";
                }

                overdueCount.innerText = data.length;
                overdueCount.style.display = data.length > 0 ? "inline-block" : "none";
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
    let currentPage = 0;
    const columnsPerPage = 6;
    const cashFlowData = <?php echo json_encode($visibleData); ?>;
    const totalPages = Math.ceil(cashFlowData.length / columnsPerPage);

    function renderTable() {
        const tableHead = document.querySelector("thead tr");
        const incomeRow = document.querySelector("tbody tr:nth-child(1)");
        const expensesRow = document.querySelector("tbody tr:nth-child(2)");

        tableHead.innerHTML = "<th>Category</th>";
        incomeRow.innerHTML = "<td>Income</td>";
        expensesRow.innerHTML = "<td>Expenses</td>";

        const start = currentPage * columnsPerPage;
        const end = start + columnsPerPage;
        const paginatedData = cashFlowData.slice(start, end);

        paginatedData.forEach(row => {
            tableHead.innerHTML += `<th>${row.month}</th>`;
            incomeRow.innerHTML += `<td>${parseFloat(row.current_income).toFixed(2)}</td>`;
            expensesRow.innerHTML += `<td>${parseFloat(row.current_expenses).toFixed(2)}</td>`;
        });

        document.getElementById("pageNumber").innerText = `${currentPage + 1} / ${totalPages}`;
        document.getElementById("prevBtn").disabled = currentPage === 0;
        document.getElementById("nextBtn").disabled = currentPage === totalPages - 1;
    }

    function changePage(direction) {
        currentPage = Math.max(0, Math.min(currentPage + direction, totalPages - 1));
        renderTable();
    }

    renderTable();
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    var ctx = document.getElementById('customerOverviewChart').getContext('2d');

    var customerOverviewChart = new Chart(ctx, {
        type: 'bar', // Keep 'bar' type
        data: {
            labels: <?php echo $customerNames; ?>, // Customer names as labels
            datasets: [{
                label: 'Total Spending (₱)',
                data: <?php echo $totalSpending; ?>, // Customer spending
                backgroundColor: '#4767B1',
                borderColor: '#388E3C',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y', // **Makes it horizontal**
            scales: {
                x: { // Controls the X-axis (spending amounts)
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return '₱' + value.toLocaleString(); // Format as currency
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false // Hides legend
                },
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            return '₱' + tooltipItem.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});

</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const invoices = <?php echo $jsonInvoices; ?>;
        const rowsPerPage = 5;
        let currentPage = 1;

        function renderTable(page) {
            const tableBody = document.getElementById("invoiceTableBody");
            tableBody.innerHTML = "";

            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const paginatedItems = invoices.slice(start, end);

            paginatedItems.forEach(row => {
                tableBody.innerHTML += `
                    <tr>
                        <td>${row.invoice_id}</td>
                        <td>${row.customer_name}</td>
                        <td>₱${parseFloat(row.total_amount).toLocaleString()}</td>
                        <td>${row.paid_date}</td>
                    </tr>
                `;
            });

            document.getElementById("pageNumber").innerText = page;
            document.getElementById("prevBtn").disabled = (page === 1);
            document.getElementById("nextBtn").disabled = (end >= invoices.length);
        }

        window.changePage = function (direction) {
            const newPage = currentPage + direction;
            if (newPage > 0 && newPage <= Math.ceil(invoices.length / rowsPerPage)) {
                currentPage = newPage;
                renderTable(currentPage);
            }
        };

        renderTable(currentPage);
    });
</script>

<script>
    function fetchTransactionHistory(customerId) {
        console.log("Fetching transaction history for customer ID:", customerId); // Debug log

        fetch('get_customer_transactions.php?customer_id=' + customerId)
            .then(response => response.text())
            .then(data => {
                console.log("Response received:", data); // Debug response
                document.getElementById("transactionContent").innerHTML = data;
                document.getElementById("transactionHistoryPanel").classList.add("open");
            })
            .catch(error => console.error("Error fetching transaction history:", error));
    }

    function toggleMaximize() {
        let panel = document.getElementById("transactionHistoryPanel");
        let maximizeBtn = document.getElementById("maximizeBtn");

        panel.classList.toggle("maximized");

        if (panel.classList.contains("maximized")) {
            maximizeBtn.innerHTML = "🗕"; 
        } else {
            maximizeBtn.innerHTML = "🗖"; 
        }
    }

    function closePanel() {
        document.getElementById("transactionHistoryPanel").classList.remove("open", "maximized");
        document.getElementById("maximizeBtn").innerHTML = "🗖"; 
    }

    function downloadTransactionHistory() {
        let table = document.querySelector("#transactionContent table");
        if (!table) {
            alert("No transaction history to download.");
            return;
        }

        let rows = Array.from(table.querySelectorAll("tr"));
        let csvContent = rows.map(row => {
            let columns = Array.from(row.querySelectorAll("td, th")).map(cell => `"${cell.innerText}"`);
            return columns.join(",");
        }).join("\n");

        let blob = new Blob([csvContent], { type: "text/csv" });
        let link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = "transaction_history.csv";
        link.click();
    }

    document.addEventListener("DOMContentLoaded", function () {
        console.log("Checking if transactionHistoryPanel is in the DOM:", document.getElementById("transactionHistoryPanel"));
    });

</script>
</body>
</html>