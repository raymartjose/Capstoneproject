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
include "fetch_count.php";
include "super_admin_fetch_request.php";
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

$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Get the current page number, default to 1
$offset = ($page - 1) * $limit; // Calculate the offset

$requests = $connection->query("SELECT * FROM requests WHERE status IN ('Pending','Approved','Rejected','Cancelled') LIMIT $limit OFFSET $offset");

$totalRequestsQuery = "SELECT COUNT(*) AS total FROM requests WHERE status IN ('Pending','Approved','Rejected','Cancelled')";
    $totalResult = $connection->query($totalRequestsQuery);
    $totalRow = $totalResult->fetch_assoc();
    $totalRequests = $totalRow['total'];
    $totalPages = ceil($totalRequests / $limit); // Total pages


$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;
$cancelledCount = 0;

$statusQuery = "SELECT status, COUNT(*) as count FROM requests GROUP BY status";
$statusResult = $connection->query($statusQuery);

if ($statusResult->num_rows > 0) {
    while ($row = $statusResult->fetch_assoc()) {
        switch ($row['status']) {
            case 'Pending':
                $pendingCount = $row['count'];
                break;
            case 'Approved':
                $approvedCount = $row['count'];
                break;
            case 'Rejected':
                $rejectedCount = $row['count'];
                break;
            case 'Cancelled':
                $cancelledCount = $row['count'];
                break;
        }
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
        <style>
.kpi-metrics {
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* 3 cards per row */
    gap: 10px;
    margin-bottom: 5px;
}
.metric {
    cursor: pointer;
    background: #0a1d4e;
    color: white;
    padding: 10px;
    border-radius: 8px;
    text-align: center;
}

.metric:hover {
        background-color: #0056b3;
    }
.head {
        display: flex;
        justify-content: space-between; /* Align heading left and button right */
        align-items: center; /* Vertically center the content */
        margin-bottom: 10px;
    }

    /* Styling for the heading */
    .head h3 {
        margin: 0;
        font-size: 18px;
        font-weight: bold;
        color: #333;
    }

    /* Styling for the button */
    .head button {
        background-color: #0a1d4e;
        color: white;
        border: none;
        cursor: pointer;
        padding: 10px;
        font-size: 12px;
        font-weight: bold;
        border-radius: 5px; /* Rounded corners */
        transition: background 0.3s ease, box-shadow 0.3s ease;
        display: inline-flex;
        align-items: center; /* Vertically center the content */
        gap: 8px;
    }

    .head button:hover {
        background-color: #0056b3;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    }

    /* Font awesome plus icon */
    .head button .las {
        font-size: 18px;
    }

    /* Table styling */
    .request {
        width: 100%;
        border-collapse: collapse;
    }

    .request th, .request td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    .request th {
        background-color: #0a1d4e;
        color: white;
        font-size: 13px;
    }

    .request td {
        font-size: 14px;
    }

    .request tbody tr:hover {
        background-color: #d1697b; /* Hover effect for rows */
        cursor: pointer;
    }

    .pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 20px;
}

.pagination a {
    margin: 0 10px;
    padding: 5px 10px;
    background-color: #0a1d4e;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}

.pagination a:hover {
    background-color: #0056b3;
}

.pagination span {
    padding: 5px 10px;
}

            </style>

        <main>
        <div class="kpi-metrics">
    <div class="metric" onclick="filterRequests('Pending')">
        <h4>Pending</h4>
        <p><?= $pendingCount ?></p>
    </div>
    <div class="metric" onclick="filterRequests('Approved')">
        <h4>Approved</h4>
        <p><?= $approvedCount ?></p>
    </div>
    <div class="metric" onclick="filterRequests('Rejected')">
        <h4>Rejected</h4>
        <p><?= $rejectedCount ?></p>
    </div>
    <div class="metric" onclick="filterRequests('Cancelled')">
        <h4>Cancelled</h4>
        <p><?= $cancelledCount ?></p>
    </div>
</div>



<!-- Request Table -->
<div class="request">
    <div class="head">
        <h3>Requests</h3>
    </div>

    <div class="table-responsive">
        <table width="100%" class="request" id="request-table">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Requestor</th>
                    <th>Department</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="request-table-body">
            <?php
    // Assuming your query looks like this:
    $requests = $connection->query("SELECT requests.*, employees.name FROM requests 
                                     JOIN employees ON requests.staff_id = employees.id 
                                     WHERE requests.status IN ('Pending','Approved','Rejected','Cancelled') 
                                     LIMIT $limit OFFSET $offset");

    // The rest of your code remains the same.
    if ($requests->num_rows > 0) {
        while ($request = $requests->fetch_assoc()) {
            echo "<tr class='request-row' data-status='" . htmlspecialchars($request['status']) . "' onclick='viewRequestDetails(" . $request['id'] . ")'>";
            echo "<td>" . htmlspecialchars($request['id']) . "</td>";
            echo "<td>" . htmlspecialchars($request['name']) . "</td>";
            echo "<td>" . htmlspecialchars($request['department']) . "</td>";
            echo "<td>" . htmlspecialchars($request['amount']) . "</td>";
            echo "<td>" . htmlspecialchars($request['status']) . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='5'>No requests found</td></tr>";
    }
?>

</tbody>

        </table>
        <div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>">Previous</a>
    <?php endif; ?>

    <span>Page <?= $page ?> of <?= $totalPages ?></span>

    <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>">Next</a>
    <?php endif; ?>
</div>

    </div>
</div>


        </main>
    </div>



<script>
function updateRequestStatus(requestId, status, type) {
    fetch("update_request_status.php", {
        method: "POST",
        body: JSON.stringify({ id: requestId, status: status, type: type }),
        headers: { "Content-Type": "application/json" }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            const errorMessage = data.error ? `Failed to update the request: ${data.error}` : "Failed to update the request.";
            alert(errorMessage);
        }
    })
    .catch(error => {
        console.error("Error updating request status:", error);
        alert("An error occurred. Please try again.");
    });
}

</script>

<script>
    function viewRequestDetails(requestId) {
        // For demonstration, we will just alert the request ID
        window.location.href = "request_form.php?id=" + requestId;
    }
</script>
<style>
    /* Add these styles to your CSS file or within a <style> tag */
table tbody tr {
    cursor: pointer; /* Changes the cursor to a pointer when hovering over rows */
}

table tbody tr:hover {
    background-color: #d1697b; /* Adds a light gray background on hover */
    transition: background-color 0.3s ease; /* Smooth transition for the hover effect */
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
    // JavaScript to filter the requests by status
    function filterRequests(status) {
        console.log("Filtering by status: " + status); // Debugging line

        // Get all rows in the table
        const rows = document.querySelectorAll('#request-table-body .request-row');

        rows.forEach(row => {
            const rowStatus = row.getAttribute('data-status');
            console.log("Row status: " + rowStatus); // Debugging line

            // Show or hide row based on matching status
            if (rowStatus === status) {
                row.style.display = ''; // Show the row
            } else {
                row.style.display = 'none'; // Hide the row
            }
        });
    }

    // Call filterRequests('Returned') when page loads to show "Returned" requests by default
    window.onload = function() {
        filterRequests('Pending');
    }
</script>

</body>
</html>