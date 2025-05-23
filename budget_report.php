<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="icon" href="img/logo-sm.png">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel= "stylesheet" href= "https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css" >
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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

// Get current month and year
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
$selectedDepartment = isset($_GET['department']) ? $_GET['department'] : "";

// Fetch total company budget for the selected month/year
$budgetQuery = $connection->prepare("
    SELECT amount FROM company_budget WHERE month = ? AND year = ?
");
$budgetQuery->bind_param("ii", $currentMonth, $currentYear);
$budgetQuery->execute();
$budgetResult = $budgetQuery->get_result();
$budgetData = $budgetResult->fetch_assoc();
$totalBudget = $budgetData['amount'] ?? 0;

// Fetch approved budget requests by department
$approvedBudgetQuery = "
    SELECT department, SUM(amount) AS approved_budget 
    FROM requests 
    WHERE request_type = 'budget_requests' 
    AND status = 'Approved' 
    AND MONTH(created_at) = ? 
    AND YEAR(created_at) = ?
";

$params = [$currentMonth, $currentYear];
$types = "ii";

if (!empty($selectedDepartment)) {
    $approvedBudgetQuery .= " AND department = ?";
    $params[] = $selectedDepartment;
    $types .= "s";
}

$approvedBudgetQuery .= " GROUP BY department";
$stmt = $connection->prepare($approvedBudgetQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$approvedBudgetResult = $stmt->get_result();

$departments = [];
$totalApprovedBudget = 0;
while ($row = $approvedBudgetResult->fetch_assoc()) {
    $departments[$row['department']]['approved_budget'] = $row['approved_budget'];
    $totalApprovedBudget += $row['approved_budget'];
}

// Fetch expense breakdown per department
$expenseQuery = "
    SELECT ex.expense_date, e.name, e.department, ex.category, ex.description, ex.amount 
    FROM employee_expenses ex 
    JOIN employees e ON ex.employee_id = e.employee_id 
    WHERE MONTH(ex.expense_date) = ? 
    AND YEAR(ex.expense_date) = ?
";

$params = [$currentMonth, $currentYear];
$types = "ii";

if (!empty($selectedDepartment)) {
    $expenseQuery .= " AND e.department = ?";
    $params[] = $selectedDepartment;
    $types .= "s";
}

$expenseQuery .= " ORDER BY e.department";
$stmt = $connection->prepare($expenseQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$expenseResult = $stmt->get_result();

$totalExpenses = 0;
$categoryExpenses = [];
while ($row = $expenseResult->fetch_assoc()) {
    if (!isset($row['expense_date'])) {
        $row['expense_date'] = 'N/A'; // Prevent undefined key error
    }
    $departments[$row['department']]['expenses'][] = $row;
    $totalExpenses += $row['amount'];
    $categoryExpenses[$row['category']] = ($categoryExpenses[$row['category']] ?? 0) + $row['amount'];
}


// Calculate budget usage percentage
$budgetUsage = ($totalBudget > 0) ? ($totalApprovedBudget / $totalBudget) * 100 : 0;
$remainingBudget = max(0, 100 - $budgetUsage);

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
                Budget Utilization
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
.header-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap; /* Ensures responsiveness on small screens */
}

.date-filter-container {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.date-filter-container label {
    font-weight: bold;
    color: #333;
}

.date-filter-container select {
    padding: 8px 12px;
    border: 1px solid #0a1d4e;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    background-color: white;
    color: #333;
    transition: all 0.3s ease-in-out;
}

.date-filter-container select:focus {
    outline: none;
    border-color: #0a1d4e;
    box-shadow: 0px 0px 5px rgba(0, 123, 255, 0.5);
}

.download-buttons {
    display: flex;
    gap: 10px;
}

.download-buttons .btn {
    padding: 10px 15px;
    font-size: 16px;
    cursor: pointer;
    background-color: #0a1d4e;
    color: #fff;
}

.cabinet-panel {
        position: fixed;
        top: 0;
        right: -40%; /* Initially hidden */
        width: 40%;
        height: 100%;
        background-color: #fff;
        box-shadow: -4px 0 10px rgba(0, 0, 0, 0.2);
        overflow-y: auto;
        transition: right 0.3s ease-in-out;
        z-index: 1000;
    }

    /* Panel Content */
    .panel-content {
        padding: 20px;
    }

    /* Close Button */
    .close-btn {
        font-size: 22px;
        font-weight: bold;
        color: #333;
        float: right;
        cursor: pointer;
    }

    .close-btn:hover {
        color: #d9534f;
    }

    /* Form Styling */
    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 16px;
    }

    /* Save Button */
    .btn-primary {
        background-color: #0a1d4e;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        width: 100%;
    }

    .btn-primary:hover {
        background-color: #082046;
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

        <div id="addExpensePanel" class="cabinet-panel">
    <div class="panel-content">
        <span class="close-btn" onclick="closeExpensePanel()">&times;</span>
        <h3>Add Expense</h3>
        <form action="add_expense.php" method="POST">
            <div class="form-group">
                <label for="department">Department</label>
                <select id="department" name="department" required onchange="fetchEmployees()">
                    <option value="">Select a department</option>
                    <?php
                    $deptQuery = "SELECT DISTINCT department FROM employees ORDER BY department";
                    $result = $connection->query($deptQuery);
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['department']) . '">' . htmlspecialchars($row['department']) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="employee">Employee Name</label>
                <select id="employee" name="employee" required>
                    <option value="">Select an employee</option>
                </select>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select a category</option>
                    <?php
                    $query = "SELECT id, name FROM expense_categories ORDER BY name";
                    $result = $connection->query($query);
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['name']) . '">' . htmlspecialchars($row['name']) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="expense_date">Date</label>
                <input type="date" id="expense_date" name="expense_date" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Expense</button>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script>
    function openExpensePanel() {
        document.getElementById("addExpensePanel").style.right = "0";
    }
    function closeExpensePanel() {
        document.getElementById("addExpensePanel").style.right = "-40%"; // Slide out
    }
</script>

        
        <main>


<div class="header-container">
    <!-- Date and Department Filter -->
    <form method="GET" id="filterForm" class="date-filter-container">
    <label for="month">Month:</label>
    <select name="month" id="month" onchange="document.getElementById('filterForm').submit();">
        <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= sprintf('%02d', $m); ?>" <?= ($m == $currentMonth) ? 'selected' : ''; ?>>
                <?= date('F', mktime(0, 0, 0, $m, 1)); ?>
            </option>
        <?php endfor; ?>
    </select>

    <label for="year">Year:</label>
    <select name="year" id="year" onchange="document.getElementById('filterForm').submit();">
        <?php for ($y = date('Y') - 5; $y <= date('Y'); $y++): ?>
            <option value="<?= $y; ?>" <?= ($y == $currentYear) ? 'selected' : ''; ?>><?= $y; ?></option>
        <?php endfor; ?>
    </select>

    <label for="department">Department:</label>
    <select name="department" id="department" onchange="document.getElementById('filterForm').submit();">
        <option value="">All Departments</option>
        <?php
        $deptQuery = "SELECT DISTINCT department FROM employees ORDER BY department";
        $result = $connection->query($deptQuery);
        while ($row = $result->fetch_assoc()):
        ?>
            <option value="<?= htmlspecialchars($row['department']); ?>" 
                <?= ($selectedDepartment == $row['department']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($row['department']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</form>

    <!-- Download Buttons on the Right -->
    <div class="download-buttons">
    <button onclick="openExpensePanel()" class="btn btn-success">Add Expense</button>
        <button id="downloadCSV" class="btn btn-primary1">Download CSV</button>
    </div>
</div>


<h2 style="text-align: center; color: #0a1d4e;">
    Expense Tracker - <?= date('F', mktime(0, 0, 0, $currentMonth, 1)) . " " . $currentYear; ?>
</h2>

<table border="1" width="100%" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background-color: #0a1d4e; color: white;">
            <th>Department</th>
            <th>Approved Budget</th>
            <th>Total Expenses</th>
            <th>Remaining Budget</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($departments)): ?>
            <?php foreach ($departments as $dept => $data): ?>
                <tr>
                    <td><?= htmlspecialchars($dept); ?></td>
                    <td>₱<?= number_format($data['approved_budget'] ?? 0, 2); ?></td>
                    <td>₱<?= number_format(array_sum(array_column($data['expenses'] ?? [], 'amount')), 2); ?></td>
                    <td>₱<?= number_format(($data['approved_budget'] ?? 0) - array_sum(array_column($data['expenses'] ?? [], 'amount')), 2); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" style="text-align: center;">No data available for this selection.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<h3 style="text-align: center;">Expense Breakdown</h3>
<table border="1" width="100%" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background-color: #0a1d4e; color: white;">
            <th>Date</th>
            <th>Employee Name</th>
            <th>Department</th>
            <th>Category</th>
            <th>Description</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($departments)): ?>
            <?php foreach ($departments as $dept => $data): ?>
                <?php if (!empty($data['expenses'])): ?>
                    <?php foreach ($data['expenses'] as $expense): ?>
                        <tr>
                            <td><?= htmlspecialchars($expense['expense_date']); ?></td>
                            <td><?= htmlspecialchars($expense['name']); ?></td>
                            <td><?= htmlspecialchars($expense['department']); ?></td>
                            <td><?= htmlspecialchars($expense['category']); ?></td>
                            <td><?= htmlspecialchars($expense['description']); ?></td>
                            <td>₱<?= number_format($expense['amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align: center;">No expenses found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>


                
        </main>
    </div>

    <script>
document.getElementById('month').addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});

document.getElementById('year').addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});
</script>

<script>
document.getElementById("downloadCSV").addEventListener("click", function () {
    let csvContent = "data:text/csv;charset=utf-8,Department,Employee Name,Category,Description,Amount\n";
    
    document.querySelectorAll("tbody tr").forEach(row => {
        let columns = row.querySelectorAll("td");
        if (columns.length === 5) { // Ensure it's a data row
            let department = columns[1].innerText;
            let employee = columns[0].innerText;
            let category = columns[2].innerText;
            let description = columns[3].innerText;
            let amount = columns[4].innerText.replace("₱", "").replace(",", ""); // Clean currency format
            csvContent += `"${department}","${employee}","${category}","${description}","${amount}"\n`;
        }
    });

    let encodedUri = encodeURI(csvContent);
    let link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "budget_report.csv");
    document.body.appendChild(link);
    link.click();
});

</script>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
function fetchEmployees() {
    var department = document.getElementById("department").value;
    var employeeSelect = document.getElementById("employee");

    employeeSelect.innerHTML = "<option value=''>Loading...</option>";

    if (department) {
        fetch("expense_employee.php?department=" + department)
            .then(response => response.json())
            .then(data => {
                employeeSelect.innerHTML = "<option value=''>Select an employee</option>";
                data.forEach(employee => {
                    employeeSelect.innerHTML += `<option value="${employee.id}">${employee.name}</option>`;
                });
            })
            .catch(error => console.error("Error fetching employees:", error));
    } else {
        employeeSelect.innerHTML = "<option value=''>Select an employee</option>";
    }
}
</script>



</body>
</html>