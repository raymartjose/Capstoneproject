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
session_start();  // Start the session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect to login if not logged in
    exit();
}

$user_name = $_SESSION['name'];  // User name from session
$user_role = $_SESSION['role_display'];  // User role from session

include "assets/databases/dbconfig.php";

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($request_id === 0) {
    die("Invalid request ID.");
}
$request_query = $connection->query("
    SELECT r.*, e.name AS staff_name, e.position 
    FROM requests r 
    LEFT JOIN employees e ON r.staff_id = e.id 
    WHERE r.id = $request_id
");
$request_data = $request_query->fetch_assoc();

// Fetch request data from the requests table
$request_query = $connection->query("SELECT * FROM requests WHERE id = $request_id");

// Fetch attachments related to the request
$attachments_query = $connection->query("SELECT * FROM attachments WHERE request_id = $request_id");

// Fetch remarks related to the request
$remarks_query = $connection->query("SELECT * FROM remarks WHERE request_id = $request_id");

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
    <form action="update_request.php?page=staff_financial.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">

    <div class="form-group">
            <label for="request_type">Request Type:</label>
            <select id="request_type" name="request_type">
            <option value="budget_requests" <?php if ($request_data['request_type'] == 'budget_requests') echo 'selected'; ?>>Budget Requests</option>
            <option value="expense_reimbursement" <?php if ($request_data['request_type'] == 'expense_reimbursement') echo 'selected'; ?>>Expense Reimbursement</option>
            <option value="capital_expenditures" <?php if ($request_data['request_type'] == 'capital_expenditures') echo 'selected'; ?>>Capital Expenditures</option>
            <option value="other" <?php if ($request_data['request_type'] == 'other') echo 'selected'; ?>>Other</option>
            </select>
        </div>

        <div class="form-group">
        <label for="department">Department:</label>
            <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($request_data['department']); ?>">
        </div>

        <div class="form-group">
        <label for="staff_name">Requestor:</label>
        <input type="text" id="staff_name" name="staff_name" value="<?php echo htmlspecialchars($request_data['staff_name']); ?>">
        </div>

        <div class="form-group">
            <label for="staff_id">Staff ID:</label>
            <input type="text" id="staff_id" name="staff_id" value="<?php echo htmlspecialchars($request_data['staff_id']); ?>">
        </div>

        <div class="form-group">
            <label for="position">Position:</label>
            <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($request_data['position']); ?>">
        </div>

        <div class="form-group">
        <label for="amount">Requested Amount:</label>
        <input type="number" id="amount" name="amount" value="<?php echo htmlspecialchars($request_data['amount']); ?>">
        </div>

        <div class="form-group full-width">
        <label for="description">Description:</label>
        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($request_data['description']); ?></textarea>
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
    <h2>Remarks</h2>
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
        <button type="submit" class="save-btn" name="save" value="Save">Save and Submit</button>
        <button type="submit" class="cancel-btn" name="cancel" value="Cancel">Cancel Request</button>
    </div>
    </form>

    <div class="transaction-graph-container">
    <h3>Transaction History</h3>
    <canvas id="transactionChart"></canvas>
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

</body>
</html>