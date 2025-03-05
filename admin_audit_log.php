<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/audit_log.css">
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
                Audit Log
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
    <div class="recent-grid1">
            <div class="projects">
                <div class="card">
                    <div class="card-header">
                        <h3>Audit Logs</h3>
                        <!-- Date Range Filter -->
                        <form method="GET" action="admin_audit_log.php" class="filter-form">
                            <input type="date" name="start_date" placeholder="Start Date">
                            <input type="date" name="end_date" placeholder="End Date">
                            <button type="submit" class="filter-btn">Filter</button>
                        </form>
                        <!-- Export Buttons -->
                        <div class="export-buttons">
                            <button onclick="exportCSV()">Export CSV</button>
                            <button onclick="exportExcel()">Export Excel</button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table width="100%">
                                <thead>
                                    <tr>
                                        <td>Date</td>
                                        <td>User</td>
                                        <td>Action</td>
                                        <td>Record Type</td>
                                        <td>Record ID</td>
                                        <td>Old Data</td>
                                        <td>New Data</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    include "assets/databases/dbconfig.php";

                                    // Date range filter logic
                                    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
                                    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
                                    $query = "SELECT * FROM audit_logs WHERE 1=1";
                                    if ($start_date) {
                                        $query .= " AND created_at >= '$start_date'";
                                    }
                                    if ($end_date) {
                                        $query .= " AND created_at <= '$end_date'";
                                    }
                                    $result = $connection->query($query);
                                    if ($result->num_rows > 0) {
                                        while ($user = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
                                            echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
                                            echo "<td>" . htmlspecialchars($user['action']) . "</td>";
                                            echo "<td>" . htmlspecialchars($user['record_type']) . "</td>";
                                            echo "<td>" . htmlspecialchars($user['record_id']) . "</td>";
                                            echo "<td>" . htmlspecialchars($user['old_data']) . "</td>";
                                            echo "<td>" . htmlspecialchars($user['new_data']) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7'>No Log found</td></tr>";
                                    }
                                    $connection->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    function exportCSV() {
        // Logic to export the table data to CSV
        let csvContent = "Date,User,Action,Record Type,Record ID,Old Data,New Data\n";
        let rows = document.querySelectorAll("table tbody tr");
        rows.forEach(function(row) {
            let cells = row.querySelectorAll("td");
            let rowContent = "";
            cells.forEach(function(cell) {
                rowContent += cell.innerText + ",";
            });
            csvContent += rowContent.slice(0, -1) + "\n"; // Remove the last comma
        });
        let downloadLink = document.createElement("a");
        downloadLink.href = "data:text/csv;charset=utf-8," + encodeURIComponent(csvContent);
        downloadLink.download = "audit_logs.csv";
        downloadLink.click();
    }

    function exportExcel() {
        // Logic to export the table data to Excel
        let table = document.querySelector("table");
        let wb = XLSX.utils.table_to_book(table, { sheet: "Audit Logs" });
        XLSX.writeFile(wb, "audit_logs.xlsx");
    }
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.2/xlsx.full.min.js"></script>

</body>
</html>
