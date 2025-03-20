<?php
include('assets/databases/dbconfig.php');

// Fetch paid invoices
$sql = "SELECT i.id, c.name, i.total_amount, i.payment_date, i.payment_method 
        FROM invoices t 
        JOIN invoices i ON i.id = i.id 
        JOIN customers c ON i.customer_id = c.id 
        WHERE i.payment_status = 'Paid'";
$result = $connection->query($sql);

$paidInvoices = [];
while ($row = $result->fetch_assoc()) {
    $paidInvoices[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paid Invoices</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .container { max-width: 1200px; margin: auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f4f4f4; }
        canvas { max-height: 300px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Paid Invoices</h2>
        <canvas id="paidInvoicesChart"></canvas>
        <table>
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    <th>Customer Name</th>
                    <th>Total Amount</th>
                    <th>Payment Date</th>
                    <th>Payment Method</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($paidInvoices as $invoice): ?>
                <tr>
                    <td><?= htmlspecialchars($invoice['id']) ?></td>
                    <td><?= htmlspecialchars($invoice['name']) ?></td>
                    <td>$<?= number_format($invoice['total_amount'], 2) ?></td>
                    <td><?= htmlspecialchars($invoice['payment_date']) ?></td>
                    <td><?= htmlspecialchars($invoice['payment_method']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('paidInvoicesChart').getContext('2d');
            var chartData = {
                labels: [<?php foreach ($paidInvoices as $invoice) echo "'" . $invoice['payment_date'] . "',"; ?>],
                datasets: [{
                    label: 'Total Paid Amount',
                    data: [<?php foreach ($paidInvoices as $invoice) echo $invoice['total_amount'] . ","; ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            };

            new Chart(ctx, {
                type: 'bar',
                data: chartData,
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true } }
                }
            });
        });
    </script>
</body>
</html>
