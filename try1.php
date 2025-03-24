<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices Paid Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .charts {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .chart-container {
            width: 45%;
            min-width: 300px;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .invoice-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .invoice-table th, .invoice-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .invoice-table th {
            background: #007bff;
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Invoices Paid Dashboard</h2>

    <div class="charts">
        <div class="chart-container">
            <canvas id="barChart"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="pieChart"></canvas>
        </div>
    </div>

    <h3>Recent Transactions</h3>
    <table class="invoice-table">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Client</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date Paid</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>INV-1001</td>
                <td>ABC Corp</td>
                <td>$2,500</td>
                <td>Paid</td>
                <td>2025-03-20</td>
            </tr>
            <tr>
                <td>INV-1002</td>
                <td>XYZ Ltd</td>
                <td>$1,800</td>
                <td>Paid</td>
                <td>2025-03-18</td>
            </tr>
            <tr>
                <td>INV-1003</td>
                <td>LMN Inc</td>
                <td>$3,200</td>
                <td>Paid</td>
                <td>2025-03-17</td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    // Bar Chart - Monthly Invoice Totals
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Total Invoices Paid ($)',
                data: [5000, 7000, 4000, 9000, 6000, 8000],
                backgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Pie Chart - Payment Distribution
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: ['Bank Transfer', 'Credit Card', 'PayPal'],
            datasets: [{
                data: [50, 30, 20],
                backgroundColor: ['#007bff', '#28a745', '#ffc107']
            }]
        },
        options: {
            responsive: true
        }
    });
</script>

</body>
</html>
