<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="icon" href="img/logo-sm.png">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/add_user.css">
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
</head>
<body>
<?php
include "user_fetch.php";
include "fetch_users.php";
session_start();  // Start the session
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

// Retrieve error message if any
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
// Clear error message after displaying
unset($_SESSION['error_message']);


include('assets/databases/dbconfig.php');

// Fetch Finance department names
$financeDepartments = [];
$financeEmployees = [];

$deptQuery = "SELECT DISTINCT department FROM employees WHERE department = 'Finance Department'";
$deptResult = $connection->query($deptQuery);
while ($row = $deptResult->fetch_assoc()) {
    $financeDepartments[] = $row['department'];
}

// Fetch employees in Finance department
$empQuery = "SELECT id, name, email, phone FROM employees WHERE department = 'Finance Department'";
$empResult = $connection->query($empQuery);
while ($row = $empResult->fetch_assoc()) {
    $financeEmployees[] = $row;
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
            <a href="add_user.php" class="active"><span class="las la-users"></span>
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
                User Management
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
    <div class="cards">
            <div class="card-single">
                <div>
                    <h3>Staff</h3>
                    <h1><?php echo $role_counts['staff']; ?></h1>
                    <span>Total</span>
                </div>
                <div>
                    <span class="las la-user"></span>
                </div>
            </div>
        <div class="card-single">
            <div>
                <h3>Administrator</h3>
                <h1><?php echo $role_counts['administrator']; ?></h1>
                <span>Total</span>
            </div>
            <div>
                <span class="las la-user"></span>
            </div>
        </div>
        <div class="card-single">
            <div>
                <h3>Super Admin</h3>
                <h1><?php echo $role_counts['super_admin']; ?></h1>
                <span>Total</span>
            </div>
            <div>
                <span class="las la-user"></span>
            </div>
        </div>
    </div>

<!--Modal-->

<!--Add User Modal-->
<div id="addUserPanel" class="cabinet-panel">
    <div class="cabinet-content">
        <span class="close-btn" onclick="closeCabinetPanel()">&times;</span>
        <h3>Add New User</h3>

        <div class="error-message" style="display: none; color: red;"></div>

        <form action="add_users.php" method="POST">
            <div class="form-group">
                <label for="department">Department (Finance Only)</label>
                <select id="department" name="department" required>
                    <?php foreach ($financeDepartments as $dept): ?>
                        <option value="<?= $dept ?>"><?= $dept ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="name">Select Employee</label>
                <select id="name" name="name" required onchange="fillEmployeeDetails()">
                    <option value="">Select Employee</option>
                    <?php foreach ($financeEmployees as $employee): ?>
                        <option value="<?= $employee['name'] ?>" 
                                data-email="<?= $employee['email'] ?>" 
                                data-phone="<?= $employee['phone'] ?>">
                            <?= $employee['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter Email" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone #</label>
                <input type="text" id="phone" name="phone" placeholder="Enter Phone Number" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter Password" required>
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="staff">Staff</option>
                    <option value="administrator">Administrator</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
            
            <button type="submit" class="btn">Add User</button>
        </form>
    </div>
</div>
<!--/Add User Modal-->

<!-- See All Users Modal -->
<div id="seeAllUsersModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeSeeAllModal()">&times;</span>
        <h3>All Users</h3>
        <div class="table-responsive">
            <table width="100%">
                <thead>
                    <tr>
                        <td>ID</td>
                        <td>Name</td>
                        <td>Email</td>
                        <td>Role</td>
                        <td>Status</td>
                        <td>Actions</td>
                    </tr>
                </thead>
                <tbody id="allUsersTableBody">
                    <!-- Data will be populated here by PHP -->
                     
                    <?php
                    include "assets/databases/dbconfig.php";
                    $query = "SELECT id, name, email, status, role FROM users";
                    $result = $connection->query($query);
                    if ($result->num_rows > 0) {
                        while ($user = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
                            echo "<td>" . htmlspecialchars($user['status']) . "</td>";
                            echo "<td>
                                                        <button class='btn-edit las la-edit' 
                                                            onclick=\"openEditModal(
                                                                '" . htmlspecialchars($user['id']) . "',
                                                                '" . htmlspecialchars(addslashes($user['name'])) . "',
                                                                '" . htmlspecialchars(addslashes($user['email'])) . "',
                                                                '" . htmlspecialchars($user['role']) . "',
                                                                '" . htmlspecialchars($user['status']) . "'
                                                            )\">
                                                        </button>
                                                    </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>No users found</td></tr>";
                    }
                    $connection->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- /see all user modal -->

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>Edit User</h3>
        <form id="editUserForm" method="POST">
            <input type="hidden" id="editId" name="id">
            
            <label for="editName">Name:</label>
            <input type="text" id="editName" name="name" required>
            
            <label for="editEmail">Email:</label>
            <input type="email" id="editEmail" name="email" required>
            
            <label for="editRole">Role:</label>
            <select id="editRole" name="role" required>
                <option value="staff">Staff</option>
                <option value="administrator">Administrator</option>
                <option value="super_admin">Super Admin</option>
            </select>
            
            
            
            <button type="submit" class="btn">Save Changes</button>
        </form>
    </div>
</div>

<!--/Modal-->

        <div class="recent-grid1">
            <div class="projects">
                <div class="card">
                    <div class="card-header">
                    <h3 id="userListTitle">Users List</h3>
                    <div class="search-wrapper">
                        <span class="las la-search"></span>
                        <input type="search" id="userSearchInput" placeholder="Search here" oninput="filterTable()" />
                    </div>
                        <button onclick="openCabinetPanel()" class="open-panel-btn">Add User <span class="las la-plus"></span></button>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table width="100%">
                                <thead>
                                    <tr>
                                        <td>ID</td>
                                        <td>Name</td>
                                        <td>Email</td>
                                        <td>Role</td>
                                        <td>Status</td>
                                        <td>Actions</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    include "assets/databases/dbconfig.php";
                                    $stmt = $connection->prepare("SELECT id, name, email, status, role FROM users LIMIT ?");
$limit = 5;
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();

                                    if ($result->num_rows > 0) {
                                        while ($user = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                                            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                                            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
                                            echo "<td>" . htmlspecialchars($user['status']) . "</td>";
                                            echo "<td>
                                                        <button class='btn-edit las la-edit' 
                                                            onclick=\"openEditModal(
                                                                '" . htmlspecialchars($user['id']) . "',
                                                                '" . htmlspecialchars(addslashes($user['name'])) . "',
                                                                '" . htmlspecialchars(addslashes($user['email'])) . "',
                                                                '" . htmlspecialchars($user['role']) . "',
                                                                '" . htmlspecialchars($user['status']) . "'
                                                            )\">
                                                        </button>
                                                    </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3'>No users found</td></tr>";
                                    }
                                    $connection->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-header">
                        <button onclick="openSeeAllModal()">See all <span class="las la-arrow-right"></span></button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

            <script src="assets/js/add_user.js"></script>
            <script src="assets/js/user_fetch.js"></script>
            <script>
            function openSeeAllModal() {
                document.getElementById("seeAllUsersModal").style.display = "block";
            }

            function closeSeeAllModal() {
                document.getElementById("seeAllUsersModal").style.display = "none";
            }
            </script>
            <script>
                function openEditModal(id, name, email, role, status) {
                    // Populate the modal fields with the user's current data
                    document.getElementById('editId').value = id;
                    document.getElementById('editName').value = name;
                    document.getElementById('editEmail').value = email;
                    document.getElementById('editRole').value = role;
                    

                    // Open the modal
                    document.getElementById('editModal').style.display = 'block';
                }

                // Function to close the modal
                function closeEditModal() {
                    document.getElementById('editModal').style.display = 'none';
                }

            </script>
            <script>
                document.getElementById('editUserForm').addEventListener('submit', function (e) {
                    e.preventDefault();

                    const formData = new FormData(this);

                    fetch('edit_users.php', {
                        method: 'POST',
                        body: formData,
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            if (data.success) {
                                alert(data.message);
                                closeEditModal();
                                location.reload(); // Refresh the page to reflect changes
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch((error) => {
                            console.error('Error:', error);
                        });
                });

            </script>
            <script>
            function filterTable() {
    const input = document.getElementById("userSearchInput");
    const filter = input.value.toLowerCase(); // Convert search term to lowercase
    const table = document.querySelector(".card-body table");
    const rows = table.querySelectorAll("tbody tr");

    // Loop through all table rows
    rows.forEach(row => {
    const cells = row.querySelectorAll("td");
    let matchFound = false;

    cells.forEach(cell => {
        if (cell.textContent.toLowerCase().includes(filter)) {
            matchFound = true;
        }
    });

    row.style.display = matchFound ? "" : "none"; // Show or hide row
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
function fillEmployeeDetails() {
    var select = document.getElementById("name"); // Ensure this ID matches your dropdown
    var selectedOption = select.options[select.selectedIndex];

    if (selectedOption.value) {
        document.getElementById("email").value = selectedOption.getAttribute("data-email") || "";
        document.getElementById("phone").value = selectedOption.getAttribute("data-phone") || "";
    } else {
        document.getElementById("email").value = "";
        document.getElementById("phone").value = "";
    }
}
</script>

<script>
    function openCabinetPanel() {
    document.getElementById("addUserPanel").classList.add("open");
}

function closeCabinetPanel() {
    document.getElementById("addUserPanel").classList.remove("open");
}

// Auto-fill Employee Details
function fillEmployeeDetails() {
    var selectedEmployee = document.getElementById("name");
    var email = selectedEmployee.options[selectedEmployee.selectedIndex].getAttribute("data-email");
    var phone = selectedEmployee.options[selectedEmployee.selectedIndex].getAttribute("data-phone");

    document.getElementById("email").value = email || "";
    document.getElementById("phone").value = phone || "";
}

</script>
</body>
</html>
