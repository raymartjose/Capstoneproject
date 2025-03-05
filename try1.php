<?php
include('assets/databases/dbconfig.php');

if (isset($_GET['id'])) {
    $contract_id = intval($_GET['id']);

    // Fetch contract details
    $contractQuery = "SELECT * FROM contracts WHERE id = ?";
    $stmt = $connection->prepare($contractQuery);
    $stmt->bind_param("i", $contract_id);
    $stmt->execute();
    $contractResult = $stmt->get_result();
    
    if ($contractRow = $contractResult->fetch_assoc()) {
        $contract = $contractRow; // Assign fetched data to $contract

        // Ensure each field exists before using it
        $customer_id = $contract['customer_id'] ?? 'N/A';
        $product_id = $contract['product_id'] ?? 'N/A';
        $product_name = $contract['equipment_type'] ?? 'N/A';
        $daily_rate = $contract['rental_rate'] ?? 0.00;
        $rental_days = $contract['rental_period'] ?? 0;
        $total_amount = $daily_rate * $rental_days;
        $issue_date = date('Y-m-d');

        // Set due_date: 30 days from issue OR rental duration end date
        $due_date = date('Y-m-d', strtotime("+30 days", strtotime($issue_date))); 

        // Fetch invoice details (if exists)
        $invoiceQuery = "SELECT * FROM invoices WHERE contract_id = ?";
        $stmt = $connection->prepare($invoiceQuery);
        $stmt->bind_param("i", $contract_id);
        $stmt->execute();
        $invoiceResult = $stmt->get_result();
        $invoice = $invoiceResult->fetch_assoc() ?: null; // Set to null if no invoice found
    } else {
        $contract = null; // Set contract to null if not found
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Contract</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .contract-container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h2, h3 {
            text-align: center;
        }
        .contract-details {
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .signature-section {
            margin-top: 40px;
            text-align: center;
        }
        .signature-line {
            display: inline-block;
            margin: 20px;
            padding-top: 10px;
            border-top: 1px solid black;
            width: 40%;
        }
    </style>
</head>
<body>

<div class="contract-container">
    <h2>Rental Agreement</h2>

    <?php if ($contract): ?>
        <h3>Contract No: <?php echo htmlspecialchars($contract['id']); ?></h3>

        <div class="contract-details">
            <p><strong>Date:</strong> <?php echo htmlspecialchars(date("F j, Y", strtotime($contract['created_at'] ?? 'now'))); ?></p>
            <p><strong>Company Name:</strong> <?php echo htmlspecialchars($contract['company_name'] ?? 'N/A'); ?></p>
            <p><strong>Client Name:</strong> <?php echo htmlspecialchars($contract['client_name'] ?? 'N/A'); ?></p>
            <p><strong>Rental Location:</strong> <?php echo htmlspecialchars($contract['location'] ?? 'N/A'); ?></p>
        </div>

        <h3>Equipment Details</h3>
        <table>
            <tr>
                <th>Equipment Type</th>
                <td><?php echo htmlspecialchars($contract['equipment_type'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <th>Rental Period</th>
                <td><?php echo htmlspecialchars($rental_days); ?> Days</td>
            </tr>
            <tr>
                <th>Rental Rate</th>
                <td><?php echo number_format($daily_rate, 2); ?> per day</td>
            </tr>
        </table>

        <h3>Payment Terms</h3>
        <table>
            <tr>
                <th>Total Rental Fee</th>
                <td><?php echo number_format($total_amount, 2); ?></td>
            </tr>
            <tr>
                <th>Late Payment Penalty</th>
                <td><?php echo number_format($contract['late_payment_penalty'] ?? 0, 2); ?></td>
            </tr>
            <tr>
                <th>Late Return Penalty</th>
                <td><?php echo number_format($contract['late_return_penalty'] ?? 0, 2); ?> per day</td>
            </tr>
        </table>

        <h3>Terms & Conditions</h3>
        <p>
            1. The renter agrees to return the equipment in the same condition as received.  
            2. Any damages or repairs required due to improper use will be charged to the renter.  
            3. Late returns beyond the rental period will be subject to additional charges as per the late return penalty.  
            4. Payment must be made according to the agreed terms to avoid penalties.  
        </p>

        <div class="signature-section">
            <div class="signature-line">Authorized Company Representative</div>
            <div class="signature-line">Client Signature</div>
        </div>

    <?php else: ?>
        <p style="text-align:center; color:red;">Contract not found.</p>
    <?php endif; ?>
</div>

<h2>Invoice Details</h2>
<?php if ($invoice): ?>
    <table>
        <tr><th>Invoice ID</th><td><?php echo htmlspecialchars($invoice['id']); ?></td></tr>
        <tr><th>Invoice Date</th><td><?php echo htmlspecialchars($invoice['issue_date']); ?></td></tr>
        <tr><th>Due Date</th><td><?php echo htmlspecialchars($invoice['due_date']); ?></td></tr>
        <tr><th>Status</th><td><?php echo htmlspecialchars($invoice['payment_status']); ?></td></tr>
        <tr><th>Amount</th><td>$<?php echo number_format($invoice['amount'], 2); ?></td></tr>
    </table>
    
<?php elseif ($contract): ?>
    <p>No invoice linked to this contract.</p>
    <a href="create_invoice1.php?contract_id=<?php echo $contract_id; ?>">View Invoice</a>
<?php endif; ?>

</body>
</html>
