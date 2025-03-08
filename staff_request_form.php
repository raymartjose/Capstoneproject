<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="icon" href="img/logo-sm.png">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/staff_request_form.css">
    <link rel= "stylesheet" href= "https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css" >
</head>
<body>

<?php
session_start();
include "assets/databases/dbconfig.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['name'];
$user_role = $_SESSION['role_display'];

?>
<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'fetchEmployees' && isset($_POST['department'])) {
        $department = mysqli_real_escape_string($connection, $_POST['department']);
        $query = "SELECT employee_id, name, position FROM employees WHERE department = '$department' ORDER BY name ASC";
        $result = mysqli_query($conn, $query);

        $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
        }

        echo json_encode($employees);
        exit;
    }
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
                <a href="staff_dashboard.php"><span class="las la-tachometer-alt"></span>
                <span>Dashboard</span></a>
            </li>
            <li>
            <a href="staff_financial.php" class="active"><span class="las la-balance-scale"></span>
            <span>Financial Request</span></a>
            </li>
            <li class="submenu">
            <a href="#"><span class="las la-sitemap"></span>
            <span>Financial Reports</span></a>
            <ul class="submenu-items">
                <li><a href="staff_coa.php"><span class="las la-folder"></span> Chart of Accounts</a></li>
                <li><a href="staff_balance_sheet.php"><span class="las la-chart-line"></span> Balance Sheet</a></li>
                <li><a href="staff_account_receivable.php"><span class="las la-file-invoice"></span> Accounts Receivable</a></li>
            </ul>
        </li>
            <li>
                <a href="staff_payroll.php"><span class="las la-users"></span>
                <span>Staffing & Payroll</span></a>
            </li>
            <li>
                <a href="staff_audit_log.php"><span class="las la-file-invoice"></span>
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
        <span id="overdue-count" style="
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
        

        <main>


<div id="request-details">
    <h2>Request Details</h2>
    <form action="save_request.php" method="POST">

    <div class="form-group">
            <label for="request_type">Request Type:</label>
            <select id="request_type" name="request_type" required>
                <option value="budget_requests">Budget Requests</option>
                <option value="expense_reimbursement">Expense Reimbursement</option>
                <option value="capital_expenditures">Capital Expenditures</option>
                <option value="other">Other</option>
            </select>
        </div>

        <div class="form-group">
        <label for="department">Department:</label>
        <select id="department" name="department" required onchange="fetchEmployees()">
    <option value="">Select Department</option>
    <?php
    include('assets/databases/dbconfig.php');

    $query = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department ASC";
    $result = mysqli_query($connection, $query);

    while ($row = mysqli_fetch_assoc($result)) {
        echo '<option value="' . htmlspecialchars($row['department']) . '">' . htmlspecialchars($row['department']) . '</option>';
    }
    ?>
</select>


        </div>

        
        <div class="form-group">
            <label for="staff_name">Requestor:</label>
            <select id="staff_name" name="staff_name" required onchange="fetchEmployeeDetails()">
    <option value="">Select Employee</option>
</select>

        </div>

        <div class="form-group">
            <label for="staff_id">Staff ID:</label>
            <input type="text" id="staff_id" name="staff_id" required>
        </div>

        <div class="form-group">
            <label for="position">Position:</label>
            <input type="text" id="position" name="position" required>
        </div>

        <div class="form-group">
            <label for="amount">Requested Amount:</label>
            <input type="number" id="amount" name="amount" required>
        </div>

        <div class="form-group full-width">
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4" required></textarea>
        </div>


    </div>



        <div id="documents">
    <h2>Documents</h2>

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
</div>

</div>

<div id="remarks">
    <div class="remarks-wrapper">
        <!-- Remarks Column -->
        <h2>Remarks</h2>
        <div class="remarks-container">
        <div class="remark-message">
            <div class="remark-header">
                <span></span>
                <span class="remark-date"></span>
            </div>
            <p class="remark-text"></p>
        </div>

    <input type="text" id="remarkText" name="remarks[]" class="remarks" placeholder="Write a remark...">
</div>

</div>
<div class="form-buttons">
    <button type="submit" name="save_request" class="save-btn">Save and Submit</button>

            <button type="button" class="cancel-btn">Cancel Request</button>
        </div>
    </form>


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

    fetch("save_request.php", {
        method: "POST",
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Request saved successfully!");
            window.location.reload(); // Reload to reflect changes
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(error => console.error("Error:", error));
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
    const overdueCount = document.getElementById("overdue-count");

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
        const seconds = Math.floor((now - past) / 1000);

        if (seconds < 60) return `${seconds} seconds ago`;
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes} minutes ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours} hours ago`;
        const days = Math.floor(hours / 24);
        if (days < 7) return `${days} days ago`;
        const weeks = Math.floor(days / 7);
        if (weeks < 4) return `${weeks} weeks ago`;
        const months = Math.floor(days / 30);
        if (months < 12) return `${months} months ago`;
        const years = Math.floor(days / 365);
        return `${years} years ago`;
    }

    function fetchNotifications() {
        fetch('fetch_notifications.php')
            .then(response => response.json())
            .then(data => {
                notificationDropdown.innerHTML = "";
                notificationDropdown.appendChild(arrow);
                notificationDropdown.appendChild(header);

                if (data.length > 0) {
                    data.forEach(item => {
                        let link = "";
                        let statusLabel = "";
                        let labelColor = "";

                        if (item.type === "invoice") {
                            link = `invoice.php?id=${item.id}`;
                            statusLabel = "Overdue";
                            labelColor = "red";
                        } else if (item.type === "request") {
                            link = item.status === "Pending" ? `request_form.php?id=${item.id}` : `staff_update_request.php?id=${item.id}`;
                            statusLabel = item.status;
                            labelColor = item.status === "Pending" ? "orange" : "blue";
                        }

                        let notificationItem = document.createElement("div");
                        notificationItem.innerHTML = `
                            <a href="${link}" class="notification-item" data-id="${item.id}" style="
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
                                    <strong style="font-size: 15px; color: #222;">${item.type === "invoice" ? "Invoice #" + item.id : "Request #" + item.id}</strong><br>
                                    ${item.type === "invoice" ? `<span style="color: #555; font-size: 13px;">Customer: ${item.customer_name}</span><br>` : `<span style="color: #555; font-size: 13px;">Type: ${item.request_type}</span><br>`}
                                    <span style="
                                        display: inline-block;
                                        background: ${labelColor};
                                        color: white;
                                        font-size: 12px;
                                        font-weight: bold;
                                        padding: 3px 8px;
                                        border-radius: 5px;
                                        margin-top: 5px;
                                    ">${statusLabel}</span>
                                    <br>
                                    <span style="font-size: 12px; color: #888;">${timeAgo(item.created_at)}</span>
                                </div>
                            </a>
                        `;
                        notificationDropdown.appendChild(notificationItem);
                    });

                    document.querySelectorAll(".notification-item").forEach(item => {
                        item.addEventListener("click", function(event) {
                            let currentCount = parseInt(overdueCount.innerText);
                            if (currentCount > 0) {
                                overdueCount.innerText = currentCount - 1;
                                if (currentCount - 1 === 0) {
                                    overdueCount.style.display = "none";
                                }
                            }
                        });
                    });

                } else {
                    notificationDropdown.innerHTML += "<p style='text-align:center; padding: 20px; font-size: 14px; color: #555;'>No notifications</p>";
                }

                overdueCount.innerText = data.length;
                overdueCount.style.display = data.length > 0 ? "inline-block" : "none";
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
function fetchEmployees() {
    let department = document.getElementById("department").value;
    let staffDropdown = document.getElementById("staff_name");
    let staffIdField = document.getElementById("staff_id");
    let positionField = document.getElementById("position");

    if (department === "") {
        staffDropdown.innerHTML = '<option value="">Select Employee</option>';
        staffIdField.value = "";
        positionField.value = "";
        return;
    }

    let formData = new FormData();
    formData.append("department", department);

    fetch("fetch_employees.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        staffDropdown.innerHTML = '<option value="">Select Employee</option>';
        data.forEach(employee => {
            let option = document.createElement("option");
            option.value = employee.employee_id;
            option.textContent = employee.name;
            option.dataset.position = employee.position;
            staffDropdown.appendChild(option);
        });
    })
    .catch(error => console.error("Error fetching employees:", error));
}

document.getElementById("staff_name").addEventListener("change", function() {
    let selectedOption = this.options[this.selectedIndex];
    let staffIdField = document.getElementById("staff_id");
    let positionField = document.getElementById("position");

    if (selectedOption.value) {
        staffIdField.value = selectedOption.value;
        positionField.value = selectedOption.dataset.position;
    } else {
        staffIdField.value = "";
        positionField.value = "";
    }
});
</script>

</body>
</html>