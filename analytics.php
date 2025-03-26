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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/countup.js/2.0.7/countUp.umd.js"></script>

</head>
<body>

<?php

session_start();
include('assets/databases/dbconfig.php');

$timeout_duration = 600;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

// Restrict access if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if session token matches the one stored in the database
$sql = "SELECT session_token FROM users WHERE id = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($stored_token);
$stmt->fetch();
$stmt->close();

if ($_SESSION['session_token'] !== $stored_token) {
    session_unset();
    session_destroy();
    header("Location: login.php?session_expired=1");
    exit();
}

$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];

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

$month = isset($_GET['month']) ? $_GET['month'] : date('m'); // Get month from filter or default to current month
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');   // Get year from filter or default to current year

// Query to fetch COGS for the selected month and year
$cogsQuery = "SELECT SUM(total_cogs) AS amount 
              FROM cogs 
              WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?";

$cogsStmt = $connection->prepare($cogsQuery);
$cogsStmt->bind_param("ii", $currentMonth, $currentYear);
$cogsStmt->execute();
$cogsResult = $cogsStmt->get_result();
$cogsRow = $cogsResult->fetch_assoc();
$cogs = $cogsRow['amount'] ?? 0;


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
$netIncome = $total_income - $totalExpenses - $cogs;

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
current_expenses AS (
    SELECT DATE_FORMAT(expense_date, '%Y-%m') AS month, SUM(amount) AS total_expenses
    FROM employee_expenses
    WHERE YEAR(expense_date) = YEAR(CURDATE())
    GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
),
current_payroll AS (
    SELECT DATE_FORMAT(processed_at, '%Y-%m') AS month, SUM(net_pay) AS total_payroll
    FROM payroll
    WHERE YEAR(processed_at) = YEAR(CURDATE())
    GROUP BY DATE_FORMAT(processed_at, '%Y-%m')
)
SELECT 
    m.month,
    COALESCE(c.total_income, 0) AS current_income,
    COALESCE(ce.total_expenses, 0) + COALESCE(cp.total_payroll, 0) AS current_expenses
FROM months m
LEFT JOIN current_year c ON m.month = c.month
LEFT JOIN current_expenses ce ON m.month = ce.month
LEFT JOIN current_payroll cp ON m.month = cp.month
ORDER BY m.month ASC;";

$result = $connection->query($sql_income_expenses);

// Fetch data from MySQL
$cash_flow_data = [];
while ($row = $result->fetch_assoc()) {
    $cash_flow_data[] = [
        'month' => $row['month'],
        'income' => (float) $row['current_income'],
        'expenses' => (float) $row['current_expenses']
    ];
}

// Debugging: Print cash flow data before calling Python
if (empty($cash_flow_data)) {
    die("Error: No income/expenses data retrieved.");
}

// Encode data and pass it to Python script
$input_data_encoded = base64_encode(json_encode($cash_flow_data));
$python_path = "C:\\Users\\Admin\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
$script_path = "C:\\xampp\\htdocs\\Capstoneproject\\forecast.py";

$forecast_output = shell_exec("$python_path $script_path " . escapeshellarg($input_data_encoded) . " 2>&1");

// Debugging: Print Python script output
if ($forecast_output === null) {
    die("Error: Python script did not return any output.");
}

$forecast_data = json_decode($forecast_output, true);

// Debugging: Print forecast output
if ($forecast_data === null) {
    die("Invalid JSON from Python: " . htmlspecialchars($forecast_output));
}

// Merge actual and forecasted data
$cash_flow_data = array_merge($cash_flow_data, $forecast_data);

// Pass data to JavaScript
echo "<script>var cashFlowData = " . json_encode($cash_flow_data) . ";</script>";

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
                <li><a href="account_receivable.php"><span class="las la-file-invoice"></span> Invoice</a></li>
            </ul>
        </li>
        <li>
                <a href="index.php"><span class="las la-file-invoice"></span>
                <span>Payroll</span></a>
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
                Dashboard
                </h2>
                </div>

                <!-- Wrap the forms inside a container -->
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
    background: linear-gradient(135deg, #0a1d4e, #003080);
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

.kpi-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr); /* 3 cards per row */
    gap: 10px;
    margin-top: 10px;
}

.card {
    background: linear-gradient(135deg, #0a1d4e, #003080);
    color: white;
    padding: 10px;
    border-radius: 8px;
    text-align: center;
    font-size: 13px;
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
                <td><?php echo "₱" .number_format($row['income'], 2); ?></td>
            <?php endforeach; ?>
        </tr>

        <!-- Expenses Row -->
        <tr>
            <td>Expenses</td>
            <?php foreach (array_slice($visibleData, 0, 6) as $row) : ?>
                <td><?php echo "₱" .number_format($row['expenses'], 2); ?></td>
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
    <button class="budget-report-btn" onclick="redirectToBudgetReport()">Budget Utilization</button>
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
        <h3>Income Overview</h3>
        <div class="kpi-cards">
    <div class="card">
        <h4>Paid Invoice</h4>
        <h5 id="paidInvoice">₱0</h5>
    </div>
    <div class="card">
        <h4>Overdue</h4>
        <h5 id="over">₱0</h5>
    </div>
    <div class="card">
        <h4>Unpaid Invoice</h4>
        <h5 id="unpaid">₱0</h5>
    </div>
</div>
        <div class="chart-wrapper">
            <canvas id="kpiChart"></canvas>
        </div>
    </div>

    <div class="tax-container">
        <h3>Tax Report</h3>
        <br>
        <br>
        <br>
        <canvas id="taxDeductionChart" widht="100%;"></canvas>
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
function formatCurrency(value) {
    return `₱${value.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
}

// Parse data from PHP
var cashFlowData = cashFlowData || [];
const monthLabels = cashFlowData.map(data => data.month);

// Extract actual and forecasted cash flow
const actualCashFlow = cashFlowData.map(data => data.income - data.expenses);
const forecastedCashFlow = cashFlowData.map(data => data.is_forecast ? data.income - data.expenses : null);
const previousYearCashFlow = cashFlowData.map(data => data.prev_year_cashflow || null);
const anomalies = cashFlowData.map(data => data.anomaly ? data.income - data.expenses : null);

// Warning alerts for sudden cash flow drop
cashFlowData.forEach((data, index) => {
    if (data.anomaly) {
        console.warn(`🚨 Alert: Sudden drop detected in ${data.month}!`);
    }
});

const cashFlowChart = new Chart(document.getElementById('cashFlowChart'), {
    type: 'line',
    data: {
        labels: monthLabels,
        datasets: [
            {
                label: 'Actual Cash Flow',
                data: actualCashFlow,
                fill: true,
                backgroundColor: 'rgba(10, 29, 78, 0.2)',
                borderColor: '#0a1d4e',
                borderWidth: 2,
                pointRadius: 3,
                tension: 0.4
            },
            {
                label: 'Forecasted Cash Flow',
                data: forecastedCashFlow,
                fill: true,
                backgroundColor: 'rgba(0, 255, 0, 0.2)',
                borderColor: 'rgba(0, 255, 0, 1)',
                borderDash: [5, 5],
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#00ff00',
                tension: 0.4
            },
            {
                label: 'Previous Year Cash Flow',
                data: previousYearCashFlow,
                fill: true,
                backgroundColor: 'rgba(255, 165, 0, 0.2)',
                borderColor: 'rgba(255, 165, 0, 1)',
                borderWidth: 2,
                borderDash: [5, 5],
                pointRadius: 3,
                pointBackgroundColor: 'orange',
                tension: 0.4
            },
            {
                label: 'Anomalies (Sudden Drop)',
                data: anomalies,
                fill: true,
                backgroundColor: 'rgba(255, 0, 0, 0.2)',
                borderColor: 'red',
                borderWidth: 2,
                pointRadius: 6,
                pointBackgroundColor: 'red',
                tension: 0.4
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
                        return formatCurrency(tooltipItem.raw || 0);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return formatCurrency(value);
                    }
                }
            }
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
    const invoices = <?php echo json_encode($invoices); ?>;
    const rowsPerPage = 5;
    let currentPage = 1;
    let totalPages = Math.ceil(invoices.length / rowsPerPage);

    console.log("Invoices Data:", invoices);
    console.log("Total Invoices:", invoices.length);
    console.log("Total Pages:", totalPages);

    function renderTable(page) {
        console.log("Rendering page:", page);
        const tableBody = document.getElementById("invoiceTableBody");
        tableBody.innerHTML = "";

        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const paginatedItems = invoices.slice(start, end);

        paginatedItems.forEach(row => {
            tableBody.innerHTML += `
                <tr class='invoice-row' 
                    data-customer-id='${row.customer_id}' 
                    data-invoice-id='${row.invoice_id}'
                    onclick='fetchTransactionHistory(${row.customer_id})' 
                    style='cursor:pointer;' 
                    onmouseover="this.style.backgroundColor='#f1b0b7';" 
                    onmouseout="this.style.backgroundColor='';">
                    <td>${row.invoice_id}</td>
                    <td>${row.customer_name}</td>
                    <td>₱${parseFloat(row.total_amount).toLocaleString()}</td>
                    <td>${new Date(row.payment_date).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })}</td>
                </tr>
            `;
        });

        document.getElementById("pageNumber1").innerText = page;
        document.getElementById("prevBtn1").disabled = (page === 1);
        document.getElementById("nextBtn1").disabled = (page >= totalPages);
    }

    window.changePage = function (direction) {
        console.log("Button clicked, changing page by:", direction);

        let newPage = currentPage + direction;

        if (newPage >= 1 && newPage <= totalPages) {
            currentPage = newPage;
            console.log("New Page:", currentPage);
            renderTable(currentPage);
        } else {
            console.log("Page change prevented. Current Page:", currentPage);
        }
    };

    renderTable(currentPage);
});

</script>

<script>
    function fetchTransactionHistory(customerId) {
        console.log("Fetching transaction history for customer ID:", customerId); // Debug log

        fetch('super_get_customer_transactions.php?customer_id=' + customerId)
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
<script>
    document.addEventListener("DOMContentLoaded", function () {
    fetch("analytics_fetch.php")
        .then(response => response.json())
        .then(data => {
            let paid = isNaN(data.paid) ? 0 : parseFloat(data.paid);
            let overdue = isNaN(data.overdue) ? 0 : parseFloat(data.overdue);
            let unpaid = isNaN(data.pending) ? 0 : parseFloat(data.pending);

            // Update KPI Cards
            document.getElementById("paidInvoice").textContent = `₱${paid.toLocaleString()}`;
            document.getElementById("over").textContent = `₱${overdue.toLocaleString()}`;
            document.getElementById("unpaid").textContent = `₱${unpaid.toLocaleString()}`;

            // Ensure no duplicate months
            const months = [...new Set(data.months)] || [];
            const paidMonthly = data.paidMonthly || [];
            const overdueMonthly = data.overdueMonthly || [];
            const unpaidMonthly = data.unpaidMonthly || [];

            // Debugging Data
            console.log("Months:", months);
            console.log("Paid Monthly Data:", paidMonthly);
            console.log("Overdue Monthly Data:", overdueMonthly);
            console.log("Unpaid Monthly Data:", unpaidMonthly);

            // Get Chart Context
            const ctx = document.getElementById("kpiChart").getContext("2d");

            // Render Grouped Bar Chart
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: "Paid",
                            data: paidMonthly,
                            backgroundColor: "#28a745", // Green
                            borderColor: "#28a745",
                            borderWidth: 1,
                        },
                        {
                            label: "Overdue",
                            data: overdueMonthly,
                            backgroundColor: "#dc3545", // Red
                            borderColor: "#dc3545",
                            borderWidth: 1,
                        },
                        {
                            label: "Unpaid",
                            data: unpaidMonthly,
                            backgroundColor: "#ffc107", // Yellow
                            borderColor: "#ffc107",
                            borderWidth: 1,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: "top"
                        },
                        tooltip: {
                            callbacks: {
                                label: function (tooltipItem) {
                                    return `₱${tooltipItem.raw.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: false,
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return `₱${value.toLocaleString()}`;
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error("Error fetching KPI data:", error);
        });
});

</script>
</body>
</html>