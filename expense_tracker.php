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
<style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ed6978;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #ed6978;
        }
            /* Center the chart container */
#yearlyChart {
    max-width: 100%;
    height: 400px; /* Adjust height as needed */
    display: block;
    margin: 20px auto; /* Center the chart */
}

/* Style the chart heading */
h2 {
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    color: #333;
    margin-bottom: 15px;
}

/* Customize chart container */
.chart-container {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    padding: 20px;
    max-width: 700px;
    margin: 0 auto; /* Center the chart */
}
@media (max-width: 768px) {
    .chart-container {
        margin-bottom: 20px;
    }
}


    </style>

<body>

<?php
session_start();  // Start the session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect to login if not logged in
    exit();
}

$user_name = $_SESSION['name'];  // User name from session
$user_role = $_SESSION['role_display'];  // User role from session

?>
<?php
include "assets/databases/dbconfig.php";
$currentYear = date("Y");

// Fetch Employee Expenses Grouped by Month and Category for Current Year
$query = "SELECT 
            DATE_FORMAT(expense_date, '%Y-%m') AS month, 
            category,
            SUM(amount) AS total_expense
          FROM employee_expenses
          WHERE YEAR(expense_date) = ?
          GROUP BY month, category
          ORDER BY month ASC";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $currentYear);
$stmt->execute();
$result = $stmt->get_result();


// Fetch Payroll Data for Current Year
$payrollQuery = "SELECT DATE_FORMAT(pay_period_start, '%Y-%m') AS month, SUM(net_pay) AS total_payroll
                FROM payroll 
                WHERE YEAR(pay_period_start) = ? 
                GROUP BY month 
                ORDER BY month ASC";
$payrollStmt = $connection->prepare($payrollQuery);
$payrollStmt->bind_param("i", $currentYear);
$payrollStmt->execute();
$payrollResult = $payrollStmt->get_result();


// Fetch Yearly Employee Expenses Data for the Current Year
$yearlyQuery = "SELECT DATE_FORMAT(expense_date, '%Y-%m') AS month, SUM(amount) AS total 
                FROM employee_expenses 
                WHERE YEAR(expense_date) = ?
                GROUP BY month 
                ORDER BY month ASC";
$yearlyStmt = $connection->prepare($yearlyQuery);
$yearlyStmt->bind_param("i", $currentYear);
$yearlyStmt->execute();
$yearlyResult = $yearlyStmt->get_result();

// Convert results to arrays for charts
$yearlyData = [];
$payrollData = [];
$expensesByMonth = [];

while ($row = $yearlyResult->fetch_assoc()) {
    $yearlyData[$row['month']] = $row['total'];
}

while ($row = $payrollResult->fetch_assoc()) {
    $payrollData[$row['month']] = $row['total_payroll'];
}


// Prepare Expense Data for Table
while ($row = $result->fetch_assoc()) {
    $expensesByMonth[$row['month']][$row['category']] = $row['total_expense'];
}

// Fetch All Categories for Table Headers
$categoryQuery = "SELECT DISTINCT category FROM employee_expenses";
$categoryResult = $connection->query($categoryQuery);

$categories = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row['category'];
}


// Fetch Yearly Employee Expenses Data for Last Year
$lastYearQuery = "SELECT DATE_FORMAT(expense_date, '%Y-%m') AS month, SUM(amount) AS total 
                  FROM employee_expenses 
                  WHERE YEAR(expense_date) = ? 
                  GROUP BY month 
                  ORDER BY month ASC";
$lastYearStmt = $connection->prepare($lastYearQuery);
$lastYearStmt->bind_param("i", $lastYear);
$lastYearStmt->execute();
$lastYearResult = $lastYearStmt->get_result();

$lastYearData = [];
while ($row = $lastYearResult->fetch_assoc()) {
    $lastYearData[$row['month']] = $row['total'];
}

// Fetch Last Year Payroll Data
$lastYearPayrollQuery = "SELECT DATE_FORMAT(pay_period_start, '%Y-%m') AS month, SUM(net_pay) AS total_payroll
                         FROM payroll 
                         WHERE YEAR(pay_period_start) = ? 
                         GROUP BY month 
                         ORDER BY month ASC";
$lastYearPayrollStmt = $connection->prepare($lastYearPayrollQuery);
$lastYearPayrollStmt->bind_param("i", $lastYear);
$lastYearPayrollStmt->execute();
$lastYearPayrollResult = $lastYearPayrollStmt->get_result();

$lastYearPayrollData = [];
while ($row = $lastYearPayrollResult->fetch_assoc()) {
    $lastYearPayrollData[$row['month']] = $row['total_payroll'];
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
                Expense Tracker
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
        <h2>2025 Monthly Expense Tracker</h2>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <?php foreach ($categories as $category) { ?>
                    <th><?= $category ?></th>
                <?php } ?>
                <th>Salary & Wages</th>
                <th>Total Expenditure</th>
                
            </tr>
        </thead>
        <tbody>
        <?php 
        $totalPerCategory = array_fill_keys($categories, 0);
        $grandTotalExpense = 0;

        foreach ($expensesByMonth as $month => $expenseData) { 
            $totalExpense = 0;
            $payrollExpense = $payrollData[$month] ?? 0;
            
        ?>
        <tr>
            <td><?= date("F", strtotime($month)) ?></td>
            <?php foreach ($categories as $category) { 
                $amount = $expenseData[$category] ?? 0;
                $totalPerCategory[$category] += $amount;
                $totalExpense += $amount;
            ?>
                <td><?= number_format($amount, 2) ?></td>
            <?php } ?>
            <td><?= number_format($payrollExpense, 2) ?></td>
            <td><strong><?= number_format($totalExpense + $payrollExpense, 2) ?></strong></td>
        </tr>
        <?php 
            $grandTotalExpense += $totalExpense + $payrollExpense;
        } ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:bold; background-color:#d1697b;">
            <td>Total</td>
            <?php foreach ($categories as $category) { ?>
                <td><?= number_format($totalPerCategory[$category], 2) ?></td>
            <?php } ?>
            <td><?= number_format(array_sum($payrollData), 2) ?></td>
            <td><?= number_format($grandTotalExpense, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="chart-container">
    <h2>Yearly Expense Breakdown</h2>
    <canvas id="yearlyChart"></canvas>
</div>


        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/countup.js/2.0.7/countUp.umd.js"></script>
<script>
var currentYearData = <?= json_encode($yearlyData) ?>;
var lastYearData = <?= json_encode($lastYearData) ?>;
var payrollData = <?= json_encode($payrollData) ?>;
var lastYearPayrollData = <?= json_encode($lastYearPayrollData) ?>;

var monthNames = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
];

// Initialize arrays
var totalExpenses = new Array(12).fill(0);
var lastYearTotalExpenses = new Array(12).fill(0);

// Process current year expenses
Object.keys(currentYearData).forEach(key => {
    var monthIndex = parseInt(key.split("-")[1], 10) - 1;
    var employeeExpenses = parseFloat(currentYearData[key]) || 0;
    var payroll = parseFloat(payrollData[key]) || 0;
    totalExpenses[monthIndex] = employeeExpenses + payroll;
});

// Process last year expenses
Object.keys(lastYearData).forEach(key => {
    var monthIndex = parseInt(key.split("-")[1], 10) - 1;
    var employeeExpenses = parseFloat(lastYearData[key]) || 0;
    var payroll = parseFloat(lastYearPayrollData[key]) || 0;
    lastYearTotalExpenses[monthIndex] = employeeExpenses + payroll;
});

// Initialize Chart
var ctx1 = document.getElementById('yearlyChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: monthNames,
        datasets: [
            {
                label: 'Current Year',
                data: totalExpenses,
                backgroundColor: 'rgba(237, 105, 120, 0.6)',
                borderColor: 'rgba(237, 105, 120, 1)',
                borderWidth: 1
            },
            {
                label: 'Last Year',
                data: lastYearTotalExpenses,
                backgroundColor: 'rgba(105, 150, 237, 0.6)',
                borderColor: 'rgba(105, 150, 237, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
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

</body>
</html>