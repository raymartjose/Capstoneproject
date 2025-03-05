<?php
include('assets/databases/dbconfig.php');

$notifications = [];


$invoice_query = "SELECT i.id, c.name AS customer_name, 'invoice' AS type, i.due_date AS created_at 
FROM invoices i 
JOIN customers c ON i.customer_id = c.id 
WHERE i.payment_status = 'overdue' 
ORDER BY i.due_date ASC";

$invoice_result = mysqli_query($connection, $invoice_query);
while ($row = mysqli_fetch_assoc($invoice_result)) {
    $notifications[] = $row;
}

// Fetch pending and returned requests
$request_query = "SELECT id, request_type, status, 'request' AS type, created_at 
                  FROM requests 
                  WHERE status IN ('Approved', 'Returned', 'Rejected') 
                  ORDER BY created_at DESC";

$request_result = mysqli_query($connection, $request_query);
while ($row = mysqli_fetch_assoc($request_result)) {
    $notifications[] = $row;
}

echo json_encode($notifications);
?>
