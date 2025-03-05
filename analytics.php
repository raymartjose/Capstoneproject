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
include "assets_liabilities.php";
include "fetch_income_expense.php";
include "assets/databases/dbconfig.php";
session_start();  // Start the session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect to login if not logged in
    exit();
}

$user_name = $_SESSION['name'];  // User name from session
$user_role = $_SESSION['role_display'];  // User role from session


// Fetch historical transaction data (last 6 months)
$sql_income_history = "SELECT transaction_date, SUM(amount) AS total_income 
                       FROM transactions 
                       GROUP BY transaction_date 
                       ORDER BY transaction_date DESC";

$result_income_history = $connection->query($sql_income_history);
$income_history = [];
while ($row = $result_income_history->fetch_assoc()) {
    $income_history[] = ['date' => $row['transaction_date'], 'income' => $row['total_income']];
}

// Fetch income vs expenses data
$sql_income_expenses = "SELECT 
    DATE_FORMAT(t.created_at, '%Y-%m-%d') AS transaction_date, 
    COALESCE(SUM(t.amount), 0) AS total_income,
    COALESCE(SUM(e.amount), 0) AS total_expenses
FROM transactions t
LEFT JOIN employee_expenses e 
    ON DATE_FORMAT(e.expense_date, '%Y-%m-%d') = DATE_FORMAT(t.created_at, '%Y-%m-%d')
GROUP BY transaction_date
ORDER BY transaction_date DESC";

$result_income_expenses = $connection->query($sql_income_expenses);
$income_expenses_data = [];
while ($row = $result_income_expenses->fetch_assoc()) {
    $income_expenses_data[] = [
        'transaction_date' => $row['transaction_date'],
        'total_income' => $row['total_income'],
        'total_expenses' => $row['total_expenses'] ?? 0 // Ensure it defaults to 0 if null
    ];
}


$sql_income = "SELECT SUM(amount) AS total_income FROM transactions WHERE transaction_date BETWEEN CURDATE() - INTERVAL 1 MONTH AND CURDATE()";
$result_income = $connection->query($sql_income);
$row_income = $result_income->fetch_assoc();
$total_income = $row_income['total_income'];

// Fetch total expenses for the current year
// Calculate total expenses
$expensesQuery = "SELECT SUM(amount) AS total_expenses 
                  FROM employee_expenses 
                  WHERE YEAR(expense_date) = YEAR(CURDATE())";
$expensesResult = $connection->query($expensesQuery);
$expensesRow = $expensesResult->fetch_assoc();
$total_expenses = $expensesRow['total_expenses'];

// Calculate total payroll
$payrollQuery = "SELECT SUM(net_pay) AS total_payroll 
                 FROM payroll 
                 WHERE YEAR(processed_at) = YEAR(CURDATE())";
$payrollResult = $connection->query($payrollQuery);
$payrollRow = $payrollResult->fetch_assoc();
$total_payroll = $payrollRow['total_payroll'];

// Sum of all expenses (employee expenses + payroll)
$total_expenses_including_payroll = $total_expenses + $total_payroll;






// Fetch the tax rates from the 'taxes' table where applicable to income
$sql_taxes = "SELECT id, name, rate, type FROM taxes WHERE is_applicable_income = 1";
$result_taxes = $connection->query($sql_taxes);
$taxes = [];
while ($row_tax = $result_taxes->fetch_assoc()) {
    $taxes[] = $row_tax;
}

// Calculate total taxes for the income based on the tax rates
$total_tax_amount = 0;
foreach ($taxes as $tax) {
    $tax_rate = $tax['rate'];
    if ($tax['type'] == 'percentage') {
        $tax_amount = ($total_income * $tax_rate) / 100;
    } elseif ($tax['type'] == 'fixed') {
        $tax_amount = $tax_rate;
    }
    $total_tax_amount += $tax_amount;
    // Optional: Store the tax amount in the transaction (if needed)
    // For each income transaction, calculate and update tax amounts
    $sql_update_tax = "UPDATE transactions SET tax_id = {$tax['id']}, tax_rate = {$tax_rate}, tax_amount = {$tax_amount}, total_amount = amount + {$tax_amount} WHERE transaction_date BETWEEN CURDATE() - INTERVAL 1 MONTH AND CURDATE()";
    $connection->query($sql_update_tax);
}

// Fetch net income after tax for the last month
$net_income_after_tax = $total_income - $total_tax_amount;


// Fetch total assets
$sql_assets = "SELECT SUM(value) AS total_assets FROM assets";
$result_assets = $connection->query($sql_assets);
$row_assets = $result_assets->fetch_assoc();
$total_assets = $row_assets['total_assets'];

// Fetch total liabilities
$sql_liabilities = "SELECT SUM(amount) AS total_liabilities FROM liabilities";
$result_liabilities = $connection->query($sql_liabilities);
$row_liabilities = $result_liabilities->fetch_assoc();
$total_liabilities = $row_liabilities['total_liabilities'];


// Fetch asset distribution by type
$sql_asset_distribution = "SELECT type, SUM(value) AS total_value FROM assets GROUP BY type";
$asset_distribution = $connection->query($sql_asset_distribution);
$asset_distribution_data = [];
while ($row = $asset_distribution->fetch_assoc()) {
    $asset_distribution_data[] = $row;
}


$sql_liabilities_breakdown = "SELECT liability_name, amount, due_date FROM liabilities";
$result_liabilities_breakdown = $connection->query($sql_liabilities_breakdown);
$liabilities_data = [];
while ($row = $result_liabilities_breakdown->fetch_assoc()) {
    $liabilities_data[] = $row;
}

// Fetch income vs expenses data
$sql_income_expenses = "SELECT transaction_date, SUM(total_income) AS total_income, SUM(total_expenses) AS total_expenses FROM (
    -- Transactions with Expenses
    SELECT 
        DATE_FORMAT(t.created_at, '%Y-%m-%d') AS transaction_date, 
        SUM(t.amount) AS total_income,
        COALESCE(SUM(e.amount), 0) AS total_expenses
    FROM transactions t
    LEFT JOIN employee_expenses e 
        ON DATE_FORMAT(e.expense_date, '%Y-%m-%d') = DATE_FORMAT(t.created_at, '%Y-%m-%d')
    GROUP BY transaction_date

    UNION

    -- Expenses with No Matching Transactions
    SELECT 
        DATE_FORMAT(e.expense_date, '%Y-%m-%d') AS transaction_date,
        0 AS total_income,
        SUM(e.amount) AS total_expenses
    FROM employee_expenses e
    WHERE NOT EXISTS (
        SELECT 1 FROM transactions t 
        WHERE DATE_FORMAT(t.created_at, '%Y-%m-%d') = DATE_FORMAT(e.expense_date, '%Y-%m-%d')
    )
    GROUP BY transaction_date
) AS combined
GROUP BY transaction_date
ORDER BY transaction_date DESC";

$result_income_expenses = $connection->query($sql_income_expenses);
$income_expenses_data = [];
while ($row = $result_income_expenses->fetch_assoc()) {
    $income_expenses_data[] = [
        'transaction_date' => $row['transaction_date'],
        'total_income' => $row['total_income'],
        'total_expenses' => $row['total_expenses'] ?? 0 // Ensure it defaults to 0 if null
    ];
}

$sql_cogs = "SELECT SUM(total_cogs) AS total_cogs FROM cogs";
$result_cogs = $connection->query($sql_cogs);
$row_cogs = $result_cogs->fetch_assoc();
$cost_of_goods_sold = $row_cogs['total_cogs'];

// Calculate gross profit margin
if ($total_income != 0) {
    $gross_profit_margin = ($total_income - $cost_of_goods_sold) / $total_income;
} else {
    $gross_profit_margin = 0; // Prevent division by zero
}

$net_worth = $total_assets - $total_liabilities;

// Calculate debt to equity ratio
if ($total_assets != 0) {
    $debt_to_equity = $total_liabilities / $total_assets;
} else {
    $debt_to_equity = 0; // Prevent division by zero
}

// Calculate net profit margin
if ($total_income != 0) {
    $net_profit_margin = ($total_income - $total_expenses) / $total_income;
} else {
    $net_profit_margin = 0; // Prevent division by zero
}

$sql_net_worth_history = "SELECT 
                            DATE_FORMAT(transaction_date, '%Y-%m') as period,
                            SUM(amount) AS total_income,
                            (SELECT SUM(amount) FROM employee_expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = DATE_FORMAT(transactions.transaction_date, '%Y-%m')) AS total_expenses,
                            (SELECT SUM(value) FROM assets WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(transactions.transaction_date, '%Y-%m')) AS total_assets,
                            (SELECT SUM(amount) FROM liabilities WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(transactions.transaction_date, '%Y-%m')) AS total_liabilities
                        FROM transactions
                        GROUP BY period 
                        ORDER BY period DESC";

$result_net_worth_history = $connection->query($sql_net_worth_history);
$net_worth_history = [];

while ($row = $result_net_worth_history->fetch_assoc()) {
    $net_worth = $row['total_assets'] - $row['total_liabilities'];
    $net_worth_history[] = [
        'date' => $row['period'],
        'net_worth' => $net_worth
    ];
}

$currentMonth = date('m');
$currentYear = date('Y');

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
$incomeQuery = $connection->prepare("SELECT SUM(amount) AS total_income FROM transactions WHERE MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?");
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
while ($row = $paymentMethodResult->fetch_assoc()) {
    $paymentMethods[$row['payment_method']] = $row['total_income'];
}

// Fetch customer transaction data
$customerQuery = "SELECT c.id AS customer_id, c.name AS customer_name, c.email, SUM(t.amount) AS total_amount, SUM(i.discount_amount) AS total_discount FROM transactions t JOIN invoices i ON t.description LIKE CONCAT('%', i.id, '%') JOIN customers c ON i.customer_id = c.id GROUP BY c.id, c.name, c.email LIMIT 25";
$customerResult = $connection->query($customerQuery);

?>


<?php
// Fetch the monthly income and tax data from your database
// This assumes you have a table to store monthly incomes or you can calculate it from income records

$sql_income = "SELECT MONTH(transaction_date) AS month, SUM(amount) AS total_income 
               FROM transactions
               GROUP BY MONTH(transaction_date)
               ORDER BY MONTH(transaction_date)";

$result_income = $connection->query($sql_income);

// Prepare data arrays
$months = [];
$income_data = [];
$tax_data = [];

// Tax rate (you can fetch this from your database or hardcode it for simplicity)
$tax_rate = 0.10; // Example: 10% tax deduction on income

while ($row = $result_income->fetch_assoc()) {
    // Store the month, income, and tax deduction data
    $months[] = $row['month']; // Month (1-12)
    $income_data[] = $row['total_income']; // Total income for the month

    // Calculate the tax deduction (tax_rate * income)
    $tax_deduction = $row['total_income'] * $tax_rate;
    $tax_data[] = $tax_deduction; // Tax deducted for the month
}

// Convert PHP arrays to JSON for JavaScript
$months_json = json_encode($months);
$income_json = json_encode($income_data);
$tax_json = json_encode($tax_data);

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
                Dashboard
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

        <main>

        <div class="cards" style="cursor: pointer">
    <div class="card-single">
        <div>
            <h3>Expenses</h3>
            <h4>₱<span id="totalExpense">0</span></h4>
            <span id="viewInsights" style="cursor: pointer; color: blue; text-decoration: underline;">View Insights</span>
        </div>
        <div>
            <span class="las la-receipt"></span>
        </div>
    </div>

    <div class="card-single">
        <div>
            <h3>Revenue</h3>
            <h4>₱<span id="totalIncome">0</span></h4>
            <span>View Insights</span>
        </div>
        <div>
            <span class="lab la-google-wallet"></span>
        </div>
    </div>

    <div class="card-single">
        <div>
            <h3>Net Income</h3>
            <h4>₱<span id="netIncome">0</span></h4>
            <span>View Insights</span>
        </div>
        <div>
            <span class="lab la-google-wallet"></span>
        </div>
    </div>

    <div class="card-single">
        <div>
            <h3>Assets</h3>
            <h4>₱<span id="totalAssets">0</span></h4>
            <span>View Insights</span>
        </div>
        <div>
            <span class="las la-coins"></span>
        </div>
    </div>

    <div class="card-single">
        <div>
            <h3>Liabilities</h3>
            <h4>₱<span id="totalLiabilities">0</span></h4>
            <span>View Insights</span>
        </div>
        <div>
            <span class="las la-credit-card"></span>
        </div>
    </div>

    <div class="card-single">
        <div>
            <h3>Net Worth</h3>
            <h4>₱<span id="netWorth">0</span></h4>
            <span>View Insights</span>
        </div>
        <div>
            <span class="las la-wallet"></span>
        </div>
    </div>

    <div class="card-single">
        <div>
            <h3>Debt-to-Equity Ratio</h3>
            <h4><span id="debtToEquity">0</span></h4>
            <span>View Insights</span>
        </div>
        <div>
            <span class="las la-balance-scale"></span>
        </div>
    </div>
</div>


        <div class="recent-grid">



                <div class="projects">
                <div class="chart-container">
                

                        <h3>Income vs Expenses</h3>
                        <canvas id="incomeVsExpensesChart"></canvas>
                    </div>

            <div class="chart-grid">
                        <div class="chart-container">
                       <h3000>Income Breakdown by Payment Method</h3>
                    <canvas id="paymentMethodChart"></canvas>
</div>
    

                    <div class="chart-container">
                        <h3>Cash Flow</h3>
                        <canvas id="cashFlowChart"></canvas>
                    </div>
                </div>

                <div class="chart-grid1">
                <div class="chart-container">
                    <h3>Gross Profit</h3>
                    <canvas id="grossProfitChart"></canvas>
                </div>

                <!-- Liabilities Breakdown Chart -->
                <div class="chart-container">
                    <h3>Net Profit Margin</h3>
                    <canvas id="netProfitMarginChart"></canvas>
                </div>

                <div class="chart-container">
                    <h3>Tax Report</h3>
            <canvas id="taxDeductionChart" width="400" height="200"></canvas>
                </div>



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

<div class="customers">
    <div class="card">
        <div class="customer">
            <div class="card-header">
            <div class="meter-container">
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

            
    <!-- Payment Method Income Breakdown Chart (Pie Chart) -->
    

        </div>
        
    </div>
</div>


            
            


    </main>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/countup.js/2.0.7/countUp.umd.js"></script>

<script>
function redirectToBudgetReport() {
    let currentMonth = new Date().getMonth() + 1; // JS months are 0-based
    let currentYear = new Date().getFullYear();
    window.location.href = `budget_report.php?month=${currentMonth}&year=${currentYear}`;
}
</script>

<script>
    // Prepare the payment method data for the bar chart
    const paymentLabels = <?php echo json_encode(array_keys($paymentMethods)); ?>;
    const paymentData = <?php echo json_encode(array_values($paymentMethods)); ?>;
    const ctxPayment = document.getElementById('paymentMethodChart').getContext('2d');

    const paymentMethodChart = new Chart(ctxPayment, {
        type: 'bar',
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
            indexAxis: 'y', // This makes the bar chart horizontal
            responsive: true,
            plugins: {
                legend: {
                    display: false // Hide legend since labels are on the Y-axis
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.85)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    callbacks: {
                        label: function(tooltipItem) {
                            const value = tooltipItem.raw;
                            return `₱${parseFloat(value).toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                x: {
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

<script>
    // Prepare the data for Chart.js
    var months = <?php echo $months_json; ?>;
    var incomeData = <?php echo $income_json; ?>;
    var taxData = <?php echo $tax_json; ?>;

    // Create the chart
    var ctx = document.getElementById('taxDeductionChart').getContext('2d');
    var taxDeductionChart = new Chart(ctx, {
        type: 'bar', // You can change this to 'line' for a line chart
        data: {
            labels: months.map(function(month) {
                const date = new Date(0); // epoch time
                date.setMonth(month - 1); // Set the month (1-12)
                return date.toLocaleString('default', { month: 'short' }); // Month name (e.g., Jan, Feb, Mar)
            }),
            datasets: [{
                label: 'Monthly Income (₱)',
                data: incomeData,
                backgroundColor: '#30c0dd',
                borderColor: '#30c0dd',
                borderWidth: 1
            }, {
                label: 'Tax Deduction (₱)',
                data: taxData,
                backgroundColor: '#FF6384',
                borderColor: '#FF6384',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return '₱ ' + tooltipItem.raw.toFixed(2); // Format tooltips as ₱ currency
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱ ' + value.toFixed(2); // Format Y-axis as ₱ currency
                        }
                    }
                }
            }
        }
    });
</script>




<script>
// Parse the PHP data into JavaScript
const incomeVsExpensesData = <?php echo json_encode($income_expenses_data); ?>;

// Initial chart creation with full data
const incomeVsExpensesChart = new Chart(document.getElementById('incomeVsExpensesChart'), {
    type: 'bar',
    data: {
        labels: incomeVsExpensesData.map(data => data.transaction_date),
        datasets: [
            {
                label: 'Income',
                data: incomeVsExpensesData.map(data => data.total_income),
                backgroundColor: '#30c0dd',
            },
            {
                label: 'Expenses',
                data: incomeVsExpensesData.map(data => data.total_expenses || 0),
                backgroundColor: '#FF6384',
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
        }
    }
});

// Cash Flow Chart
const cashFlowChart = new Chart(document.getElementById('cashFlowChart'), {
    type: 'line',
    data: {
        labels: incomeVsExpensesData.map(data => data.transaction_date),
        datasets: [{
            label: 'Cash Flow',
            data: incomeVsExpensesData.map(data => data.total_income - (data.total_expenses || 0)),
            fill: false,
            borderColor: '#30c0dd',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
        }
    }
});


</script>


<script>
    // Net Worth History Chart
const netWorthData = <?php echo json_encode($net_worth_history); ?>;
const netWorthChart = new Chart(document.getElementById('NetworthChart'), {
    type: 'line',
    data: {
        labels: netWorthData.map(data => data.date),
        datasets: [{
            label: 'Net Worth',
            data: netWorthData.map(data => data.net_worth),
            backgroundColor: '#30c0dd',
            borderColor: '#30c0dd',
            borderWidth: 2,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Net Worth (₱)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Time Period'
                }
            }
        }
    }
});

</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Calculate percentages for Net Profit and Expenses
    var totalIncome = <?php echo $total_income; ?>;
    var totalExpenses = <?php echo $total_expenses; ?>;
    var netProfit = totalIncome - totalExpenses;

    var incomePercentage = ((netProfit / totalIncome) * 100).toFixed(2); // Net Profit %
    var expensesPercentage = ((totalExpenses / totalIncome) * 100).toFixed(2); // Expenses %

    // Get canvas context
    var ctx = document.getElementById('netProfitMarginChart').getContext('2d');

    // Create gradient colors
    var gradientProfit = ctx.createLinearGradient(0, 0, 0, 300);
    gradientProfit.addColorStop(0, '#1E88E5'); // Blue
    gradientProfit.addColorStop(1, '#64B5F6'); // Lighter blue

    var gradientExpenses = ctx.createLinearGradient(0, 0, 0, 300);
    gradientExpenses.addColorStop(0, '#9E9E9E'); // Gray
    gradientExpenses.addColorStop(1, '#E0E0E0'); // Light gray

    // Net Profit Margin Doughnut Chart
    var netProfitMarginChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Net Profit (' + incomePercentage + '%)', 'Expenses (' + expensesPercentage + '%)'],
            datasets: [{
                data: [incomePercentage, expensesPercentage],
                backgroundColor: [gradientProfit, gradientExpenses],
                hoverBackgroundColor: ['#1565C0', '#30c0dd'], // Highlight colors
                borderColor: '#30c0dd', // White border for separation
                borderWidth: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            cutout: '75%', // Thin ring
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: '#4F4F4F',
                        font: {
                            size: 14,
                            weight: '600'
                        },
                        usePointStyle: true, // Circular legend markers
                        padding: 20
                    }
                },
                tooltip: {
                    enabled: true,
                    callbacks: {
                        label: function (tooltipItem) {
                            return `${tooltipItem.label}: ${tooltipItem.raw}%`;
                        }
                    }
                }
            },
            elements: {
                center: {
                    text: incomePercentage + '%',
                    color: '#1E88E5', // Text color
                    fontStyle: 'bold', // Font style
                    sidePadding: 20, // Padding around the text
                    minFontSize: 18, // Minimum font size
                    lineHeight: 25 // Line height
                }
            }
        },
        plugins: [{
            // Custom plugin to display text in the center
            id: 'centerText',
            beforeDraw: function (chart) {
                if (chart.config.options.elements.center) {
                    var ctx = chart.ctx;
                    var centerConfig = chart.config.options.elements.center;
                    var fontStyle = centerConfig.fontStyle || 'Arial';
                    var txt = centerConfig.text;
                    var color = centerConfig.color || '#000';
                    var sidePadding = centerConfig.sidePadding || 20;
                    var sidePaddingCalculated = (sidePadding / 100) * (chart.innerRadius * 2);
                    ctx.font = centerConfig.minFontSize + 'px ' + fontStyle;

                    // Calculate text width
                    var stringWidth = ctx.measureText(txt).width;
                    var elementWidth = (chart.innerRadius * 2) - sidePaddingCalculated;

                    // Adjust font size if necessary
                    var widthRatio = elementWidth / stringWidth;
                    var newFontSize = Math.floor(centerConfig.minFontSize * widthRatio);
                    var elementHeight = (chart.innerRadius * 2);

                    ctx.font = newFontSize + 'px ' + fontStyle;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = color;

                    // Draw text in the center
                    var centerX = ((chart.chartArea.left + chart.chartArea.right) / 2);
                    var centerY = ((chart.chartArea.top + chart.chartArea.bottom) / 2);
                    ctx.fillText(txt, centerX, centerY);
                }
            }
        }]
    });
});
</script>

<script>
const grossProfitData = <?php echo json_encode($net_worth_history); ?>;
console.log(grossProfitData); // Debugging output

// Prepare the data for the chart
const grossProfit = grossProfitData.map(data => {
    const income = data.total_income;
    const cogs = data.total_cogs || 0; // Ensure COGS is available
    const grossProfitValue = income - cogs; // Gross Profit = Income - COGS
    return { date: data.date, gross_profit: grossProfitValue };
});

console.log(grossProfit); // Debugging output

// Gross Profit Chart
const grossProfitChart = new Chart(document.getElementById('grossProfitChart'), {
    type: 'line',
    data: {
        labels: grossProfit.map(data => data.date), // Use the dates as labels
        datasets: [{
            label: 'Gross Profit',
            data: grossProfit.map(data => data.gross_profit),
            fill: false,
            borderColor: '#30c0dd',  // Green color for the line
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: { 
                callbacks: {
                    label: function(tooltipItem) {
                        return 'Gross Profit: ' + tooltipItem.raw.toFixed(2);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true, // Ensures the y-axis starts from zero
                ticks: {
                    callback: function(value) {
                        return value.toFixed(2); // Display numbers with two decimal places
                    }
                }
            }
        }
    }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Get values from PHP
    const totalExpense = <?php echo json_encode($total_expenses_including_payroll); ?> || 0;
    const totalIncome = <?php echo json_encode($total_income); ?> || 0;
    const netIncome = <?php echo json_encode($net_income_after_tax); ?> || 0;
    const totalAssets = <?php echo json_encode($totalAssets); ?> || 0;
    const totalLiabilities = <?php echo json_encode($totalLiabilities); ?> || 0;
    const netWorth = <?php echo json_encode($net_worth); ?> || 0;
    const debtToEquity = <?php echo json_encode($debt_to_equity); ?> || 0;

    // Initialize CountUp.js
    const options = { duration: 2, separator: ",", decimalPlaces: 2 };

    new countUp.CountUp("totalExpense", totalExpense, options).start();
    new countUp.CountUp("totalIncome", totalIncome, options).start();
    new countUp.CountUp("netIncome", netIncome, options).start();
    new countUp.CountUp("totalAssets", totalAssets, options).start();
    new countUp.CountUp("totalLiabilities", totalLiabilities, options).start();
    new countUp.CountUp("netWorth", netWorth, options).start();
    new countUp.CountUp("debtToEquity", debtToEquity, options).start();
});
</script>
<script>
    document.getElementById("viewInsights").addEventListener("click", function() {
        window.location.href = "expense_tracker.php";
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

</body>
</html>