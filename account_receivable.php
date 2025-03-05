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
                <li><a href="balance_sheet.php"><span class="las la-chart-line"></span> Balance Sheet</a></li>
                <li><a href="account_receivable.php" class="active"><span class="las la-file-invoice"></span> Accounts Receivable</a></li>
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
                Account Receivable Aging
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
        <div class="card">
            <div class="card-header">
                <h3>Receivables Aging Report</h3>

                <label for="">Due Date Filter:<input type="date" id="dueDateFilter" class="date-picker" onchange="filterByDueDate()"></label>
                
                <button onclick="exportTableToCSV('receivables_aging.csv')" class="btn-export">
                    <span class="las la-file-export"></span> Export CSV
                </button>
            </div>

            <div class="table-responsive">
                <table id="agingTable">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Invoice No</th>
                            <th>Due Date</th>
                            <th>Current</th>
                            <th>1-30 Days</th>
                            <th>31-60 Days</th>
                            <th>61-90 Days</th>
                            <th>90+ Days</th>
                            <th>Total Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        include('assets/databases/dbconfig.php');

                        $sql = "SELECT customer_name, invoice_id, due_date, current_amount, 
                                past_due_30, past_due_60, past_due_90, past_due_90plus, total_due 
                                FROM receivables";
                        $result = $connection->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['customer_name']}</td>
                                        <td>{$row['invoice_id']}</td>
                                        <td>{$row['due_date']}</td>
                                        <td class='current'>{$row['current_amount']}</td>
                                        <td class='past30'>{$row['past_due_30']}</td>
                                        <td class='past60'>{$row['past_due_60']}</td>
                                        <td class='past90'>{$row['past_due_90']}</td>
                                        <td class='past90plus'>{$row['past_due_90plus']}</td>
                                        <td class='total_due'>{$row['total_due']}</td>
                                    </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9' style='text-align: center;'>No records found</td></tr>";
                        }
                        $connection->close();
                        ?>
                    </tbody>
                    <tfoot>
            <tr>
                <td colspan="3" style="font-weight: bold; text-align: right;">Total:</td>
                <td id="totalCurrent">0</td>
                <td id="totalPast30">0</td>
                <td id="totalPast60">0</td>
                <td id="totalPast90">0</td>
                <td id="totalPast90plus">0</td>
                <td id="totalDue">0</td>
            </tr>
        </tfoot>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
    function exportTableToCSV(filename) {
    var csv = [];
    var rows = document.querySelectorAll("#agingTable tr:not([style*='display: none'])"); // Exclude hidden rows

    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        for (var j = 0; j < cols.length; j++) {
            row.push(cols[j].innerText);
        }
        csv.push(row.join(","));
    }

    var csvFile = new Blob(["\ufeff" + csv.join("\n")], { type: "text/csv;charset=utf-8;" });
    var downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}

</script>

<script>
    function calculateTotals() {
        let totalCurrent = 0, totalPast30 = 0, totalPast60 = 0, totalPast90 = 0, totalPast90plus = 0, totalDue = 0;

        document.querySelectorAll(".current").forEach(cell => totalCurrent += parseFloat(cell.innerText.replace(/[^0-9.-]+/g, "")) || 0);        ;
        document.querySelectorAll(".past30").forEach(cell => totalPast30 += parseFloat(cell.innerText.replace(/[^0-9.-]+/g, "")) || 0); 
        document.querySelectorAll(".past60").forEach(cell => totalPast60 += parseFloat(cell.innerText.replace(/[^0-9.-]+/g, "")) || 0); 
        document.querySelectorAll(".past90").forEach(cell => totalPast90 += parseFloat(cell.innerText.replace(/[^0-9.-]+/g, "")) || 0); 
        document.querySelectorAll(".past90plus").forEach(cell => totalPast90plus += parseFloat(cell.innerText.replace(/[^0-9.-]+/g, "")) || 0); 
        document.querySelectorAll(".total_due").forEach(cell => totalDue += parseFloat(cell.innerText.replace(/[^0-9.-]+/g, "")) || 0); 

        document.getElementById("totalCurrent").innerText = totalCurrent.toFixed(2);
        document.getElementById("totalPast30").innerText = totalPast30.toFixed(2);
        document.getElementById("totalPast60").innerText = totalPast60.toFixed(2);
        document.getElementById("totalPast90").innerText = totalPast90.toFixed(2);
        document.getElementById("totalPast90plus").innerText = totalPast90plus.toFixed(2);
        document.getElementById("totalDue").innerText = totalDue.toFixed(2);
    }

    window.onload = calculateTotals;
</script>

<script>
    function filterByDueDate() {
    let inputDate = document.getElementById("dueDateFilter").value;
    let table = document.getElementById("agingTable");
    let rows = table.getElementsByTagName("tr");

    let totalCurrent = 0, totalPast30 = 0, totalPast60 = 0, totalPast90 = 0, totalPast90plus = 0, totalDue = 0;

    for (let i = 1; i < rows.length - 1; i++) { // Exclude header and footer
        let dueDateCell = rows[i].getElementsByTagName("td")[2]; // Due Date is in the 3rd column (index 2)
        if (dueDateCell) {
            let dueDate = dueDateCell.innerText.trim();
            let formattedDueDate = new Date(dueDate).toISOString().split("T")[0]; // Convert to YYYY-MM-DD
            
            if (formattedDueDate === inputDate || inputDate === "") {
                rows[i].style.display = ""; // Show row

                // Recalculate totals for visible rows
                totalCurrent += parseFloat(rows[i].getElementsByClassName("current")[0]?.innerText.replace(/[^0-9.-]+/g, "") || 0);
                totalPast30 += parseFloat(rows[i].getElementsByClassName("past30")[0]?.innerText.replace(/[^0-9.-]+/g, "") || 0);
                totalPast60 += parseFloat(rows[i].getElementsByClassName("past60")[0]?.innerText.replace(/[^0-9.-]+/g, "") || 0);
                totalPast90 += parseFloat(rows[i].getElementsByClassName("past90")[0]?.innerText.replace(/[^0-9.-]+/g, "") || 0);
                totalPast90plus += parseFloat(rows[i].getElementsByClassName("past90plus")[0]?.innerText.replace(/[^0-9.-]+/g, "") || 0);
                totalDue += parseFloat(rows[i].getElementsByClassName("total_due")[0]?.innerText.replace(/[^0-9.-]+/g, "") || 0);
            } else {
                rows[i].style.display = "none"; // Hide row
            }
        }
    }

    // Update footer totals
    document.getElementById("totalCurrent").innerText = totalCurrent.toFixed(2);
    document.getElementById("totalPast30").innerText = totalPast30.toFixed(2);
    document.getElementById("totalPast60").innerText = totalPast60.toFixed(2);
    document.getElementById("totalPast90").innerText = totalPast90.toFixed(2);
    document.getElementById("totalPast90plus").innerText = totalPast90plus.toFixed(2);
    document.getElementById("totalDue").innerText = totalDue.toFixed(2);
}

</script>


<style>
    .card {
        background: white;
        padding: 20px;
        margin: 20px;
        border-radius: 5px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }

    .btn-export {
        background: #ed6978;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        display: flex;
        align-items: center;
    }

    .btn-export span {
        margin-right: 5px;
    }

    .btn-export:hover {
        background: #d1697b;
    }

    .table-responsive {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: center;
    }

    th {
        background: #ed6978;
    }
    tfoot {
        background: #d1697b;
    }
    .date-picker {
    padding: 8px;
    margin-left: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 14px;
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
</body>
</html>