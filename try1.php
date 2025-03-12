<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .report-container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="report-container">
        <h2>Income Report - Paid Invoices</h2>
        <table>
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    <th>Customer Name</th>
                    <th>Total Amount</th>
                    <th>Payment Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>INV-001</td>
                    <td>John Doe</td>
                    <td>$1,500</td>
                    <td>2025-03-10</td>
                    <td style="color: green; font-weight: bold;">Paid</td>
                </tr>
                <tr>
                    <td>INV-002</td>
                    <td>Jane Smith</td>
                    <td>$2,200</td>
                    <td>2025-03-11</td>
                    <td style="color: green; font-weight: bold;">Paid</td>
                </tr>
                <tr>
                    <td>INV-003</td>
                    <td>Mark Johnson</td>
                    <td>$3,000</td>
                    <td>2025-03-12</td>
                    <td style="color: green; font-weight: bold;">Paid</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>