<?php
include('assets/databases/dbconfig.php');

$sql_aging_report = "
SELECT ar.id AS receivable_id, ar.invoice_id, ar.customer_id, ar.total_amount, ar.due_date, ar.status, 
       DATEDIFF(CURDATE(), ar.due_date) AS days_past_due, 
       i.product_name, i.due_date AS invoice_due_date, i.payment_status
FROM accounts_receivable ar
JOIN invoices i ON ar.invoice_id = i.id
WHERE ar.status != 'paid'";

$result_aging_report = $connection->query($sql_aging_report);
$aging_data = [];

while ($row = $result_aging_report->fetch_assoc()) {
    if ($row['days_past_due'] <= 30) {
        $aging_data['0-30'][] = $row;
    } elseif ($row['days_past_due'] <= 60) {
        $aging_data['31-60'][] = $row;
    } elseif ($row['days_past_due'] <= 90) {
        $aging_data['61-90'][] = $row;
    } else {
        $aging_data['90+'][] = $row;
    }
}

?>

<div class="table-responsive">
    <table width="100%">
        <thead>
            <tr>
                <th>Customer ID</th>
                <th>Invoice ID</th>
                <th>Product Name</th>
                <th>Amount Due</th>
                <th>Days Past Due</th>
                <th>Status</th>
                <th>Due Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($aging_data as $age_range => $items): ?>
                <tr><td colspan="7"><strong><?php echo $age_range; ?> Days</strong></td></tr>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo $item['customer_id']; ?></td>
                        <td><?php echo $item['invoice_id']; ?></td>
                        <td><?php echo $item['product_name']; ?></td>
                        <td>₱<?php echo number_format($item['total_amount'], 2); ?></td>
                        <td><?php echo $item['days_past_due']; ?> days</td>
                        <td><?php echo $item['status']; ?></td>
                        <td><?php echo $item['due_date']; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
