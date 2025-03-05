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
$user_role = $_SESSION['role'];  // User role from session

?>

<input type="checkbox" id="nav-toggle">
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="img/logo1.png" alt="">
    </div>

    <div class="sidebar-menu">
        <ul>
        <li>
                <a href="staff_dashboard.php" class="active"><span class="las la-tachometer-alt"></span>
                <span>Dashboard</span></a>
            </li>
            <li>
                <a href="staff_add_product_services.php"><span class="las la-truck"></span>
                <span>Product & Services</span></a>
            </li>
            <li>
            <a href="staff_financial.php"><span class="las la-balance-scale"></span>
            <span>Financial Request</span></a>
            </li>
            <li>
                <a href="staff_payroll.php"><span class="las la-users"></span>
                <span>Staffing & Payroll</span></a>
            </li>
            <li>
                <a href="staff_audit_log.php"><span class="las la-file-invoice"></span>
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
                Account Receivable Aging
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

</body>
</html>