<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="icon" href="img/logo-sm.png">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/request_form.css">
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

include "assets/databases/dbconfig.php";

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($request_id === 0) {
    die("Invalid request ID.");
}

// Fetch request data from the requests table
$request_query = $connection->query("
    SELECT r.*, e.name AS staff_name, e.position 
    FROM requests r 
    LEFT JOIN employees e ON r.staff_id = e.id 
    WHERE r.id = $request_id
");
$request_data = $request_query->fetch_assoc();


// Fetch attachments related to the request
$attachments_query = $connection->query("SELECT * FROM attachments WHERE request_id = $request_id");

// Fetch remarks related to the request
$remarks_query = $connection->query("SELECT * FROM remarks WHERE request_id = $request_id");


$request_id = $_GET['id']; // Get request_id from the query string

$query = "SELECT created_at, status FROM requests WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();



$transaction_query = $connection->prepare("
    SELECT changed_at, status, changed_by 
    FROM request_transactions 
    WHERE request_id = ? 
    ORDER BY changed_at ASC
");
$transaction_query->bind_param("i", $request_id);
$transaction_query->execute();
$transaction_result = $transaction_query->get_result();

$transactions = [];
while ($row = $transaction_result->fetch_assoc()) {
    $transactions[] = $row;
}
$transaction_query->close();



$_SESSION['department'] = $request_data['department'];
$selected_department = isset($_GET['department']) ? trim($_GET['department']) : $_SESSION['department'] ?? '';

if (empty($selected_department)) {
    echo "<p style='color: red;'>No department selected.</p>";
}

// Get previous month date range
$prevMonthStart = date("Y-m-01", strtotime("first day of last month"));
$prevMonthEnd = date("Y-m-t", strtotime("last day of last month"));

// Query: Get total previous month expenses & approved budget
$summaryQuery = "SELECT 
                COALESCE(SUM(ex.amount), 0) AS total_previous_expense,
                COALESCE(SUM(r.amount), 0) AS total_previous_budget
            FROM employees e
            LEFT JOIN employee_expenses ex 
                ON e.id = ex.employee_id 
                AND ex.expense_date BETWEEN ? AND ?
            LEFT JOIN requests r 
                ON e.department = r.department 
                AND r.created_at BETWEEN ? AND ?
            WHERE e.department = ?";

$summaryStmt = mysqli_prepare($connection, $summaryQuery);
mysqli_stmt_bind_param($summaryStmt, "sssss", $prevMonthStart, $prevMonthEnd, $prevMonthStart, $prevMonthEnd, $selected_department);
mysqli_stmt_execute($summaryStmt);
$summaryResult = mysqli_stmt_get_result($summaryStmt);
$summaryData = mysqli_fetch_assoc($summaryResult);

$total_previous_expense = $summaryData['total_previous_expense'];
$total_previous_budget = $summaryData['total_previous_budget'];

// Calculate remaining budget
$remaining_budget = $total_previous_budget - $total_previous_expense;

// Query: Get expense breakdown per category
$breakdownQuery = "SELECT 
                ex.category,
                COALESCE(SUM(ex.amount), 0) AS category_expense
            FROM employees e
            LEFT JOIN employee_expenses ex 
                ON e.id = ex.employee_id 
                AND ex.expense_date BETWEEN ? AND ?
            WHERE e.department = ?
            GROUP BY ex.category";

$breakdownStmt = mysqli_prepare($connection, $breakdownQuery);
mysqli_stmt_bind_param($breakdownStmt, "sss", $prevMonthStart, $prevMonthEnd, $selected_department);
mysqli_stmt_execute($breakdownStmt);
$breakdownResult = mysqli_stmt_get_result($breakdownStmt);

$expense_breakdown = [];
while ($row = mysqli_fetch_assoc($breakdownResult)) {
    $expense_breakdown[] = $row;
}

// Performance Score Calculation
$performance_score = ($total_previous_expense > 0 && $total_previous_budget > 0) 
    ? min(($total_previous_expense / $total_previous_budget) * 100, 100) 
    : 100;

// Approval Justification
$budget_variance = $total_previous_expense > 0 ? (($total_previous_budget - $total_previous_expense) / $total_previous_expense) * 100 : 100;
if ($budget_variance <= 10) {
    $approval_recommendation = "✅ Likely to be Approved";
    $justification = "Spending is consistent with previous trends.";
} elseif ($budget_variance > 10 && $budget_variance <= 30) {
    $approval_recommendation = "⚠️ Requires Justification";
    $justification = "Moderate increase. Department must provide reasoning.";
} else {
    $approval_recommendation = "❌ High Increase - Review Needed";
    $justification = "Significant increase detected. Justification required.";
}
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
            <a href="super_financial.php" class="active"><span class="las la-balance-scale"></span>
            <span>Financial Request</span></a>
            <li>
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
                Financial
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
.container1 {
        max-width: 900px;
        margin: 0 auto;
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    section {
        margin-bottom: 30px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    th, td {
        text-align: left;
        padding: 8px;
        border: 1px solid #BDC3C7;
    }

    th {
        background-color: #ecf0f1;
    }

    td {
        background-color: #ffffff;
    }

    .score {
        background-color: #ecf0f1;
        padding: 20px;
        text-align: center;
        border-radius: 8px;
    }

    .score strong {
        font-size: 36px;
        color: #2C3E50;
    }

    footer {
        text-align: center;
        margin-top: 50px;
        font-size: 14px;
        color: #7F8C8D;
    }
        </style>
        
        <main>
        <div class="tabs">
        <button class="tab-button active" onclick="showTab('request-display')">Request Details</button>
    <button class="tab-button" onclick="showTab('budget-utilization')">Budget Utilization</button>
</div>

<div id="budget-utilization" class="tab-content" style="display: block;">
    <div class="container1">
        <!-- Header with Logo and Title -->
        <img src="img/logo1.png" alt="Company Logo" style="width: 17%; margin-bottom: 20px;">
        <section class="report-header">
            <h2>Budget Summary & Performance Report</h2>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($selected_department); ?></p>
            <p><strong>Report Date:</strong> <span id="report-date"><?php echo date("F j, Y"); ?></span></p>
        </section>

        <!-- Overall Budget Performance Section -->
        <section class="budget-performance">
            <h2>Overall Budget Performance</h2>
            <table>
                <thead>
                    <tr>
                        <th>Total Previous Month Expenses</th>
                        <th>Total Previous Month Budget Approved</th>
                        <th>Remaining Budget</th>
                        <th>Budget Change (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo "₱" . number_format($total_previous_expense, 2); ?></td>
                        <td><?php echo "₱" . number_format($total_previous_budget, 2); ?></td>
                        <td><?php echo "₱" . number_format($remaining_budget, 2); ?></td>
                        <td><?php echo number_format($budget_variance, 2) . "%"; ?></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Performance Score Section -->
        <section class="credit-score">
            <h2>Budget Performance Score</h2>
            <div class="score">
                <p><strong><?php echo number_format($performance_score, 2) . "%"; ?></strong></p>
                <p><small><?php echo $approval_recommendation; ?></small></p>
                <br>
                <p><?php echo $justification; ?></p>
            </div>
        </section>

        <!-- Expense Breakdown Section -->
        <section class="expense-breakdown">
            <h2>Expense Breakdown for Previous Month</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Previous Month Expenses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expense_breakdown as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td><?php echo "₱" . number_format($row['category_expense'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Final Decision Insights Section -->
        <section class="recommendations">
            <h2>Final Decision Insights</h2>
            <p>Based on historical performance, budget requests are assessed with the following recommendations:</p>
            <ul>
                <li>✅ **Low increase (≤10%)** – Approval likely.</li>
                <li>⚠️ **Moderate increase (10-30%)** – Justification required.</li>
                <li>❌ **High increase (>30%)** – Needs review before approval.</li>
            </ul>
        </section>

        <!-- Footer Section -->
        <footer class="footer">
            <p>&copy; 2025 C.B. Barangay Enterprises. All rights reserved.</p>
        </footer>
    </div>
</div>

<div id="request-display" class="tab-content" style="display: none;">
        <div id="request-details">
    <h2 id="h2">Request Details</h2>
    <form action="update_request.php?page=super_financial.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">

    <div class="form-group">
            <label for="request_type">Request Type:</label>
            <select id="request_type" name="request_type" disabled>
            <option value="budget_requests" <?php if ($request_data['request_type'] == 'budget_requests') echo 'selected'; ?>>Budget Requests</option>
            <option value="expense_reimbursement" <?php if ($request_data['request_type'] == 'expense_reimbursement') echo 'selected'; ?>>Expense Reimbursement</option>
            <option value="capital_expenditures" <?php if ($request_data['request_type'] == 'capital_expenditures') echo 'selected'; ?>>Capital Expenditures</option>
            <option value="other" <?php if ($request_data['request_type'] == 'other') echo 'selected'; ?>>Other</option>
            </select>
        </div>

        <div class="form-group">
        <label for="department">Department:</label>
            <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($request_data['department']); ?>" disabled>
        </div>

        <div class="form-group">
        <label for="staff_name">Requestor:</label>
        <input type="text" id="staff_name" name="staff_name" value="<?php echo htmlspecialchars($request_data['staff_name']); ?>" disabled>
        </div>

        <div class="form-group">
            <label for="staff_id">Staff ID:</label>
            <input type="text" id="staff_id" name="staff_id" value="<?php echo htmlspecialchars($request_data['staff_id']); ?>" disabled>
        </div>

        <div class="form-group">
            <label for="position">Position:</label>
            <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($request_data['position']); ?>" disabled>
        </div>

        <div class="form-group">
        <label for="amount">Requested Amount:</label>
        <input type="number" id="amount" name="amount" value="<?php echo htmlspecialchars($request_data['amount']); ?>" disabled>
        </div>

        <div class="form-group full-width">
        <label for="description">Description:</label>
        <textarea id="description" name="description" rows="4" disabled><?php echo htmlspecialchars($request_data['description']); ?></textarea>
        </div>


    </div>



        <div id="documents">
    <h2 id="h2">Documents</h2>

    <div id="attachments-container">
    <div class="attachment-row">
    <select name="categories[]" class="file-category">
                <option value="invoice">Invoice</option>
                <option value="receipt">Receipt</option>
                <option value="contract">Contract</option>
                <option value="other">Other</option>
            </select>
    
            <input type="file" name="attachments[]" class="attachment-file" onchange="handleFileUpload(this)">
            <a href="#" download="" class="download-link">Download</a>
            <input type="date" class="file-date">
            <input type="text" class="remarks1" name="remarks1[]" placeholder="Remarks">
            <button type="button" class="add-btn" onclick="addAttachmentRow1()">+</button>
    </div>
    <?php while ($attachment = $attachments_query->fetch_assoc()): ?>
            <div class="attachment-row">
                <select name="categories[]" class="file-category">
                    <option value="invoice" <?php if ($attachment['category'] == 'invoice') echo 'selected'; ?>>Invoice</option>
                    <option value="receipt" <?php if ($attachment['category'] == 'receipt') echo 'selected'; ?>>Receipt</option>
                    <option value="contract" <?php if ($attachment['category'] == 'contract') echo 'selected'; ?>>Contract</option>
                    <option value="other" <?php if ($attachment['category'] == 'other') echo 'selected'; ?>>Other</option>
                </select>
                <input type="text" value="<?php echo htmlspecialchars($attachment['file_name']); ?>">
                <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" download>Download</a>
                <input type="date" value="<?php echo date('Y-m-d', strtotime($attachment['uploaded_at'])); ?>" class="file-date">
                <input type="text" class="remarks1" value="<?php echo htmlspecialchars($attachment['remark']); ?>">
                <button type="button" class="remove-btn" onclick="removeAttachmentRow(this)">-</button>
            </div>
        <?php endwhile; ?>
    
</div>

</div>

<div id="remarks">
    <div class="remarks-wrapper">
    <h2 id="h2">Remarks</h2>
    <div class="remarks-container">
    <?php while ($remark = $remarks_query->fetch_assoc()): ?>
        <div class="remark-message">
            <div class="remark-header">
                <span><?php echo htmlspecialchars($remark['user_name']); ?></span>
                <span class="remark-date"><?php echo htmlspecialchars($remark['created_at']); ?></span>
            </div>
            <p class="remark-text"><?php echo htmlspecialchars($remark['remark']); ?></p>
        </div>
    <?php endwhile; ?>
    <input type="text" id="remarkText" name="remarks[]" class="remarks" placeholder="Write a remark...">
</div>
    

</div>
<div class="form-buttons">
        <button type="submit" class="approve-btn" name="approve" value="Approved">Approve</button>
        <button type="submit" class="reject-btn" name="reject" value="Rejected">Reject</button>
        <button type="submit" class="return-btn" name="return" value="Returned">Return</button>
    </div>
    </form>

    <div class="transaction-graph-container">
    <h3>Transaction History</h3>
    <canvas id="transactionChart"></canvas>
</div>
</div>
</div>
        </main>
    </div>






    <script>
    function handleFileUpload(fileInput) {
        const file = fileInput.files[0];
        const downloadLink = fileInput.parentElement.querySelector(".download-link");
        const fileDateInput = fileInput.parentElement.querySelector(".file-date");

        // Make the download button visible once the file is uploaded
        downloadLink.style.display = 'inline-block';

        // Set the href attribute of the download link to the uploaded file's path
        const fileURL = URL.createObjectURL(file);
        const fileName = file.name;
        
        downloadLink.href = fileURL;
        downloadLink.download = fileName; // Set the file name for download

        // Set the date input to the current date (when the file is uploaded)
        const currentDate = new Date().toISOString().split('T')[0]; // Format the date as YYYY-MM-DD
        fileDateInput.value = currentDate;
    }

    function addAttachmentRow1() {
        let container = document.getElementById("attachments-container");
        let newRow = document.createElement("div");
        newRow.classList.add("attachment-row");
        newRow.innerHTML = `
            <select name="categories[]" class="file-category">
                <option value="invoice">Invoice</option>
                <option value="receipt">Receipt</option>
                <option value="contract">Contract</option>
                <option value="other">Other</option>
            </select>
            <input type="file" name="attachments[]" class="attachment-file" onchange="handleFileUpload(this)">
            <a href="#" download="" class="download-link" style="display:none;">Download</a>
            <input type="date" class="file-date">
            <input type="text" class="remarks1" name="remarks1[]" placeholder="Remarks">
            <button type="button" class="remove-btn" onclick="removeAttachmentRow(this)">-</button>
        `;
        container.appendChild(newRow);
    }

    function removeAttachmentRow(button) {
        let row = button.parentElement; // Get the parent div of the button (the row)
        row.remove(); // Remove the row from the container
    }
</script>

<script>
    document.querySelector(".save-btn").addEventListener("click", function (e) {
    e.preventDefault(); // Prevent default form submission

    let form = document.querySelector("form");
    let formData = new FormData(form); // Automatically collects form inputs

    // Collect file attachments manually
    document.querySelectorAll("#attachments-container .attachment-row").forEach((row, index) => {
        let fileInput = row.querySelector("input[type='file']");
        if (fileInput && fileInput.files.length > 0) {
            formData.append(`attachments[${index}]`, fileInput.files[0]);
        }
        formData.append(`categories[${index}]`, row.querySelector(".file-category").value);
    });

    .catch(error => console.error("Error:", error));
});

</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>


<script>
document.addEventListener("DOMContentLoaded", function () {
    const transactions = <?php echo json_encode($transactions); ?>;
    
    if (!transactions || transactions.length === 0) {
        console.error("No transaction data found.");
        return;
    }

    const ctx = document.getElementById('transactionChart').getContext('2d');
    const statusLabels = ["Pending", "Returned", "Approved", "Rejected", "Cancelled"];

    function getStatusValue(status) {
        return statusLabels.indexOf(status);
    }

    const labels = [];
    const statusData = [];
    const changedByData = [];

    transactions.forEach(transaction => {
        const statusValue = getStatusValue(transaction.status);
        if (statusValue === -1) return;

        labels.push(new Date(transaction.changed_at).toLocaleString());
        statusData.push(statusValue);
        changedByData.push(transaction.changed_by);
    });

    const dataset = {
        label: 'Transaction Status Flow',
        data: statusData,
        backgroundColor: 'rgba(2, 2, 2, 0.2)',
        borderColor: 'rgb(6, 6, 6)',
        borderWidth: 2,
        fill: false,
        pointRadius: 6,
        pointBackgroundColor: 'rgb(0, 0, 0)',
        segment: {
            borderDash: ctx => {
                const index = ctx.p1DataIndex;
                if (index > 0 && statusData[index] < statusData[index - 1]) {
                    return [6, 6]; // Dashed line for status reversion
                }
                return undefined; // Solid line for progression
            }
        },
        datalabels: {
            align: 'top',
            anchor: 'end',
            color: 'black',
            font: { weight: 'bold' },
            formatter: (value, context) => changedByData[context.dataIndex]
        }
    };

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [dataset]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true, position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            const index = tooltipItem.dataIndex;
                            const status = statusLabels[statusData[index]] || 'Unknown';
                            const date = labels[index];
                            const changedBy = changedByData[index];
                            return `Status: "${status}" on ${date}\nChanged by: ${changedBy}`;
                        }
                    }
                },
                datalabels: { display: true } // Enables the labels on the chart
            },
            scales: {
                y: {
                    ticks: { callback: value => statusLabels[value] || '' }
                }
            }
        },
        plugins: [ChartDataLabels] // Enable Chart.js DataLabels plugin
    });
});

</script>


<style>
.transaction-graph-container {
    margin-top: 20px;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.transaction-graph-container h3 {
    text-align: center;
    margin-bottom: 10px;
}

#transactionChart {
    max-height: 300px;
}
</style>
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
    showTab('request-display'); // Set the default visible tab on page load
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