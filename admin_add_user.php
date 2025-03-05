<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/add_user.css">
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
</head>
<body>
<?php
include "user_fetch.php";
include "fetch_users.php";
session_start();  // Start the session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect to login if not logged in
    exit();
}

$user_name = $_SESSION['name'];  // User name from session
$user_role = $_SESSION['role'];  // User role from session

// Retrieve error message if any
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
// Clear error message after displaying
unset($_SESSION['error_message']);


?>

<input type="checkbox" id="nav-toggle">
<div class="sidebar">
    <div class="sidebar-brand">
        <h2><span class="lab la-accusoft"> <span>Finance</span></span></h2>
    </div>

    <div class="sidebar-menu">
        <ul>
        <li>
                <a href="administrator_dashboard.php" class="active"><span class="las la-tachometer-alt"></span>
                <span>Dashboard</span></a>
            </li>
            <li>
                <a href="add_product_services.php"><span class="las la-truck"></span>
                <span>Product & Services</span></a>
            </li>
            <li>
            <a href="financial.php"><span class="las la-balance-scale"></span>
            <span>Financial Request</span></a>
            <li>
                <a href="analytics.php"><span class="las la-chart-bar"></span>
                <span>Analytics</span></a>
            </li>
            <li>
            <a href="admin_add_user.php"><span class="las la-users"></span>
                <span>User Management</span></a>
            </li>
            <li>
                <a href="admin_audit_log.php"><span class="las la-file-invoice"></span>
                <span>Audit Logs</span></a>
            </li>
            <li>
                <a href="logout.php"><span class="las la-sign-out-alt"></span>
                <span>Logout</span></a>
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
        <span class="las la-bell" width="40px" height="40px"></span>
            <div>
                <h4><?php echo htmlspecialchars($user_name); ?></h4>
                <small><?php echo htmlspecialchars($user_role); ?></small>
            </div>
        </div>
    </header>

    <main>
    <div class="cards">
            <div class="card-single" onclick="updateUserListTitle('Staff'); filterUsers('staff')" style="cursor: pointer;">
                <div>
                    <h3>Staff</h3>
                    <h1><?php echo $role_counts['staff']; ?></h1>
                    <span>Total</span>
                </div>
                <div>
                    <span class="las la-user"></span>
                </div>
            </div>
        <div class="card-single" onclick="updateUserListTitle('Administrator'); filterUsers('administrator')" style="cursor: pointer;">
            <div>
                <h3>Administrator</h3>
                <h1><?php echo $role_counts['administrator']; ?></h1>
                <span>Total</span>
            </div>
            <div>
                <span class="las la-user"></span>
            </div>
        </div>
        <div class="card-single" onclick="updateUserListTitle('Super Admin'); filterUsers('super_admin')" style="cursor: pointer;">
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
    <div id="addUserModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Add New User</h3>
       
        <div class="error-message" style="display: none; color: red;"></div>
  
        <form action="add_users.php" method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" placeholder="Enter Full Name" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter Email" required>
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
                                <tbody>
                                    <?php
                                    include "assets/databases/dbconfig.php";
                                    $query = "SELECT id, name, email, status, role FROM users LIMIT 5";
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
                                        echo "<tr><td colspan='3'>No users found</td></tr>";
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
                        <button onclick="openModal()" class="btn">Add User <span class="las la-plus"></span></button>
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
                                    $query = "SELECT id, name, email, status, role FROM users LIMIT 5";
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
            function updateUserListTitle(role) {
                document.getElementById('userListTitle').textContent = role + " List";
            }
            </script>
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

        // Check if any cell in the row contains the search term
        cells.forEach(cell => {
            if (cell.textContent.toLowerCase().includes(filter)) {
                matchFound = true;
            }
        });

        // Show or hide the row based on the search match
        if (matchFound) {
            row.style.display = ""; // Show row
        } else {
            row.style.display = "none"; // Hide row
        }
    });
}

            </script>
</body>
</html>
