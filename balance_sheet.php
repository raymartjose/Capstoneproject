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
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>


    
</head>
<body>
    <style>
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 5px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #0a1d4e; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        select, input, button { padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        button { background-color: #0a1d4e; color: white; cursor: pointer; }
        button:hover { background-color: #0a1d4e; }
        .form-group { margin-top: 20px; display: flex; gap: 10px; }
    </style>

<?php
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
                <li><a href="coa.php"><span class="las la-folder"></span> Chart of Accounts</a></li>
                <li><a href="balance_sheet.php" class="active"><span class="las la-chart-line"></span> Balance Sheet</a></li>
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
                Balance Sheet
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
    border: none;
    cursor: pointer;
    transition: color 0.3s;
    background: #fff;
    color: var(--text-grey);
}

.close:hover {
    color: red;
}

/* Modal Body */
.modal-body {
    margin-top: 15px;
}

/* Form Styles */
#changePasswordModal .form-group {
    margin-bottom: 15px;
}

#changePasswordModal .form-group label {
    font-weight: bold;
    color: #555;
}

#changePasswordModal .form-control {
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
        <label for="yearSelector">Select Year:</label>
        <select id="yearSelector" onchange="fetchBalanceSheet()">
            <?php
            include('assets/databases/dbconfig.php');
            $yearsQuery = "SELECT DISTINCT YEAR(created_at) as year FROM assets UNION SELECT DISTINCT YEAR(created_at) FROM liabilities ORDER BY year DESC";
            $yearsResult = $connection->query($yearsQuery);
            while ($row = $yearsResult->fetch_assoc()) {
                echo "<option value='" . $row['year'] . "'>" . $row['year'] . "</option>";
            }
            ?>
        </select>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Subcategory</th>
                        <th class="text-right">Amount (Current Year)</th>
                        <th class="text-right">Amount (Previous Year)</th>
                    </tr>
                </thead>
                <tbody id="balanceSheetBody">
                    <?php include('fetch_balance_sheet.php'); ?>
                </tbody>
            </table>
        </div>
        <!--
        <h2 class="mt-4">Add New Asset</h2>
        <form id="addAssetForm" class="form-group">
            <input type="text" name="type" placeholder="Asset Type" required>
            <input type="number" name="value" placeholder="Amount" required>
            <button type="submit">Add Asset</button>
        </form>
        
        <h2 class="mt-4">Add New Liability</h2>
        <form id="addLiabilityForm" class="form-group">
            <input type="text" name="liability_name" placeholder="Liability Name" required>
            <input type="number" name="amount" placeholder="Amount" required>
            <button type="submit">Add Liability</button>
        </form>
        -->

        <div class="charts-container">
    <div style="display: flex; justify-content: space-between;">
        <div style="width: 48%;">
            <h4>Current Year</h4>
            <canvas id="currentYearChart"></canvas>
        </div>
        <div style="width: 48%;">
            <h4>Previous Year</h4>
            <canvas id="previousYearChart"></canvas>
        </div>
    </div>
</div>

    </div>


    <style>
    .charts-container {
        padding: 40px;
        width: 100%;
        max-width: auto;
        background-color: #f4f6f9; /* Light background with soft gradient */
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .charts-container h4 {
        font-size: 1rem;
        color: #0a1d4e;
        text-align: center;
        font-weight: 600;
        text-transform: uppercase;
    }

    .charts-container > div {
        display: flex;
        justify-content: space-between;
        gap: 2%; /* Space between the charts */

    }

    .charts-container > div > div {
        width: 50%;
    }

    .charts-container canvas {
        width: 100%;
        max-width: 100%;
        height: auto;
        border-radius: 12px;
        background-color: #ffffff;
        border: 1px solid #e3e7f1;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        padding: 10px;
    }

    /* Styling for the bar chart's tooltips */
    .chartjs-tooltip {
        background-color: rgba(0, 0, 0, 0.7) !important;
        color: white !important;
        font-size: 0.9rem;
        padding: 8px;
        border-radius: 4px;
    }
</style>


        </main>
    </div>

    <script>
        function fetchBalanceSheet() {
            const year = document.getElementById('yearSelector').value;
            fetch(`fetch_balance_sheet.php?year=${year}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('balanceSheetBody').innerHTML = data;
                });
        }
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
    // Fetch the PHP data (subcategories and amounts) for the current year and previous year
    const balanceData = <?php echo json_encode($data); ?>;

    // Prepare the labels (subcategories) and the data for the current year and previous year
    let currentYearData = [];
    let previousYearData = [];
    let labels = [];

    // Loop through the balance data and populate the labels and values
    for (let category in balanceData) {
        for (let subcategory in balanceData[category]) {
            labels.push(subcategory); // Add subcategory as label

            // For current year and previous year
            currentYearData.push(balanceData[category][subcategory][<?php echo $currentYear; ?>] || 0);
            previousYearData.push(balanceData[category][subcategory][<?php echo $previousYear; ?>] || 0);
        }
    }

    // Current Year Chart
    const ctx1 = document.getElementById('currentYearChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Amount (Current Year)',
                data: currentYearData,
                backgroundColor: '#0a1d4e',
                borderColor: '#0a1d4e',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                datalabels: {
                    color: '#fff', // White text color
                    anchor: 'end', // Position the label at the center of the bar
                    align: 'start',  // Align the label at the center
                    font: {
                        size: 12
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        },
        plugins: [ChartDataLabels]
    });

    // Previous Year Chart
    const ctx2 = document.getElementById('previousYearChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Amount (Previous Year)',
                data: previousYearData,
                backgroundColor: '#FFA725',
                borderColor: '#FFA725',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                datalabels: {
                    color: '#fff', // White text color
                    anchor: 'end', // Position the label at the center of the bar
                    align: 'start',  // Align the label at the center
                    font: {
                        size: 12
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        },
        plugins: [ChartDataLabels]
    });
</script>


</body>
</html>