<?php
// Include database connection
include('assets/databases/dbconfig.php');

// Get customer_id from the query parameter
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

if ($customer_id > 0) {
    // Fetch customer name
    $customerQuery = "SELECT name FROM customers WHERE id = $customer_id LIMIT 1";
    $customerResult = mysqli_query($connection, $customerQuery);
    $customerRow = mysqli_fetch_assoc($customerResult);
    $customer_name = $customerRow['name'] ?? 'Unknown';

    // Query to get all invoices for the specific customer
    $query = "SELECT i.id, i.product_name, i.total_amount, i.payment_status, i.payment_method, i.due_date, i.issue_date
              FROM invoices i
              WHERE i.customer_id = $customer_id
              ORDER BY i.issue_date DESC";
    $result = mysqli_query($connection, $query);

    echo "<h2>" . htmlspecialchars($customer_name) . "</h2>";

    if (mysqli_num_rows($result) > 0) {
        echo '<table width="100%">
                <thead>
                    <tr>
                        <td>Invoice Number</td>
                        <td>Product Name</td>
                        <td>Amount</td>
                        <td>Status</td>
                        <td>Payment Method</td>
                        <td>Due Date</td>
                        <td>Issue Date</td>
                    </tr>
                </thead>
                <tbody>';

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['id']) . "</td>
                    <td>" . htmlspecialchars($row['product_name']) . "</td>
                    <td>₱" . number_format($row['total_amount'], 2) . "</td>
                    <td>" . ucfirst($row['payment_status']) . "</td>
                    <td>" . htmlspecialchars($row['payment_method']) . "</td>
                    <td>" . htmlspecialchars($row['due_date']) . "</td>
                    <td>" . htmlspecialchars($row['issue_date']) . "</td>
                  </tr>";
        }

        echo '</tbody>
            </table>';
    } else {
        echo "<p>No transactions found for this customer.</p>";
    }
}
?>
