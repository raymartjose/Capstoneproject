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
include "assets/databases/dbconfig.php";

session_start();  // Start the session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect to login if not logged in
    exit();
}

$user_name = $_SESSION['name'];  // User name from session
$user_role = $_SESSION['role_display'];  // User role from session

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
                <li><a href="coa.php" class="active"><span class="las la-folder"></span> Chart of Accounts</a></li>
                <li><a href="balance_sheet.php"><span class="las la-chart-line"></span> Balance Sheet</a></li>
                <li><a href="account_receivable.php"><span class="las la-file-invoice"></span> Accounts Receivable</a></li>
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
                Chart of Accounts
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
    position: absolute;
    top: 12px;
    right: 20px;
    font-size: 1.8rem;
    font-weight: bold;
    color: var(--text-grey);
    cursor: pointer;
    transition: color 0.2s;
    background: #fff;
    padding: -0px;
}

.close:hover {
    color: #d1697b;
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
        <button onclick="document.getElementById('addAccountModal').style.display='block'">+ Add Account</button>

        <!-- Add Account Modal -->
        <!-- Add Account Modal -->
<div id="addAccountModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('addAccountModal').style.display='none'">&times;</span>
        <h2>Add New Account</h2>
        <form id="addAccountForm">
            <label>Account Code</label>
            <input type="text" id="account_code" name="account_code" required>

            <label>Account Name</label>
            <input type="text" id="account_name" name="account_name" required>

            <label>Category</label>
            <select id="category" name="category">
                <option value="Asset">Asset</option>
                <option value="Liability">Liability</option>
                <option value="Equity">Equity</option>
                <option value="Income">Income</option>
                <option value="Expense">Expense</option>
            </select>

            <button type="submit">Add Account</button>
        </form>
    </div>
</div>


        <!-- Chart of Accounts Table -->
        <table id="coaTable">
            <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th>Category</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
    <?php
    // Fetch total income from transactions table
    $income_query = "SELECT SUM(amount) AS total_income FROM transactions";
    $income_result = mysqli_query($connection, $income_query);
    $income_row = mysqli_fetch_assoc($income_result);
    $total_income = $income_row['total_income'] ?? 0;

    // Fetch total salaries (net pay) from payroll table
    $payroll_query = "SELECT SUM(net_pay) AS total_salaries FROM payroll";
    $payroll_result = mysqli_query($connection, $payroll_query);
    $payroll_row = mysqli_fetch_assoc($payroll_result);
    $total_salaries = $payroll_row['total_salaries'] ?? 0;

    // Fetch total employee expenses from employee_expenses table
    $expenses_query = "SELECT SUM(amount) AS total_expenses FROM employee_expenses";
    $expenses_result = mysqli_query($connection, $expenses_query);
    $expenses_row = mysqli_fetch_assoc($expenses_result);
    $total_expenses = $expenses_row['total_expenses'] ?? 0;

    $cogsQuery = "SELECT SUM(total_cogs) AS amount FROM cogs";
$cogsResult = mysqli_query($connection, $cogsQuery);
$cogsRow = mysqli_fetch_assoc($cogsResult);
$cogs = $cogsRow['amount'] ?? 0;

    // Compute updated Cash balance
    $updated_cash_balance = $total_income - $total_salaries - $total_expenses - $cogs;

    // Fetch chart_of_accounts data
    $query = "SELECT id, account_code, account_name, category, balance FROM chart_of_accounts";
    $result = mysqli_query($connection, $query);

    while ($row = mysqli_fetch_assoc($result)) {
        $balance = $row['balance']; // Default balance from DB

        // Override balance for "Salaries & Wages"
        if ($row['account_name'] == 'Salaries & Wages') {
            $balance = $total_salaries;
        }

        // Override balance for "Maintenance and Other Operating Expenses"
        if ($row['account_name'] == 'Maintenance and Other Operating Expenses') {
            $balance = $total_expenses;
        }

        // Override balance for "Cash"
        if ($row['account_name'] == 'Cash') {
            $balance = $updated_cash_balance;
        }

        echo "<tr>
            <td>{$row['account_code']}</td>
            <td>{$row['account_name']}</td>
            <td>{$row['category']}</td>
            <td>{$balance}</td>
        </tr>";
    }
    ?>
</tbody>

        </table>
    </main>
</div>

<style>
body { font-family: Arial, sans-serif; }
/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: white;
    border-radius: 10px;
    overflow: hidden; /* Ensures rounded corners are applied */
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
}

/* Table Header */
th {
    background: var(--main-color);  /* Use the same color as the button */
    color: white;
    font-weight: bold;
    padding: 12px;
    text-align: left;
}

/* Table Rows */
td {
    padding: 10px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

/* Alternating Row Colors */
tbody tr:nth-child(even) {
    background: rgba(0, 0, 0, 0.05);
}

/* Hover Effect */
tbody tr:hover {
    background: var(--main-color);
    color: #fff;
    transition: 0.3s;
}

/* Modal Background */
#addAccountModal { 
    display: none; 
    position: fixed; 
    left: 0; 
    top: 0; 
    width: 100%; 
    height: 100%; 
    background-color: rgba(0,0,0,0.5); 
    z-index: 10001;
    transition: opacity 0.3s ease-in-out;
}

/* Modal Content */
#addAccountModal .modal-content {
    position: relative;
    background: white;
    margin: 10% auto;
    padding: 25px;
    width: 40%;
    border-radius: 12px;
    box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.15);
    animation: fadeIn 0.3s ease-in-out;
}

/* Close Button */
#addAccountModal .close {
    position: absolute;
    top: 12px;
    right: 20px;
    font-size: 1.8rem;
    font-weight: bold;
    color: var(--text-grey);
    cursor: pointer;
    transition: color 0.2s;
}

#addAccountModal .close:hover {
    color: #d1697b;
}

/* Form Styling */
#addAccountModal form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

#addAccountModal label {
    font-weight: 600;
    color: #333;
}

/* Input Fields */
#addAccountModal input, select, textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
    transition: all 0.2s ease-in-out;
}

#addAccountModal input:focus, select:focus, textarea:focus {
    border-color: var(--main-color);
    outline: none;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.2);
}

/* Submit Button */
button {
    background: var(--main-color);
    color: white;
    border: none;
    padding: 12px;
    font-size: 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s ease-in-out;
}


/* Modal Animation */
@keyframes fadeIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

button { background: var(--main-color);
    border-radius: 10px;
    color: #fff;
    font-size: .8rem;
    padding: .5rem 1rem;
    border: 1px solid var(--main-color);
    cursor: pointer;}
</style>

<script>
document.getElementById('addAccountForm').addEventListener('submit', function(event) {
    event.preventDefault();

    var formData = new FormData(this);

    fetch('add_account.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert(data);
        location.reload();
    })
    .catch(error => console.error('Error:', error));
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
</body>
</html>