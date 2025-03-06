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

include('assets/databases/dbconfig.php');

$sql = "SELECT 
id,
    customer_name, 
    SUM(IFNULL(current_amount, 0)) AS current_amount,
    SUM(IFNULL(past_due_30, 0)) AS past_due_30,
    SUM(IFNULL(past_due_60, 0)) AS past_due_60,
    SUM(IFNULL(past_due_90, 0)) AS past_due_90,
    SUM(IFNULL(past_due_90plus, 0)) AS past_due_90plus,
    SUM(IFNULL(total_due, 0)) AS total_due
FROM receivables
GROUP BY customer_name  -- ✅ Group by customer_id
ORDER BY total_due DESC"; // Order by highest due amount
 // Fix ORDER BY

$result = $connection->query($sql);

$receivablesData = [];
$totalCurrent = $totalPast30 = $totalPast60 = $totalPast90 = $totalPast90plus = $totalDue = 0;

// Fetch data only once
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $receivablesData[] = $row;
        $totalCurrent += $row['current_amount'];
        $totalPast30 += $row['past_due_30'];
        $totalPast60 += $row['past_due_60'];
        $totalPast90 += $row['past_due_90'];
        $totalPast90plus += $row['past_due_90plus'];
        $totalDue += $row['total_due'];
    }
}

$sqlUnpaid = "SELECT 
                invoices.id AS invoice_id, 
                invoices.customer_id,
                invoices.product_name, 
                invoices.amount, 
                invoices.due_date, 
                invoices.payment_status, 
                customers.name AS customer_name
            FROM invoices
            JOIN customers ON invoices.customer_id = customers.id
            WHERE invoices.payment_status = 'pending' OR invoices.payment_status = 'overdue'
            ORDER BY invoices.due_date ASC";

$resultUnpaid = $connection->query($sqlUnpaid);

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
                Accounts Receivable
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



        <div id="transactionHistoryPanel" class="transaction-panel">
    <div class="panel-header">
        <h3>Transaction History</h3>
        <div>
            <button onclick="downloadTransactionHistory()">📥 Download</button>
            <button id="maximizeBtn" onclick="toggleMaximize()">🗖</button> 
            <button onclick="closePanel()">✖</button>
        </div>
    </div>
    <div id="transactionContent">
        <p>Select an invoice to view transaction history.</p>
    </div>
</div>

<style>
.transaction-panel {
    position: fixed;
    top: 0;
    right: -600px; /* Initially hidden */
    width: 600px; /* Default width */
    height: 100%;
    background: white;
    box-shadow: -2px 0 5px rgba(0,0,0,0.2);
    transition: right 0.3s ease-in-out, width 0.3s ease-in-out;
    padding: 20px;
    overflow-y: auto;
    z-index: 10000;
}
.transaction-panel.open {
    right: 0;
}

/* Maximized Mode */
.transaction-panel.maximized {
    width: 100vw; /* Full-screen width */
}

/* Panel Header */
.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

/* Button Styling */
.panel-header button {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    margin-left: 8px;
    padding: 5px 10px;
    border-radius: 5px;
}

.panel-header button:hover {
    background-color: #f0f0f0;
}
</style>

<script>
function toggleMaximize() {
    let panel = document.getElementById("transactionHistoryPanel");
    let maximizeBtn = document.getElementById("maximizeBtn");

    panel.classList.toggle("maximized");

    // Change button icon based on state
    if (panel.classList.contains("maximized")) {
        maximizeBtn.innerHTML = "🗕"; // Restore icon
    } else {
        maximizeBtn.innerHTML = "🗖"; // Maximize icon
    }
}

function closePanel() {
    let panel = document.getElementById("transactionHistoryPanel");
    panel.classList.remove("open", "maximized");
    document.getElementById("maximizeBtn").innerHTML = "🗖"; // Reset button icon
}

// Function to download table as CSV
function downloadTransactionHistory() {
    let table = document.querySelector("#transactionContent table");
    if (!table) {
        alert("No transaction history to download.");
        return;
    }

    let rows = Array.from(table.querySelectorAll("tr"));
    let csvContent = rows.map(row => {
        let columns = Array.from(row.querySelectorAll("td, th")).map(cell => `"${cell.innerText}"`);
        return columns.join(",");
    }).join("\n");

    let blob = new Blob([csvContent], { type: "text/csv" });
    let link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "transaction_history.csv";
    link.click();
}
</script>



        <main>
        <div class="container">
        

<!-- KPI Cards -->
<div class="kpi-cards">
    <div class="card">
        <h4>Total Receivables</h4>
        <h5 id="totalReceivables">₱0</h5>
    </div>
    <div class="card">
        <h4>Past Due %</h4>
        <h5 id="pastDuePercent">0%</h5>
    </div>
    <div class="card">
        <h4>Over 90 Days</h4>
        <h5 id="over90">₱0</h5>
    </div>
</div>

<!-- Chart Section -->
<div class="charts-container">
    <!-- Horizontal Bar Chart (Top 10 Unpaid Customers) -->
    <div class="chart-box">
        <h4>Top 10 Customers with Unpaid Invoices</h4>
        <canvas id="topCustomersChart"></canvas>
    </div>
    
    <!-- Pie Chart (Unpaid Customer Percentage) -->
    <div class="chart-box pie-chart">
    <!-- Pie Chart -->
    <div class="pie-chart-container">
        <canvas id="unpaidPieChart"></canvas>
    </div>

    <!-- Legend (Labels) -->
    <div class="pie-chart-legend">
        <ul id="pieChartLegend"></ul>
    </div>
</div>

</div>


<div class="tables-wrapper">
    <!-- Aging Report Table -->
    <div class="table-container">
        <h3>Aging Report</h3>
        <button id="updateAging">Update Aging</button>
        <table id="agingTable">
    <thead>
        <tr>
        <th style="display: none;">ID</th>
            <th>Customer</th>
            <th>Current</th>
            <th>1-30</th>
            <th>31-60</th>
            <th>61-90</th>
            <th>90+</th>
            <th>Total Due</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($receivablesData)) : ?>
            <?php foreach ($receivablesData as $row) : ?>
                <tr data-customer-id="<?= htmlspecialchars($row['customer_name']) ?>">
                <td style="display: none;"><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td><?= number_format($row['current_amount'], 2) ?></td>
                    <td><?= number_format($row['past_due_30'], 2) ?></td>
                    <td><?= number_format($row['past_due_60'], 2) ?></td>
                    <td><?= number_format($row['past_due_90'], 2) ?></td>
                    <td><?= number_format($row['past_due_90plus'], 2) ?></td>
                    <td><?= number_format($row['total_due'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr><td colspan='7' style='text-align: center;'>No records found</td></tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td style="font-weight: bold; text-align: right;">Total:</td>
            <td><?= number_format($totalCurrent, 2) ?></td>
            <td><?= number_format($totalPast30, 2) ?></td>
            <td><?= number_format($totalPast60, 2) ?></td>
            <td><?= number_format($totalPast90, 2) ?></td>
            <td><?= number_format($totalPast90plus, 2) ?></td>
            <td><?= number_format($totalDue, 2) ?></td>
        </tr>
    </tfoot>
</table>

    </div>

    <!-- Unpaid Invoices Table -->
    <div class="table-container small-table">
        <h3>Unpaid Invoices</h3>
        <table id="unpaidInvoicesTable">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Invoice No.</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
    <?php if ($resultUnpaid->num_rows > 0) : ?>
        <?php while ($row = $resultUnpaid->fetch_assoc()) : ?>
            <tr class="invoice-row" data-invoice-id="<?= $row['customer_id'] ?>" 
    style="cursor:pointer;" 
    onmouseover="this.style.backgroundColor='#f1b0b7';" 
    onmouseout="this.style.backgroundColor='';">
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><?= htmlspecialchars($row['invoice_id']) ?></td>
                <td><?= date("M d, Y", strtotime($row['due_date'])) ?></td>
                <td><?= number_format($row['amount'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else : ?>
        <tr><td colspan="4" style="text-align: center;">No unpaid invoices</td></tr>
    <?php endif; ?>
</tbody>
        </table>
    </div>
</div>
<br>
<div class="chart-box1">
    <h4>Paid vs Unpaid Invoices</h4>
<canvas id="paidUnpaidChart"></canvas>
</div>
</div>

    </main>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>


<script>
const ctx = document.getElementById("paidUnpaidChart").getContext("2d");

// Sample Data
const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
const paidInvoices = [5000, 7000, 6500, 8000, 9000, 7500, 6200, 7000, 7200, 8100, 8300, 8800];
const unpaidInvoices = [2000, 1500, 3000, 2500, 2200, 2700, 3100, 2900, 2800, 2400, 2600, 2300];

const paidUnpaidChart = new Chart(ctx, {
    type: "bar",
    data: {
        labels: months, // Months on X-axis
        datasets: [
            {
                label: "Paid",
                data: paidInvoices,
                backgroundColor: "#4CAF50",
            },
            {
                label: "Unpaid",
                data: unpaidInvoices,
                backgroundColor: "#FF5733",
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                beginAtZero: true
            },
            y: {
                beginAtZero: true
            }
        }
    }
});

</script>

<script>
// Horizontal Bar Chart (Top 10 Unpaid Customers)
let ctx1 = document.getElementById("topCustomersChart").getContext("2d");
let topCustomersChart = new Chart(ctx1, {
    type: "bar",
    data: {
        labels: ["Customer A", "Customer B", "Customer C", "Customer D", "Customer E", "Customer F", "Customer G", "Customer H", "Customer I", "Customer J"],
        datasets: [{
            label: "Unpaid Amount ($)",
            data: [10000, 9200, 8500, 7800, 7300, 6900, 6200, 5800, 5500, 5000],
            backgroundColor: "#ed6978"
        }]
    },
    options: {
        indexAxis: "y",
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: { beginAtZero: true }
        }
    }
});

const pieChartData = {
    labels: ["Customer A", "Customer B", "Customer C", "Customer D"],
    datasets: [{
        data: [30, 25, 20, 25], // Example unpaid percentages
        backgroundColor: ["#FF6384", "#36A2EB", "#FFCE56", "#4BC0C0"]
    }]
};

const unpaidPieChart = new Chart(document.getElementById("unpaidPieChart"), {
    type: "doughnut",
    data: pieChartData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "35%", // Set cutout size
        plugins: {
            legend: {
                display: false // Hide default legend
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        let value = tooltipItem.raw;
                        let total = pieChartData.datasets[0].data.reduce((a, b) => a + b, 0);
                        let percentage = ((value / total) * 100).toFixed(1) + "%";
                        return `${pieChartData.labels[tooltipItem.dataIndex]}: ${percentage}`;
                    }
                }
            },
            datalabels: {
                color: "#fff",
                font: { weight: "bold" },
                formatter: (value, ctx) => {
                    let total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                    let percentage = ((value / total) * 100).toFixed(1);
                    return percentage + "%";
                }
            }
        }
    },
    plugins: [ChartDataLabels] // Enable DataLabels plugin
});

// Custom Legend
const legendContainer = document.getElementById("pieChartLegend");
legendContainer.innerHTML = ""; // Clear existing legend before adding new ones
pieChartData.labels.forEach((label, index) => {
    const legendItem = document.createElement("li");
    legendItem.innerHTML = `<span style="background-color: ${pieChartData.datasets[0].backgroundColor[index]}; width: 12px; height: 12px; display: inline-block; margin-right: 5px;"></span> ${label}`;
    legendContainer.appendChild(legendItem);
});

</script>

<style>
.container {
    max-width: auto;
    margin: auto;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.kpi-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr); /* 3 cards per row */
    gap: 15px;
    margin-top: 20px;
}

.card {
    background: #ed6978;
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.tables-wrapper {
    display: flex;
    gap: 15px;
    justify-content: space-between;
    margin-top: 20px;
}

.table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
    padding: 10px;
    overflow-x: auto;
    flex: 1;
}

.small-table {
    max-width: 48%; /* Adjusts width to fit side-by-side */
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px; /* Reduce font size */
}

th, td {
    border: 1px solid #ddd;
    padding: 6px; /* Reduce padding */
    text-align: center;
}

th {
    background: #ed6978;
    color: white;
    font-size: 12px;
}

h3 {
    font-size: 14px;
    text-align: center;
}


.filters {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}

.charts-container {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin: 20px 0;
}
.chart-box1 {
    width: 100%;
    max-width: auto; /* Adjust as needed */
    height: 300px; /* Reduce height */
    margin: auto;
}


.chart-box {
    width: 50%; /* Reduced width */
    max-width: auto;
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.chart-box canvas {
    height: 250px !important;
    max-height: 250px;
    width: 100%;
}

.chart-box.pie-chart {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px;
}

.pie-chart-container {
    flex: 1;
    max-width: 60%;
}

.pie-chart-legend {
    flex: 1;
    max-width: 40%;
    text-align: left;
}

.pie-chart-legend ul {
    list-style: none;
    padding: 0;
}

.pie-chart-legend li {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    font-size: 14px;
}

.pie-chart-legend span {
    width: 12px;
    height: 12px;
    display: inline-block;
    margin-right: 8px;
    border-radius: 50%;
}

/* Stack charts on smaller screens */
@media (max-width: 768px) {
    .charts-container {
        flex-direction: column;
        align-items: center;
    }
    .chart-box {
        width: 100%;
    }
}



/* Responsive Design */
@media (max-width: 768px) {
    .kpi-cards {
        grid-template-columns: repeat(2, 1fr); /* 2 cards per row on tablets */
    }
}

@media (max-width: 480px) {
    .kpi-cards {
        grid-template-columns: 1fr; /* 1 card per row on mobile */
    }
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
    document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".invoice-row").forEach(row => {
        row.addEventListener("click", function() {
            let invoiceId = this.getAttribute("data-invoice-id");
            fetchTransactionHistory(invoiceId);
        });
    });
});

function fetchTransactionHistory(customerId) {
    fetch('get_customer_transactions.php?customer_id=' + customerId)
    .then(response => response.text())
    .then(data => {
        document.getElementById("transactionContent").innerHTML = data;
        document.getElementById("transactionHistoryPanel").classList.add("open");
    });
}


function closePanel() {
    document.getElementById("transactionHistoryPanel").classList.remove("open");
}

</script>
<script>
document.getElementById('updateAging').addEventListener('click', function() {
    fetch('update_aging.php')
        .then(response => response.text())
        .then(data => {
            alert('Aging report updated!');
            location.reload(); // Reload page to reflect new data
        });
});
</script>

</body>
</html>