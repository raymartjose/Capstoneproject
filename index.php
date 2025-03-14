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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .chart-container {
            display: flex;
            align-items: center;
            justify-content: space-around;
            height: 300px;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            background-color: #f9f9f9;
            position: relative;
        }
        .chart-box {
            width: 50%;
            height: 250px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .chart-box canvas {
            max-width: 100% !important;
            max-height: 100% !important;
        }
        .chart-labels {
            width: 40%;
        }
        .chart-center-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .chart-labels ul {
            list-style: none;
            padding: 0;
        }
        .chart-labels li {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #1a237e;
            color: #fff;
        }
        .filter-section {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    justify-content: flex-end; /* Moves elements to the right */
}

#dates{
    font-weight: bold;
    font-size: 40px;
    color: #333;
}

.filter-section input[type="month"] {
    padding: 8px 12px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 5px;
    outline: none;
    transition: all 0.3s ease-in-out;
}

.filter-section input[type="month"]:focus {
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
}

#download {
    width: 15%;
    padding: 10px;
    font-size: 16px;
    border-radius: 6px;
    background: #ed6978;
    border: none;
    color: white;
    transition: background 0.3s;
    cursor: pointer;
}

#download:hover {
    background: #d1697b;
}

    </style>
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
            <a href="#"><span class="las la-sitemap"></span>
            <span>Financial Reports</span></a>
            <ul class="submenu-items">
                <li><a href="coa.php"><span class="las la-folder"></span> Chart of Accounts</a></li>
                <li><a href="balance_sheet.php"><span class="las la-chart-line"></span> Balance Sheet</a></li>
                <li><a href="account_receivable.php"><span class="las la-file-invoice"></span> Accounts Receivable</a></li>
            </ul>
        </li>
        <li>
                <a href="index.php" class="active"><span class="las la-file-invoice"></span>
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
                Payroll
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
        <div class="container">
        <div class="filter-section" style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
        <span id="dates" class="las la-calendar"><label for="monthFilter"></label></span>
        <input type="month" id="monthFilter" onchange="fetchFilteredData()">
        <button id="download" onclick="downloadCSV()">Download CSV</button>
    </div>
        <div class="chart-container">
        <div class="chart-box" style="display: flex; align-items: center;">
    <canvas id="payrollChart" style="max-width: 300px; max-height: 300px; border-right: 3px solid #ccc; padding-right: 10px;"></canvas>
    <div class="chart-center-text" id="chartTotal" style="font-weight: bold; font-size: 15px;"></div>
</div>
<div class="chart-labels" id="chartLabels">
    <ul id="labelList" style="list-style: none; padding-left: 20px;"></ul>
</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Employee Count</th>
                    <th>Payroll Count</th>
                    <th>Total Gross Pay</th>
                    <th>Net Pay</th>
                </tr>
            </thead>
            <tbody id="payrollTableBody">
            <?php
    include "assets/databases/dbconfig.php";

    $sql = "SELECT 
                e.department, 
                COUNT(DISTINCT e.id) AS employee_count, 
                COUNT(p.id) AS payroll_count,
                SUM(p.gross_pay) AS total_gross_pay,
                SUM(p.net_pay) AS total_net_pay
            FROM employees e
            LEFT JOIN payroll p ON e.employee_id = p.employee_id
            GROUP BY e.department";
    
    $result = $connection->query($sql);

    $data = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
            echo "<tr>
                    <td>{$row['department']}</td>
                    <td>{$row['employee_count']}</td>
                    <td>{$row['payroll_count']}</td>
                    <td>₱" . number_format($row['total_gross_pay'], 2) . "</td>
                    <td>₱" . number_format($row['total_net_pay'], 2) . "</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='6'>No payroll data available</td></tr>";
    }

    $connection->close();
    ?>
</tbody>
            </tbody>
        </table>
    </div>
        
</main>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const monthFilter = document.getElementById("monthFilter");
    monthFilter.value = new Date().toISOString().slice(0, 7); // Set default to current month
    fetchFilteredData();
});

function fetchFilteredData() {
    const month = document.getElementById("monthFilter").value;
    fetch(`fetch_payroll_data.php?month=${month}`)
        .then(response => response.json())
        .then(data => {
            updateTable(data);
            updateChart(data, month);
        })
        .catch(error => console.error("Error fetching payroll data:", error));
}

function updateTable(data) {
    let tbody = document.getElementById("payrollTableBody");
    tbody.innerHTML = "";

    if (data.length > 0) {
        data.forEach(row => {
            tbody.innerHTML += `<tr>
                <td>${row.department}</td>
                <td>${row.employee_count}</td>
                <td>${row.payroll_count}</td>
                <td>₱${parseFloat(row.total_gross_pay).toLocaleString()}</td>
                <td>₱${parseFloat(row.total_net_pay).toLocaleString()}</td>
            </tr>`;
        });
    } else {
        tbody.innerHTML = "<tr><td colspan='5'>No payroll data available</td></tr>";
    }
}

function updateChart(data, selectedMonth) {
    let labels = [];
    let values = [];
    let totalNetPay = 0;
    let colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4CAF50', '#9C27B0', '#FF9800', '#009688'];

    if (data.length > 0) {
        data.forEach((item, index) => {
            labels.push(item.department);
            values.push(parseFloat(item.total_net_pay));
            totalNetPay += parseFloat(item.total_net_pay);
        });
    } else {
        labels = ["No Data"];
        values = [1];
        colors = ["#d3d3d3"];
    }

    document.getElementById('chartTotal').innerHTML = `Net Pay:<br>₱${totalNetPay.toLocaleString()}`;


    // Destroy previous chart instance if it exists
    if (window.payrollChart && typeof window.payrollChart.destroy === "function") {
    window.payrollChart.destroy();
}


    const ctx = document.getElementById('payrollChart').getContext('2d');
    window.payrollChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '85%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            let index = tooltipItem.dataIndex;
                            let percentage = ((values[index] / totalNetPay) * 100).toFixed(2);
                            return `${labels[index]}: ₱${values[index].toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    let labelList = document.getElementById('labelList');
    labelList.innerHTML = '';
    if (data.length > 0) {
        data.forEach((item, index) => {
            let percentage = ((values[index] / totalNetPay) * 100).toFixed(2);
            let listItem = `<li style="color:${colors[index]}; font-weight: bold;">
                <span style="display: inline-block; width: 12px; height: 12px; background-color:${colors[index]}; margin-right: 5px;"></span>
                ${item.department}: ₱${values[index].toLocaleString()} (${percentage}%)
            </li>`;
            labelList.innerHTML += listItem;
        });
    } else {
        labelList.innerHTML = "<li>No payroll data available</li>";
    }
}



function downloadCSV() {
    const month = document.getElementById("monthFilter").value;
    fetch(`fetch_payroll_data.php?month=${month}`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                alert("No payroll data available for this month.");
                return;
            }

            let csvContent = "data:text/csv;charset=utf-8,Department,Employee Count,Payroll Count,Total Gross Pay,Net Pay\n";

            data.forEach(row => {
                csvContent += `${row.department},${row.employee_count},${row.payroll_count},${row.total_gross_pay},${row.total_net_pay}\n`;
            });

            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `payroll_data_${month}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        })
        .catch(error => console.error("Error downloading CSV:", error));
}

document.addEventListener("DOMContentLoaded", fetchFilteredData);
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