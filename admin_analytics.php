<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/chart.css">
    <link rel= "stylesheet" href= "https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css" >
</head>
<body>

<?php
include "assets_liabilities.php";
include "fetch_income_expense.php";
include "assets/databases/dbconfig.php";
session_start();  // Start the session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect to login if not logged in
    exit();
}

$user_name = $_SESSION['name'];  // User name from session
$user_role = $_SESSION['role'];  // User role from session


// Fetch historical transaction data (last 6 months)
$sql_income_history = "SELECT transaction_date, SUM(amount) AS total_income 
                       FROM transactions 
                       GROUP BY transaction_date 
                       ORDER BY transaction_date DESC";

$result_income_history = $connection->query($sql_income_history);
$income_history = [];
while ($row = $result_income_history->fetch_assoc()) {
    $income_history[] = ['date' => $row['transaction_date'], 'income' => $row['total_income']];
}

// Fetch income vs expenses data
$sql_income_expenses = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m-%d') AS transaction_date, 
    SUM(amount) AS total_income,
    (SELECT SUM(amount) FROM expense WHERE DATE_FORMAT(expense_date, '%Y-%m-%d') = DATE_FORMAT(transactions.transaction_date, '%Y-%m-%d')) AS total_expenses
FROM transactions
GROUP BY transaction_date
ORDER BY transaction_date DESC";

$result_income_expenses = $connection->query($sql_income_expenses);
$income_expenses_data = [];
while ($row = $result_income_expenses->fetch_assoc()) {
    $income_expenses_data[] = [
        'transaction_date' => $row['transaction_date'],
        'total_income' => $row['total_income'],
        'total_expenses' => $row['total_expenses'] ?? 0 // Ensure it defaults to 0 if null
    ];
}


$sql_income = "SELECT SUM(amount) AS total_income FROM transactions WHERE transaction_date BETWEEN CURDATE() - INTERVAL 1 MONTH AND CURDATE()";
$result_income = $connection->query($sql_income);
$row_income = $result_income->fetch_assoc();
$total_income = $row_income['total_income'];

// Fetch total expenses for the last month
$sql_expenses = "SELECT SUM(amount) AS total_expenses FROM expense WHERE expense_date BETWEEN CURDATE() - INTERVAL 1 MONTH AND CURDATE()";
$result_expenses = $connection->query($sql_expenses);
$row_expenses = $result_expenses->fetch_assoc();
$total_expenses = $row_expenses['total_expenses'];

// Fetch the tax rates from the 'taxes' table where applicable to income
$sql_taxes = "SELECT id, name, rate, type FROM taxes WHERE is_applicable_income = 1";
$result_taxes = $connection->query($sql_taxes);
$taxes = [];
while ($row_tax = $result_taxes->fetch_assoc()) {
    $taxes[] = $row_tax;
}

// Calculate total taxes for the income based on the tax rates
$total_tax_amount = 0;
foreach ($taxes as $tax) {
    $tax_rate = $tax['rate'];
    if ($tax['type'] == 'percentage') {
        $tax_amount = ($total_income * $tax_rate) / 100;
    } elseif ($tax['type'] == 'fixed') {
        $tax_amount = $tax_rate;
    }
    $total_tax_amount += $tax_amount;
    // Optional: Store the tax amount in the transaction (if needed)
    // For each income transaction, calculate and update tax amounts
    $sql_update_tax = "UPDATE transactions SET tax_id = {$tax['id']}, tax_rate = {$tax_rate}, tax_amount = {$tax_amount}, total_amount = amount + {$tax_amount} WHERE transaction_date BETWEEN CURDATE() - INTERVAL 1 MONTH AND CURDATE()";
    $connection->query($sql_update_tax);
}

// Fetch net income after tax for the last month
$net_income_after_tax = $total_income - $total_tax_amount;


// Fetch total assets
$sql_assets = "SELECT SUM(value) AS total_assets FROM assets";
$result_assets = $connection->query($sql_assets);
$row_assets = $result_assets->fetch_assoc();
$total_assets = $row_assets['total_assets'];

// Fetch total liabilities
$sql_liabilities = "SELECT SUM(amount) AS total_liabilities FROM liabilities";
$result_liabilities = $connection->query($sql_liabilities);
$row_liabilities = $result_liabilities->fetch_assoc();
$total_liabilities = $row_liabilities['total_liabilities'];


// Fetch asset distribution by type
$sql_asset_distribution = "SELECT type, SUM(value) AS total_value FROM assets GROUP BY type";
$asset_distribution = $connection->query($sql_asset_distribution);
$asset_distribution_data = [];
while ($row = $asset_distribution->fetch_assoc()) {
    $asset_distribution_data[] = $row;
}


$sql_liabilities_breakdown = "SELECT liability_name, amount, due_date FROM liabilities";
$result_liabilities_breakdown = $connection->query($sql_liabilities_breakdown);
$liabilities_data = [];
while ($row = $result_liabilities_breakdown->fetch_assoc()) {
    $liabilities_data[] = $row;
}

// Fetch income vs expenses data
$sql_income_expenses = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m-%d') AS transaction_date, 
    SUM(amount) AS total_income,
    (SELECT SUM(amount) FROM expense WHERE DATE_FORMAT(expense_date, '%Y-%m-%d') = DATE_FORMAT(transactions.transaction_date, '%Y-%m-%d')) AS total_expenses
FROM transactions
GROUP BY transaction_date
ORDER BY transaction_date DESC";

$result_income_expenses = $connection->query($sql_income_expenses);
$income_expenses_data = [];
while ($row = $result_income_expenses->fetch_assoc()) {
    $income_expenses_data[] = [
        'transaction_date' => $row['transaction_date'],
        'total_income' => $row['total_income'],
        'total_expenses' => $row['total_expenses'] ?? 0 // Ensure it defaults to 0 if null
    ];
}

$sql_cogs = "SELECT SUM(total_cogs) AS total_cogs FROM cogs";
$result_cogs = $connection->query($sql_cogs);
$row_cogs = $result_cogs->fetch_assoc();
$cost_of_goods_sold = $row_cogs['total_cogs'];

// Calculate gross profit margin
if ($total_income != 0) {
    $gross_profit_margin = ($total_income - $cost_of_goods_sold) / $total_income;
} else {
    $gross_profit_margin = 0; // Prevent division by zero
}

$net_worth = $total_assets - $total_liabilities;
$debt_to_equity = ($total_liabilities / $total_assets);

// Calculate net profit margin
if ($total_income != 0) {
    $net_profit_margin = ($total_income - $total_expenses) / $total_income;
} else {
    $net_profit_margin = 0; // Prevent division by zero
}

$sql_net_worth_history = "SELECT 
                            DATE_FORMAT(transaction_date, '%Y-%m') as period,
                            SUM(amount) AS total_income,
                            (SELECT SUM(amount) FROM expense WHERE DATE_FORMAT(expense_date, '%Y-%m') = DATE_FORMAT(transactions.transaction_date, '%Y-%m')) AS total_expenses,
                            (SELECT SUM(value) FROM assets WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(transactions.transaction_date, '%Y-%m')) AS total_assets,
                            (SELECT SUM(amount) FROM liabilities WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(transactions.transaction_date, '%Y-%m')) AS total_liabilities
                        FROM transactions
                        GROUP BY period 
                        ORDER BY period DESC";

$result_net_worth_history = $connection->query($sql_net_worth_history);
$net_worth_history = [];

while ($row = $result_net_worth_history->fetch_assoc()) {
    $net_worth = $row['total_assets'] - $row['total_liabilities'];
    $net_worth_history[] = [
        'date' => $row['period'],
        'net_worth' => $net_worth
    ];
}

?>

<?php
// Fetch the monthly income and tax data from your database
// This assumes you have a table to store monthly incomes or you can calculate it from income records

$sql_income = "SELECT MONTH(transaction_date) AS month, SUM(amount) AS total_income 
               FROM transactions
               GROUP BY MONTH(transaction_date)
               ORDER BY MONTH(transaction_date)";

$result_income = $connection->query($sql_income);

// Prepare data arrays
$months = [];
$income_data = [];
$tax_data = [];

// Tax rate (you can fetch this from your database or hardcode it for simplicity)
$tax_rate = 0.10; // Example: 10% tax deduction on income

while ($row = $result_income->fetch_assoc()) {
    // Store the month, income, and tax deduction data
    $months[] = $row['month']; // Month (1-12)
    $income_data[] = $row['total_income']; // Total income for the month

    // Calculate the tax deduction (tax_rate * income)
    $tax_deduction = $row['total_income'] * $tax_rate;
    $tax_data[] = $tax_deduction; // Tax deducted for the month
}

// Convert PHP arrays to JSON for JavaScript
$months_json = json_encode($months);
$income_json = json_encode($income_data);
$tax_json = json_encode($tax_data);
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
                <a href="admin_analytics.php"><span class="las la-chart-bar"></span>
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
                Analytics
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

        <div class="cards" style="cursor: pointer">
                <div class="card-single">
                    <div>
                    <h3>Expense</h3>
                        <h1><?php echo "₱" , number_format($totalExpense, 2); ?></h1>
                        <span>Total Expenses</span>
                    </div>
                    <div>
                        <span class="las la-receipt"></span>
                    </div>
                </div>

                <div class="card-single">
                    <div>
                        <h3>Revenue</h3>
                        <h1><?php echo "₱" , number_format($total_income, 2); ?></h1>
                        <span>Total Revenue</span>
                    </div>
                    <div>
                        <span class="lab la-google-wallet"></span>
                    </div>
                </div>

<div class="card-single">
    <div>
        <h3>Net Income</h3>
        <h1><?php echo "₱" , number_format($net_income_after_tax, 2); ?></h1>
        <span>Total Income</span>
    </div>
    <div>
        <span class="lab la-google-wallet"></span>
    </div>
</div>


                <div class="card-single">
                    <div>
                    <h3>Assets</h3>
                        <h1><?php echo "₱" , number_format($totalAssets, 2); ?></h1>
                        <span>Total Assets</span>
                    </div>
                    <div>
                        <span class="las la-coins"></span>
                    </div>
                </div>

                <div class="card-single">
                    <div>
                    <h3>Liabilities</h3>
                        <h1><?php echo "₱" , number_format($totalLiabilities, 2); ?></h1>
                        <span>Total Liabilities</span>
                    </div>
                    <div>
                        <span class="las la-credit-card"></span>
                    </div>
                </div>

                <div class="card-single">
                    <div>
                        <h3>Net Worth</h3>
                        <h1><?php echo "₱" . number_format($net_worth, 2); ?></h1>
                        <span>Total financial worth</span>
                    </div>
                    <div>
                        <span class="las la-wallet"></span>
                    </div>
                </div>

                <div class="card-single">
                    <div>
                        <h3>Debt-to-Equity Ratio</h3>
                        <h1><?php echo number_format($debt_to_equity, 2); ?></h1>
                        <span>Financial leverage ratio</span>
                    </div>
                    <div>
                        <span class="las la-balance-scale"></span>
                    </div>
                </div>

                <div class="card-single">
                    <div>
                        <h3>Gross Profit Margin</h3>
                        <h1><?php echo number_format($gross_profit_margin * 100, 2) . "%"; ?></h1>
                        <span>Profitability metric</span>
                    </div>
                    <div>
                        <span class="las la-chart-pie"></span>
                    </div>
                </div>


            </div>

        <div class="recent-grid">



                <div class="projects">
                <div class="chart-container">
                <div class="time-period-selector">
    <label for="timePeriod">Select Time Period: </label>
    <select id="timePeriod">
        <option value="24h">Last 24 Hours</option>
        <option value="3d">Last 3 Days</option>
    </select>
</div>
                        <h3>Income vs Expenses</h3>
                        <canvas id="incomeVsExpensesChart"></canvas>
                    </div>

            <div class="chart-grid">
                        <div class="chart-container">
                        <h3>Net Worth</h3>
                            <canvas id="NetworthChart"></canvas>
                        </div>

                    <div class="chart-container">
                        <h3>Cash Flow</h3>
                        <canvas id="cashFlowChart"></canvas>
                    </div>
                </div>

                <div class="chart-grid1">
                <div class="chart-container">
                    <h3>Gross Profit</h3>
                    <canvas id="grossProfitChart"></canvas>
                </div>

                <!-- Liabilities Breakdown Chart -->
                <div class="chart-container">
                    <h3>Net Profit Margin</h3>
                    <canvas id="netProfitMarginChart"></canvas>
                </div>

                <div class="chart-container">
                    <h3>Tax Report</h3>
            <canvas id="taxDeductionChart" width="400" height="200"></canvas>
                </div>



    </div>                          

</div>
                

            <div class="customers">
                <div class="card">


                <div class="customer">
                <div class="card-header">
                <h3>Asset Distribution</h3>
                </div>
                    <div class="chart-containers">
                    <canvas id="assetDistributionChart"></canvas>
                    </div>
                </div>
                
                    
                <div class="card">
                    <div class="customer">
                    <div class="card-header">
                    <h3>Liabilities Breakdown</h3>
                    </div>
                        <div class="chart-containers">
                            <canvas id="liabilitiesChart"></canvas>
                        </div>
                    </div> 

                
            </div>

            
            


    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Prepare the data for Chart.js
    var months = <?php echo $months_json; ?>;
    var incomeData = <?php echo $income_json; ?>;
    var taxData = <?php echo $tax_json; ?>;

    // Create the chart
    var ctx = document.getElementById('taxDeductionChart').getContext('2d');
    var taxDeductionChart = new Chart(ctx, {
        type: 'bar', // You can change this to 'line' for a line chart
        data: {
            labels: months.map(function(month) {
                const date = new Date(0); // epoch time
                date.setMonth(month - 1); // Set the month (1-12)
                return date.toLocaleString('default', { month: 'short' }); // Month name (e.g., Jan, Feb, Mar)
            }),
            datasets: [{
                label: 'Monthly Income (₱)',
                data: incomeData,
                backgroundColor: '#30c0dd',
                borderColor: '#30c0dd',
                borderWidth: 1
            }, {
                label: 'Tax Deduction (₱)',
                data: taxData,
                backgroundColor: '#FF6384',
                borderColor: '#FF6384',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return '₱ ' + tooltipItem.raw.toFixed(2); // Format tooltips as ₱ currency
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱ ' + value.toFixed(2); // Format Y-axis as ₱ currency
                        }
                    }
                }
            }
        }
    });
</script>




<script>
// Asset Distribution Chart
const assetDistributionData = <?php echo json_encode($asset_distribution_data); ?>;
const assetDistributionChart = new Chart(document.getElementById('assetDistributionChart'), {
    type: 'pie',
    data: {
        labels: assetDistributionData.map(data => data.type),
        datasets: [{
            label: 'Asset Distribution',
            data: assetDistributionData.map(data => data.total_value),
            backgroundColor: ['#FF6384', '#30c0dd', '#FFCE56', '#4BC0C0'],
            borderColor: '#30c0dd',
            borderWidth: 1
        }]
    }
});

// Liabilities Breakdown Chart (Bar chart for due dates)
const liabilitiesData = <?php echo json_encode($liabilities_data); ?>;
const liabilitiesChart = new Chart(document.getElementById('liabilitiesChart'), {
    type: 'pie',
    data: {
        labels: liabilitiesData.map(data => data.liability_name), // Liability names as labels
        datasets: [{
            label: 'Liabilities Breakdown',
            data: liabilitiesData.map(data => data.amount), // Liabilities amounts as data
            backgroundColor: ['#30c0dd', '#33FF57', '#3357FF', '#F2C200'], // Custom colors for each slice
            borderColor: '#30c0dd',
            borderWidth: 1
        }]
    }
});
</script>
<script>
// Parse the PHP data into JavaScript
const incomeVsExpensesData = <?php echo json_encode($income_expenses_data); ?>;

// Function to filter data based on the selected period
function filterData(period) {
    let filteredData = [];
    let currentDate = new Date();

    // Filter data based on the period (Last 24 Hours or Last 3 Days)
    if (period === "24h") {
        filteredData = incomeVsExpensesData.filter(data => {
            let transactionDate = new Date(data.transaction_date);
            return (currentDate - transactionDate) <= 24 * 60 * 60 * 1000; // Last 24 hours
        });
    } else if (period === "3d") {
        filteredData = incomeVsExpensesData.filter(data => {
            let transactionDate = new Date(data.transaction_date);
            return (currentDate - transactionDate) <= 3 * 24 * 60 * 60 * 1000; // Last 3 days
        });
    }

    return filteredData;
}

// Function to update the charts with the filtered data
function updateCharts(period) {
    const filteredData = filterData(period);

    // Update the Income vs Expenses chart
    incomeVsExpensesChart.data.labels = filteredData.map(data => data.transaction_date);
    incomeVsExpensesChart.data.datasets[0].data = filteredData.map(data => data.total_income);
    incomeVsExpensesChart.data.datasets[1].data = filteredData.map(data => data.total_expenses || 0);
    incomeVsExpensesChart.update();

    // Update the Cash Flow chart
    const cashFlowData = filteredData.map(data => data.total_income - (data.total_expenses || 0));
    cashFlowChart.data.labels = filteredData.map(data => data.transaction_date);
    cashFlowChart.data.datasets[0].data = cashFlowData;
    cashFlowChart.update();
}

// Initial chart creation with full data
const incomeVsExpensesChart = new Chart(document.getElementById('incomeVsExpensesChart'), {
    type: 'bar',
    data: {
        labels: incomeVsExpensesData.map(data => data.transaction_date),
        datasets: [
            {
                label: 'Income',
                data: incomeVsExpensesData.map(data => data.total_income),
                backgroundColor: '#30c0dd',
            },
            {
                label: 'Expenses',
                data: incomeVsExpensesData.map(data => data.total_expenses || 0),
                backgroundColor: '#FF6384',
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
        }
    }
});

// Cash Flow Chart
const cashFlowChart = new Chart(document.getElementById('cashFlowChart'), {
    type: 'line',
    data: {
        labels: incomeVsExpensesData.map(data => data.transaction_date),
        datasets: [{
            label: 'Cash Flow',
            data: incomeVsExpensesData.map(data => data.total_income - (data.total_expenses || 0)),
            fill: false,
            borderColor: '#30c0dd',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
        }
    }
});

// Event listener for time period change
document.getElementById('timePeriod').addEventListener('change', function (event) {
    const selectedPeriod = event.target.value;
    updateCharts(selectedPeriod); // Update charts with the selected period
});
</script>


<script>
    // Net Worth History Chart
const netWorthData = <?php echo json_encode($net_worth_history); ?>;
const netWorthChart = new Chart(document.getElementById('NetworthChart'), {
    type: 'line',
    data: {
        labels: netWorthData.map(data => data.date),
        datasets: [{
            label: 'Net Worth',
            data: netWorthData.map(data => data.net_worth),
            backgroundColor: '#30c0dd',
            borderColor: '#30c0dd',
            borderWidth: 2,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Net Worth (₱)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Time Period'
                }
            }
        }
    }
});

</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Calculate percentages for Net Profit and Expenses
    var totalIncome = <?php echo $total_income; ?>;
    var totalExpenses = <?php echo $total_expenses; ?>;
    var netProfit = totalIncome - totalExpenses;

    var incomePercentage = ((netProfit / totalIncome) * 100).toFixed(2); // Net Profit %
    var expensesPercentage = ((totalExpenses / totalIncome) * 100).toFixed(2); // Expenses %

    // Get canvas context
    var ctx = document.getElementById('netProfitMarginChart').getContext('2d');

    // Create gradient colors
    var gradientProfit = ctx.createLinearGradient(0, 0, 0, 300);
    gradientProfit.addColorStop(0, '#1E88E5'); // Blue
    gradientProfit.addColorStop(1, '#64B5F6'); // Lighter blue

    var gradientExpenses = ctx.createLinearGradient(0, 0, 0, 300);
    gradientExpenses.addColorStop(0, '#9E9E9E'); // Gray
    gradientExpenses.addColorStop(1, '#E0E0E0'); // Light gray

    // Net Profit Margin Doughnut Chart
    var netProfitMarginChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Net Profit (' + incomePercentage + '%)', 'Expenses (' + expensesPercentage + '%)'],
            datasets: [{
                data: [incomePercentage, expensesPercentage],
                backgroundColor: [gradientProfit, gradientExpenses],
                hoverBackgroundColor: ['#1565C0', '#30c0dd'], // Highlight colors
                borderColor: '#30c0dd', // White border for separation
                borderWidth: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            cutout: '75%', // Thin ring
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: '#4F4F4F',
                        font: {
                            size: 14,
                            weight: '600'
                        },
                        usePointStyle: true, // Circular legend markers
                        padding: 20
                    }
                },
                tooltip: {
                    enabled: true,
                    callbacks: {
                        label: function (tooltipItem) {
                            return `${tooltipItem.label}: ${tooltipItem.raw}%`;
                        }
                    }
                }
            },
            elements: {
                center: {
                    text: incomePercentage + '%',
                    color: '#1E88E5', // Text color
                    fontStyle: 'bold', // Font style
                    sidePadding: 20, // Padding around the text
                    minFontSize: 18, // Minimum font size
                    lineHeight: 25 // Line height
                }
            }
        },
        plugins: [{
            // Custom plugin to display text in the center
            id: 'centerText',
            beforeDraw: function (chart) {
                if (chart.config.options.elements.center) {
                    var ctx = chart.ctx;
                    var centerConfig = chart.config.options.elements.center;
                    var fontStyle = centerConfig.fontStyle || 'Arial';
                    var txt = centerConfig.text;
                    var color = centerConfig.color || '#000';
                    var sidePadding = centerConfig.sidePadding || 20;
                    var sidePaddingCalculated = (sidePadding / 100) * (chart.innerRadius * 2);
                    ctx.font = centerConfig.minFontSize + 'px ' + fontStyle;

                    // Calculate text width
                    var stringWidth = ctx.measureText(txt).width;
                    var elementWidth = (chart.innerRadius * 2) - sidePaddingCalculated;

                    // Adjust font size if necessary
                    var widthRatio = elementWidth / stringWidth;
                    var newFontSize = Math.floor(centerConfig.minFontSize * widthRatio);
                    var elementHeight = (chart.innerRadius * 2);

                    ctx.font = newFontSize + 'px ' + fontStyle;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = color;

                    // Draw text in the center
                    var centerX = ((chart.chartArea.left + chart.chartArea.right) / 2);
                    var centerY = ((chart.chartArea.top + chart.chartArea.bottom) / 2);
                    ctx.fillText(txt, centerX, centerY);
                }
            }
        }]
    });
});
</script>

<script>
const grossProfitData = <?php echo json_encode($net_worth_history); ?>;
console.log(grossProfitData); // Debugging output

// Prepare the data for the chart
const grossProfit = grossProfitData.map(data => {
    const income = data.total_income;
    const cogs = data.total_cogs || 0; // Ensure COGS is available
    const grossProfitValue = income - cogs; // Gross Profit = Income - COGS
    return { date: data.date, gross_profit: grossProfitValue };
});

console.log(grossProfit); // Debugging output

// Gross Profit Chart
const grossProfitChart = new Chart(document.getElementById('grossProfitChart'), {
    type: 'line',
    data: {
        labels: grossProfit.map(data => data.date), // Use the dates as labels
        datasets: [{
            label: 'Gross Profit',
            data: grossProfit.map(data => data.gross_profit),
            fill: false,
            borderColor: '#30c0dd',  // Green color for the line
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: { 
                callbacks: {
                    label: function(tooltipItem) {
                        return 'Gross Profit: ' + tooltipItem.raw.toFixed(2);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true, // Ensures the y-axis starts from zero
                ticks: {
                    callback: function(value) {
                        return value.toFixed(2); // Display numbers with two decimal places
                    }
                }
            }
        }
    }
});
</script>


</body>
</html>