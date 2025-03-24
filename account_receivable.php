<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="icon" href="img/logo-sm.png">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel= "stylesheet" href= "https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css" >
    
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

include('assets/databases/dbconfig.php');

$agingPage = isset($_GET['aging_page']) ? max(1, intval($_GET['aging_page'])) : 1;
$unpaidPage = isset($_GET['unpaid_page']) ? max(1, intval($_GET['unpaid_page'])) : 1;

// Set limit per page
$limit = 5;
$agingOffset = ($agingPage - 1) * $limit;
$unpaidOffset = ($unpaidPage - 1) * $limit;

// Get total count for pagination
$totalAgingRows = $connection->query("SELECT COUNT(DISTINCT customer_name) AS total FROM receivables")->fetch_assoc()['total'];
$totalUnpaidRows = $connection->query("SELECT COUNT(*) AS total FROM invoices WHERE payment_status IN ('pending', 'overdue')")->fetch_assoc()['total'];

// Total pages
$totalAgingPages = ceil($totalAgingRows / $limit);
$totalUnpaidPages = ceil($totalUnpaidRows / $limit);

// Aging Report Query with Pagination
$sql = "SELECT 
            id,
            customer_name, 
            SUM(IFNULL(current_amount, 0)) AS current_amount,
            SUM(IFNULL(past_due_30, 0)) AS past_due_30,
            SUM(IFNULL(past_due_60, 0)) AS past_due_60,
            SUM(IFNULL(past_due_90, 0)) AS past_due_90,
            SUM(IFNULL(past_due_90plus, 0)) AS past_due_90plus,
            SUM(IFNULL(total_due, 0)) AS total_due
        FROM receivables
        GROUP BY customer_name
        ORDER BY total_due DESC
        LIMIT $limit OFFSET $agingOffset";

$result = $connection->query($sql);

$receivablesData = [];
$totalCurrent = $totalPast30 = $totalPast60 = $totalPast90 = $totalPast90plus = $totalDue = 0;

// Fetch data only once
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $receivablesData[] = $row;
        $totalCurrent += $row['current_amount'];
        $totalPast30 += $row['past_due_30'];
        $totalPast60 += $row['past_due_60'];
        $totalPast90 += $row['past_due_90'];
        $totalPast90plus += $row['past_due_90plus'];
        $totalDue += $row['total_due'];
    }
}

$sqlUnpaid = "SELECT 
                invoices.id AS invoice_id, 
                invoices.customer_id,
                invoices.product_name, 
                invoices.total_amount, 
                invoices.due_date, 
                invoices.payment_status, 
                customers.name AS customer_name
            FROM invoices
            JOIN customers ON invoices.customer_id = customers.id
            WHERE invoices.payment_status IN ('pending', 'overdue')
            ORDER BY invoices.due_date ASC
            LIMIT $limit OFFSET $unpaidOffset";

$resultUnpaid = $connection->query($sqlUnpaid);


function getPaidUnpaidInvoicesData() {
    global $connection;

    // Initialize an array to hold monthly data for paid and unpaid invoices
    $paidInvoices = array_fill(0, 12, 0);
    $unpaidInvoices = array_fill(0, 12, 0);

    // Query to get the total amount for paid invoices by month
    $sqlPaid = "SELECT 
                    MONTH(invoices.payment_date) AS month,
                    SUM(invoices.total_amount) AS total_paid
                FROM invoices
                WHERE invoices.payment_status = 'paid'
                GROUP BY MONTH(invoices.payment_date)
                ORDER BY month ASC";
    $resultPaid = $connection->query($sqlPaid);
    if ($resultPaid->num_rows > 0) {
        while ($row = $resultPaid->fetch_assoc()) {
            $monthIndex = $row['month'] - 1;  // Convert month number to zero-based index
            $paidInvoices[$monthIndex] = $row['total_paid'];
        }
    }

    // Query to get the total amount for unpaid invoices by month
    $sqlNotpaid = "SELECT 
                    MONTH(invoices.due_date) AS month,
                    SUM(invoices.total_amount) AS total_unpaid
                FROM invoices
                WHERE invoices.payment_status IN ('pending', 'overdue')
                GROUP BY MONTH(invoices.due_date)
                ORDER BY month ASC";
    $resultNotpaid = $connection->query($sqlNotpaid);
    if ($resultNotpaid->num_rows > 0) {
        while ($row = $resultNotpaid->fetch_assoc()) {
            $monthIndex = $row['month'] - 1;  // Convert month number to zero-based index
            $unpaidInvoices[$monthIndex] = $row['total_unpaid'];
        }
    }

    return [
        'paidInvoices' => $paidInvoices,
        'unpaidInvoices' => $unpaidInvoices
    ];
}

// Fetch paid and unpaid invoice data
$invoiceData = getPaidUnpaidInvoicesData();
$paidInvoices = json_encode($invoiceData['paidInvoices']);
$unpaidInvoices = json_encode($invoiceData['unpaidInvoices']);

?>

<input type="checkbox" id="nav-toggle">
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="img/logo1.png" alt="">
    </div>

    <div class="sidebar-menu">
    <ul>
        <li>
                <a href="analytics.php"><span class="las la-tachometer-alt"></span>
                <span>Dashboard</span></a>
            </li>
            <li>
            <a href="super_financial.php"><span class="las la-balance-scale"></span>
            <span>Financial Request</span></a>
</li>
<li class="submenu">
            <a href="#" class="active"><span class="las la-sitemap"></span>
            <span>Financial Reports</span></a>
            <ul class="submenu-items">
                <li><a href="coa.php"><span class="las la-folder"></span> Chart of Accounts</a></li>
                <li><a href="balance_sheet.php"><span class="las la-chart-line"></span> Balance Sheet</a></li>
                <li><a href="account_receivable.php" class="active"><span class="las la-file-invoice"></span> Accounts Receivable</a></li>
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
                Accounts Receivable
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

.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.tab-button {
    background: #0a1d4e;
    color: white;
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
}

.tab-button.active {
    background: #0056b3;
}

.tab-content {
    background: #fff;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
</style>

<script>
function toggleMaximize() {
    let panel = document.getElementById("transactionHistoryPanel");
    let maximizeBtn = document.getElementById("maximizeBtn");

    panel.classList.toggle("maximized");

    // Change button icon based on state
    if (panel.classList.contains("maximized")) {
        maximizeBtn.innerHTML = "🗕"; // Restore icon
    } else {
        maximizeBtn.innerHTML = "🗖"; // Maximize icon
    }
}

function closePanel() {
    let panel = document.getElementById("transactionHistoryPanel");
    panel.classList.remove("open", "maximized");
    document.getElementById("maximizeBtn").innerHTML = "🗖"; // Reset button icon
}

// Function to download table as CSV
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
</script>



        <main>
        <div class="tabs">
        <button class="tab-button active" onclick="showTab('invoice-display')">All Invoices</button>
        <button class="tab-button active" onclick="showTab('AR-display')">Accounts Receivable</button>
    <button class="tab-button" onclick="showTab('AP-display')">Accounts Payable</button>
</div>
<style>
.kpi-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: left;
    gap: 15px;
    padding: 5px;
    margin-bottom: 10px;
}

.chart-container1 {
    width: 350px; /* Ensure adequate space */
    background: #fff;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    padding: 15px;
    display: flex;
    flex-direction: column; /* Stack title, then chart & legend */
    align-items: flex-start; /* Align everything to the left */
    gap: 8px; /* Space between title, chart, and legend */
    position: relative;
}

.chart-title {
    font-size: 14px;
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
    align-self: flex-start;
}

.chart-content {
    display: flex;
    flex-direction: row; /* Keep chart and legend side by side */
    align-items: center;
    gap: 12px; /* Space between chart and legend */
}

.chart-container1 canvas {
    max-height: 120px; 
    max-width: 120px;
    flex-shrink: 0; /* Prevent shrinking */
}

.chart-legend {
    display: flex;
    flex-direction: column;
    align-items: flex-start; /* Align labels to the left */
    white-space: nowrap; /* Prevent text wrapping */
}

.legend-item {
    display: flex;
    align-items: center;
    font-size: 12px;
    color: #444;
    margin: 4px 0;
    gap: 6px; /* Adds spacing between label and value */
}

.legend-item span:last-child {
    font-weight: bold; /* Make the value stand out */
}


.color-box {
    width: 12px;
    height: 12px;
    display: inline-block;
    margin-right: 6px;
    border-radius: 3px;
}

/* Define Colors */
.paid { background-color: #4CAF50; }
.pending { background-color: #FF9800; }
.overdue { background-color: #e74c3c; }
.cash { background-color: #3498db; }
.credit { background-color: #9b59b6; }
.bank { background-color: #2ecc71; }
.online { background-color: #f1c40f; }

/* Status Badge Styles */
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: bold;
    text-align: center;
    min-width: 70px;
}

/* Paid Status */
.status-badge.paid {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

/* Pending/Unpaid Status */
.status-badge.pending {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}

/* Overdue Status */
.status-badge.overdue {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

    .filters input, .filters select {
        padding: 6px;
        margin-right: 5px;
    }

    .invoice-table-container {
        margin-top: 5px;
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        overflow-x: auto;
    }

    #invoiceTable1 {
        width: 100%;
        border-collapse: collapse;
        border: none;
    }
    #invoiceTable1 th,
#invoiceTable1 td {
    border: none; /* Remove borders from table cells */
    padding: 8px; /* Adjust padding for better spacing */
    text-align: left; /* Align text properly */
}

    #invoiceTable1 thead {
        background: #f4f4f4;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    #invoiceTable1 tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    #invoiceTable1 tbody tr:hover {
        background-color: #e6f7ff;
        transition: background 0.3s ease;
    }

    #invoiceTable1 th, #invoiceTable1 td {
        padding: 10px;
        text-align: left;
    }

    .action-dropdown {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
    }
    .action-btn {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 16px;
    }
    .dropdown-content {
        display: none;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        background-color: #fff;
        box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
        z-index: 1;
        min-width: 120px;
        text-align: center;
    }

</style>
<div id="invoice-display" class="tab-content" style="display: block;">
<div class="kpi-grid">
    <!-- Payments Chart -->
    <div class="chart-container1">
        <div class="chart-title">Payments</div>
        <div class="chart-content">
            <canvas id="paymentsChart"></canvas>
            <div class="chart-legend">
                <span class="legend-item"><span class="color-box paid"></span> Paid: <span id="paidAmount">0</span></span>
                <span class="legend-item"><span class="color-box pending"></span> Outstanding: <span id="pendingAmount">0</span></span>
            </div>
        </div>
    </div>

    <!-- Payment Methods Chart -->
    <div class="chart-container1">
        <div class="chart-title">Payment Methods</div>
        <div class="chart-content">
            <canvas id="paymentMethodChart"></canvas>
            <div class="chart-legend">
                <span class="legend-item"><span class="color-box cash"></span> Cash: <span id="cashCount">0</span></span>
                <span class="legend-item"><span class="color-box credit"></span> Credit: <span id="creditCount">0</span></span>
                <span class="legend-item"><span class="color-box bank"></span> Bank Transfer: <span id="bankCount">0</span></span>
                <span class="legend-item"><span class="color-box online"></span> Online: <span id="onlineCount">0</span></span>
            </div>
        </div>
    </div>

    <!-- Invoice Status Chart -->
    <div class="chart-container1">
        <div class="chart-title">Status</div>
        <div class="chart-content">
            <canvas id="invoiceStatusChart"></canvas>
            <div class="chart-legend">
                <span class="legend-item"><span class="color-box pending"></span> Unpaid: <span id="pendingCount"> 0</span></span>
                <span class="legend-item"><span class="color-box paid"></span> Paid: <span id="paidCount"> 0</span></span>
                <span class="legend-item"><span class="color-box overdue"></span> Overdue: <span id="overdueCount"> 0</span></span>
            </div>
      </div>
    </div>
</div>

<div class="filters">
    <input type="text" id="searchName" placeholder="Search Customer..." onkeyup="filterInvoices()">
    From
    <input type="date" id="startDate" onchange="filterInvoices()">
    To
    <input type="date" id="endDate" onchange="filterInvoices()">
    
</div>

<div class="invoice-table-container">
    <table id="invoiceTable1">
        <thead>
            <tr>
            <th style="width: 1%; white-space: nowrap;">
                <select id="statusFilter" onchange="filterInvoices()">
        <option value="">Status</option>
        <option value="paid">Paid</option>
        <option value="pending">Unpaid</option>
        <option value="overdue">Overdue</option>
    </select></th>
                <th>Invoice #</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Due Date</th>
                <th>Payment Date</th>
                <th style="width: 1%; white-space: nowrap;">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
            // Update overdue status for unpaid invoices
            $updateStatusSql = "UPDATE invoices 
                                SET payment_status = 'overdue' 
                                WHERE payment_status != 'paid' 
                                AND due_date < CURDATE()";
            $connection->query($updateStatusSql);

            // Fetch all invoices (No Pagination)
            $sql = "SELECT i.id AS invoice_id, c.id AS customer_id, c.name AS customer_name, 
                           i.total_amount, i.due_date, i.payment_date, i.payment_status 
                    FROM invoices i
                    INNER JOIN customers c ON i.customer_id = c.id
                    ORDER BY i.payment_date DESC";

            $result = $connection->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Assign CSS class based on payment_status
                    $statusClass = strtolower($row['payment_status']); // Convert to lowercase for consistency

                    echo "<tr>
                            <td><span class='status-badge $statusClass'>{$row['payment_status']}</span></td>
                            <td>{$row['invoice_id']}</td>
                            <td class='customer-name'>{$row['customer_name']}</td>
                            <td>₱" . number_format($row['total_amount'], 2) . "</td>
                            <td class='due-date'>" . date("Y-m-d", strtotime($row['due_date'])) . "</td>
                            <td class='pay-date'>" . (!empty($row['payment_date']) && $row['payment_date'] !== '0000-00-00' ? date("Y-m-d", strtotime($row['payment_date'])) : '-- -- --') . "</td>
                            <td>
                                <div class='action-dropdown'>
                                    <button class='action-btn' onclick='toggleDropdown(this)'>▼</button>
                                    <div class='dropdown-content'>
                                        <a href='super_invoice.php?id=" . htmlspecialchars($row['invoice_id']) . "'>View Invoice</a>
                                        <a href='#' onclick='fetchTransactionHistory(" . htmlspecialchars($row['customer_id']) . ")'>View History</a>
                                    </div>
                                </div>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='6' style='text-align: center;'>No invoices found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
    function toggleDropdown(button) {
        var dropdown = button.nextElementSibling;
        dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
    }
    document.addEventListener('click', function(event) {
        if (!event.target.matches('.action-btn')) {
            document.querySelectorAll('.dropdown-content').forEach(function(dropdown) {
                dropdown.style.display = 'none';
            });
        }
    });
</script>

<script>
    function filterInvoices() {
        let statusFilter = document.getElementById("statusFilter").value.toLowerCase();
        let searchName = document.getElementById("searchName").value.toLowerCase();
        let startDate = document.getElementById("startDate").value;
        let endDate = document.getElementById("endDate").value;

        let rows = document.querySelectorAll("#invoiceTable1 tbody tr");

        rows.forEach(row => {
            let statusElement = row.querySelector(".status-badge");
            let customerElement = row.querySelector(".customer-name");
            let dueDateElement = row.querySelector(".due-date");

            if (statusElement && customerElement && dueDateElement) {
                let status = statusElement.textContent.toLowerCase();
                let customerName = customerElement.textContent.toLowerCase();
                let dueDate = dueDateElement.textContent;

                let statusMatch = statusFilter === "" || status.includes(statusFilter);
                let nameMatch = searchName === "" || customerName.includes(searchName);
                let dateMatch = (!startDate || dueDate >= startDate) && (!endDate || dueDate <= endDate);

                row.style.display = (statusMatch && nameMatch && dateMatch) ? "" : "none";
            }
        });
    }
</script>


</div>


<div id="AR-display" class="tab-content" style="display: none;">
<div class="kpi-cards">
    <div class="card">
        <h4>Total Receivable</h4>
        <h5 id="totalAR">₱0</h5>
    </div>
    <div class="card">
        <h4>Paid Invoice %</h4>
        <h5 id="paidPercent">0%</h5>
    </div>
    <div class="card">
        <h4>No. of Paid Invoices</h4>
        <h5 id="total">0</h5>
    </div>
</div>
<div class="charts-container">
    <!-- Horizontal Bar Chart (Top 10 Unpaid Customers) -->
    <div class="chart-box">
        <h4>Paid invoices amount by customer (Top 10)</h4>
        <canvas id="topPaidCustomersChart"></canvas>
    </div>
    
    <!-- Pie Chart (Unpaid Customer Percentage) -->
    <div class="chart-box pie-chart">
    <!-- Pie Chart -->
    <div class="pie-chart-container">
        <canvas id="paidPieChart"></canvas>
    </div>

    <!-- Legend (Labels) -->
    <div class="pie-chart-legend1">
        <ul id="pieChartLegend1"></ul>
    </div>
</div>

</div>
<div class="container">
<div class="chart-box1">
    <h4>Paid Invoices</h4>
    <canvas id="monthlyPaidInvoicesChart"></canvas>
</div>

</div>

   
</div>
<div id="AP-display" class="tab-content" style="display: none;">

<!-- KPI Cards -->
<div class="kpi-cards">
    <div class="card">
        <h4>Total Payable</h4>
        <h5 id="totalReceivables">₱0</h5>
    </div>
    <div class="card">
        <h4>Past Due %</h4>
        <h5 id="pastDuePercent">0%</h5>
    </div>
    <div class="card">
        <h4>Over 90 Days</h4>
        <h5 id="over90">₱0</h5>
    </div>
</div>

<!-- Chart Section -->
<div class="charts-container">
    <!-- Horizontal Bar Chart (Top 10 Unpaid Customers) -->
    <div class="chart-box">
        <h4>Unpaid invoices amount by customer (Top 10)</h4>
        <canvas id="topCustomersChart"></canvas>
    </div>
    
    <!-- Pie Chart (Unpaid Customer Percentage) -->
    <div class="chart-box pie-chart">
    <!-- Pie Chart -->
    <div class="pie-chart-container">
        <canvas id="unpaidPieChart"></canvas>
    </div>

    <!-- Legend (Labels) -->
    <div class="pie-chart-legend">
        <ul id="pieChartLegend"></ul>
    </div>
</div>

</div>


<div class="tables-wrapper">
    <!-- Aging Report Table -->
    <div class="table-container">
        <h3>Aging Report</h3>
        <br>
        <table id="agingTable">
    <thead>
        <tr>
        <th style="display: none;">ID</th>
            <th>Customer</th>
            <th>Current</th>
            <th>1-30</th>
            <th>31-60</th>
            <th>61-90</th>
            <th>90+</th>
            <th>Total Due</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($receivablesData)) : ?>
            <?php foreach ($receivablesData as $row) : ?>
                <tr data-customer-id="<?= htmlspecialchars($row['customer_name']) ?>">
                <td style="display: none;"><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td><?= number_format($row['current_amount'], 2) ?></td>
                    <td><?= number_format($row['past_due_30'], 2) ?></td>
                    <td><?= number_format($row['past_due_60'], 2) ?></td>
                    <td><?= number_format($row['past_due_90'], 2) ?></td>
                    <td><?= number_format($row['past_due_90plus'], 2) ?></td>
                    <td><?= number_format($row['total_due'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr><td colspan='7' style='text-align: center;'>No records found</td></tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td style="font-weight: bold; text-align: right;">Total:</td>
            <td><?= number_format($totalCurrent, 2) ?></td>
            <td><?= number_format($totalPast30, 2) ?></td>
            <td><?= number_format($totalPast60, 2) ?></td>
            <td><?= number_format($totalPast90, 2) ?></td>
            <td><?= number_format($totalPast90plus, 2) ?></td>
            <td><?= number_format($totalDue, 2) ?></td>
        </tr>
    </tfoot>

</table>
<div class="pagination">
    <?php if ($agingPage > 1) : ?>
        <a href="?aging_page=<?= $agingPage - 1 ?>" class="prev">← Previous</a>
    <?php endif; ?>
    
    <?php if ($agingPage < $totalAgingPages) : ?>
        <a href="?aging_page=<?= $agingPage + 1 ?>" class="next">Next →</a>
    <?php endif; ?>
</div>

<button id="updateAging">Update Aging</button>
    </div>

    <!-- Unpaid Invoices Table -->
    <div class="table-container small-table">
        <h3>Unpaid Invoices</h3>
        <br>
        <table id="unpaidInvoicesTable">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Invoice No.</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
    <?php 
    $totalUnpaidAmount = 0; // Initialize total
    if ($resultUnpaid->num_rows > 0) : 
        while ($row = $resultUnpaid->fetch_assoc()) : 
            $totalUnpaidAmount += $row['total_amount']; // Sum up total amount
    ?>
            <tr class="invoice-row" data-invoice-id="<?= $row['customer_id'] ?>" 
                style="cursor:pointer;" 
                onmouseover="this.style.backgroundColor='#f1b0b7';" 
                onmouseout="this.style.backgroundColor='';">
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><?= htmlspecialchars($row['invoice_id']) ?></td>
                <td><?= date("M d, Y", strtotime($row['due_date'])) ?></td>
                <td><?= number_format($row['total_amount'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else : ?>
        <tr><td colspan="4" style="text-align: center;">No unpaid invoices</td></tr>
    <?php endif; ?>
</tbody>
<tfoot>
    <tr>
        <td colspan="3" style="font-weight: bold; text-align: right;">Total:</td>
        <td style="font-weight: bold;">₱<?= number_format($totalUnpaidAmount, 2) ?></td>
    </tr>
</tfoot>
        </table>
        <div class="pagination">
    <?php if ($unpaidPage > 1) : ?>
        <a href="?unpaid_page=<?= $unpaidPage - 1 ?>" class="prev">← Previous</a>
    <?php endif; ?>
    
    <?php if ($unpaidPage < $totalUnpaidPages) : ?>
        <a href="?unpaid_page=<?= $unpaidPage + 1 ?>" class="next">Next →</a>
    <?php endif; ?>
</div>

    </div>
</div>
<br>
<div class="container">
    <div class="chart-box1">
        <h4>Unpaid Invoices</h4>
        <canvas id="monthlyUnpaidInvoicesChart"></canvas> <!-- FIXED: Updated ID -->
    </div>
</div>
    </div>

    </main>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

<script>
const ctxMonthlyPaid = document.getElementById("monthlyPaidInvoicesChart").getContext("2d");

// Monthly labels for the chart
const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

// Data from PHP variables (Only Paid Invoices)
const paidInvoicesData = <?php echo $paidInvoices; ?>;

const monthlyPaidInvoicesChart = new Chart(ctxMonthlyPaid, {
    type: "bar",
    data: {
        labels: months, // X-axis: Months
        datasets: [
            {
                label: "Paid Invoices",
                data: paidInvoicesData,
                backgroundColor: "#223D7B", // Blue for Paid
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                position: 'top', // Position of the legend
            }
        }
    }
});
</script>


<script>
const ctx = document.getElementById("monthlyUnpaidInvoicesChart").getContext("2d");

// Monthly labels for the chart
const months1 = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

// Data from PHP variables (Only Unpaid Invoices)
const unpaidInvoicesData = <?php echo $unpaidInvoices; ?>;

const monthlyUnpaidInvoicesChart = new Chart(ctx, {
    type: "bar",
    data: {
        labels: months1, // X-axis: Months
        datasets: [
            {
                label: "Unpaid Invoices",
                data: unpaidInvoicesData,
                backgroundColor: "#B80C0C", // Red for Unpaid
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                position: 'top', // Position of the legend
            }
        }
    }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    fetch("fetch_unpaid_customers.php") // Adjust based on your PHP file path
        .then(response => response.json())
        .then(data => {
            // Populate Bar Chart (Top 10 Unpaid Customers)
            let ctx1 = document.getElementById("topCustomersChart").getContext("2d");
            new Chart(ctx1, {
                type: "bar",
                data: {
                    labels: data.customers,
                    datasets: [{
                        label: "Unpaid Amount (₱)",
                        data: data.amounts,
                        backgroundColor: "#4767B1"
                    }]
                },
                options: {
                    indexAxis: "y",
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { beginAtZero: true }
                    }
                }
            });

            // Populate Pie Chart (Unpaid Customer Percentage)
            let pieColors = ["#FF6384", "#36A2EB", "#FFCE56", "#4BC0C0", "#9966FF", "#FF9F40", "#8BC34A", "#E91E63", "#009688", "#795548"];

            // Initialize Pie Chart with Percentage Labels
            let unpaidPieChart = new Chart(document.getElementById("unpaidPieChart"), {
                type: "doughnut",
                data: {
                    labels: data.customers,
                    datasets: [{
                        data: data.percentages,
                        backgroundColor: pieColors.slice(0, data.customers.length)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "35%", // Adjust cutout size for doughnut effect
                    plugins: {
                        legend: { display: false }, // Hide default legend
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    let value = tooltipItem.raw;
                                    return `${data.customers[tooltipItem.dataIndex]}: ${value}%`;
                                }
                            }
                        },
                        datalabels: {
                            color: "#fff", // Set text color
                            font: { weight: "bold", size: 14 }, // Customize font
                            formatter: (value) => value + "%" // Show percentage
                        }
                    }
                },
                plugins: [ChartDataLabels] // Enable Chart.js Data Labels plugin
            });

            // Generate Custom Legend for Pie Chart
            let legendContainer = document.getElementById("pieChartLegend");
            legendContainer.innerHTML = ""; // Clear before adding new
            data.customers.forEach((label, index) => {
                const legendItem = document.createElement("li");
                legendItem.innerHTML = `<span style="background-color: ${pieColors[index]}; width: 12px; height: 12px; display: inline-block; margin-right: 5px;"></span> ${label}`;
                legendContainer.appendChild(legendItem);
            });
        })
        .catch(error => console.error("Error loading chart data:", error));
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    fetch("fetch_paid_customers.php") // Adjust based on your PHP file path
        .then(response => response.json())
        .then(data => {
            // Populate Bar Chart (Top 10 Unpaid Customers)
            let ctx1 = document.getElementById("topPaidCustomersChart").getContext("2d");
            new Chart(ctx1, {
                type: "bar",
                data: {
                    labels: data.paidcustomers,
                    datasets: [{
                        label: "Paid Amount (₱)",
                        data: data.paidamounts,
                        backgroundColor: "#4767B1"
                    }]
                },
                options: {
                    indexAxis: "y",
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { beginAtZero: true }
                    }
                }
            });

            // Populate Pie Chart (Unpaid Customer Percentage)
            let pieColors = ["#FF6384", "#36A2EB", "#FFCE56", "#4BC0C0", "#9966FF", "#FF9F40", "#8BC34A", "#E91E63", "#009688", "#795548"];

            // Initialize Pie Chart with Percentage Labels
            let paidPieChart = new Chart(document.getElementById("paidPieChart"), {
                type: "doughnut",
                data: {
                    labels: data.paidcustomers,
                    datasets: [{
                        data: data.paidpercentages,
                        backgroundColor: pieColors.slice(0, data.paidcustomers.length)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "35%", // Adjust cutout size for doughnut effect
                    plugins: {
                        legend: { display: false }, // Hide default legend
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    let value = tooltipItem.raw;
                                    return `${data.paidcustomers[tooltipItem.dataIndex]}: ${value}%`;
                                }
                            }
                        },
                        datalabels: {
                            color: "#fff", // Set text color
                            font: { weight: "bold", size: 14 }, // Customize font
                            formatter: (value) => value + "%" // Show percentage
                        }
                    }
                },
                plugins: [ChartDataLabels] // Enable Chart.js Data Labels plugin
            });

            // Generate Custom Legend for Pie Chart
            let legendContainer1 = document.getElementById("pieChartLegend1");
            legendContainer1.innerHTML = ""; // Clear before adding new
            data.paidcustomers.forEach((label, index) => {
                const legendItem1 = document.createElement("li");
                legendItem1.innerHTML = `<span style="background-color: ${pieColors[index]}; width: 12px; height: 12px; display: inline-block; margin-right: 5px;"></span> ${label}`;
                legendContainer1.appendChild(legendItem1);
            });
        })
        .catch(error => console.error("Error loading chart data:", error));
});
</script>


<style>
.container {
    max-width: auto;
    margin: auto;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.kpi-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr); /* 3 cards per row */
    gap: 15px;
    margin-top: 20px;
}

.card {
    background: linear-gradient(135deg, #0a1d4e, #003080);
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.tables-wrapper {
    display: flex;
    gap: 15px;
    justify-content: space-between;
    margin-top: 20px;
}

.table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
    padding: 10px;
    overflow-x: auto;
    flex: 1;
}

.small-table {
    max-width: 48%; /* Adjusts width to fit side-by-side */
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px; /* Reduce font size */
}

th, td {
    border: 1px solid #ddd;
    padding: 6px; /* Reduce padding */
    text-align: center;
}

th {
    background:linear-gradient(135deg, #0a1d4e, #003080);
    color: white;
    font-size: 12px;
}

h3 {
    font-size: 14px;
    text-align: center;
}

.charts-container {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin: 20px 0;
}
.chart-box1 {
    width: 100%;
    max-width: auto; /* Adjust as needed */
    height: 300px; /* Reduce height */
    margin: auto;
}


.chart-box {
    width: 50%; /* Reduced width */
    max-width: auto;
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.chart-box canvas {
    height: 250px !important;
    max-height: 250px;
    width: 100%;
}

.chart-box.pie-chart {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px;
}

.pie-chart-container {
    flex: 1;
    max-width: 60%;
}

.pie-chart-legend {
    flex: 1;
    max-width: 40%;
    text-align: left;
}

.pie-chart-legend ul {
    list-style: none;
    padding: 0;
}

.pie-chart-legend li {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    font-size: 14px;
}

.pie-chart-legend span {
    width: 12px;
    height: 12px;
    display: inline-block;
    margin-right: 8px;
    border-radius: 50%;
}

.pie-chart-legend1 {
    flex: 1;
    max-width: 40%;
    text-align: left;
}

.pie-chart-legend1 ul {
    list-style: none;
    padding: 0;
}

.pie-chart-legend1 li {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    font-size: 14px;
}

.pie-chart-legend1 span {
    width: 12px;
    height: 12px;
    display: inline-block;
    margin-right: 8px;
    border-radius: 50%;
}

/* Stack charts on smaller screens */
@media (max-width: 768px) {
    .charts-container {
        flex-direction: column;
        align-items: center;
    }
    .chart-box {
        width: 100%;
    }
}



/* Responsive Design */
@media (max-width: 768px) {
    .kpi-cards {
        grid-template-columns: repeat(2, 1fr); /* 2 cards per row on tablets */
    }
}

@media (max-width: 480px) {
    .kpi-cards {
        grid-template-columns: 1fr; /* 1 card per row on mobile */
    }
}

.pagination {
    text-align: center;
    margin-top: 10px;
}

.pagination a {
    padding: 8px 12px;
    text-decoration: none;
    background: #007bff;
    color: white;
    border-radius: 4px;
    margin: 0 5px;
}

.pagination a:hover {
    background: #0056b3;
}

</style>
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
    document.querySelectorAll(".invoice-row").forEach(row => {
        row.addEventListener("click", function() {
            let invoiceId = this.getAttribute("data-invoice-id");
            fetchTransactionHistory(invoiceId);
        });
    });
});

function fetchTransactionHistory(customerId) {
    fetch('super_get_customer_transactions.php?customer_id=' + customerId)
    .then(response => response.text())
    .then(data => {
        document.getElementById("transactionContent").innerHTML = data;
        document.getElementById("transactionHistoryPanel").classList.add("open");
    });
}


function closePanel() {
    document.getElementById("transactionHistoryPanel").classList.remove("open");
}

</script>
<script>
document.getElementById('updateAging').addEventListener('click', function() {
    fetch('update_aging.php')
        .then(response => response.text())
        .then(data => {
            alert('Aging report updated!');
            location.reload(); // Reload page to reflect new data
        });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    showTab('invoice-display'); // Set the default visible tab on page load
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
<script>
function fetchKPIData1() {
    fetch('get_kpi_paid.php')
    .then(response => response.json())
    .then(data => {
        document.getElementById("totalAR").textContent = `₱${data.totalPaid}`;
        document.getElementById("paidPercent").textContent = `${data.paidPercent}%`;
        document.getElementById("total").textContent = `${data.total}`;
    })
    .catch(error => console.error('Error fetching KPI data (Paid):', error));
}

function fetchKPIData() {
    fetch('get_kpi_data.php')
    .then(response => response.json())
    .then(data => {
        document.getElementById("totalReceivables").textContent = `₱${data.totalReceivables}`;
        document.getElementById("pastDuePercent").textContent = `${data.pastDuePercent}%`;
        document.getElementById("over90").textContent = `₱${data.over90}`;
    })
    .catch(error => console.error('Error fetching KPI data:', error));
}

// Ensure both functions run on page load
window.addEventListener('load', () => {
    fetchKPIData1();
    fetchKPIData();
});
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
    fetch("fetch_invoice_data.php")
        .then(response => response.json())
        .then(data => {
            renderPaymentsChart(data.payments);
            renderPaymentMethodChart(data.payment_methods);
            renderInvoiceStatusChart(data.invoice_status);

            // Update legend amounts
            document.getElementById("paidAmount").textContent = `₱${data.payments.paid.toFixed(2)}`;
            document.getElementById("pendingAmount").textContent = `₱${data.payments.pending_overdue.toFixed(2)}`;

            // Update payment method counts
            document.getElementById("cashCount").textContent = `₱${data.payment_methods.cash.amount.toFixed(2)}`;
            document.getElementById("creditCount").textContent = `₱${data.payment_methods.credit.amount.toFixed(2)}`;
            document.getElementById("bankCount").textContent = `₱${data.payment_methods.bank_transfer.amount.toFixed(2)}`;
            document.getElementById("onlineCount").textContent = `₱${data.payment_methods.online.amount.toFixed(2)}`;

            // Update invoice status counts
            document.getElementById("pendingCount").textContent = data.invoice_status.pending;
            document.getElementById("paidCount").textContent = data.invoice_status.paid;
            document.getElementById("overdueCount").textContent = data.invoice_status.overdue;
        })
        .catch(error => console.error("Error fetching data:", error));

    function renderPaymentsChart(data) {
        new Chart(document.getElementById("paymentsChart"), {
            type: "doughnut",
            data: {
                labels: ["Paid", "Pending & Overdue"],
                datasets: [{
                    data: [data.paid, data.pending_overdue],
                    backgroundColor: ["#4CAF50", "#FF9800"]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false } // Hide chart legend since we have custom legends
                }
            }
        });
    }

    function renderPaymentMethodChart(data) {
        new Chart(document.getElementById("paymentMethodChart"), {
            type: "doughnut",
            data: {
                labels: ["Cash", "Credit", "Bank Transfer", "Online"],
                datasets: [{
                    data: [
                        data.cash.amount, 
                        data.credit.amount, 
                        data.bank_transfer.amount, 
                        data.online.amount
                    ],
                    backgroundColor: ["#3498db", "#9b59b6", "#2ecc71", "#f1c40f"]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    function renderInvoiceStatusChart(data) {
        new Chart(document.getElementById("invoiceStatusChart"), {
            type: "doughnut",
            data: {
                labels: ["Unpaid", "Paid", "Overdue"],
                datasets: [{
                    data: [data.pending, data.paid, data.overdue],
                    backgroundColor: ["#f39c12", "#2ecc71", "#e74c3c"]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});

</script>
</body>
</html>