<?php
include('assets/databases/dbconfig.php');
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['customer_id'], $_POST['product_id'], $_POST['rental_start_date'], $_POST['rental_end_date'], $_POST['tax_id'])) {

        $customer_id = $_POST['customer_id'];
        $product_id = $_POST['product_id'];
        $rental_start_date = $_POST['rental_start_date'];
        $rental_end_date = $_POST['rental_end_date'];
        $tax_id = $_POST['tax_id'];
        $discount_amount = $_POST['discount_amount'] ?? 0.00;
        $payment_status = $_POST['payment_status'];
        $payment_method = $_POST['payment_method'] ?? NULL;
        $surcharges = $_POST['surcharges'] ?? 0.00;

        $selected_services = $_POST['additional_services'] ?? [];
        $additional_services_cost = 0.00;

        if ($selected_services && !in_array("none", $selected_services)) {
            foreach ($selected_services as $service_id) {
                $serviceQuery = "SELECT price FROM additional_services WHERE id = '$service_id'";
                $serviceResult = mysqli_query($connection, $serviceQuery);
                if ($service = mysqli_fetch_assoc($serviceResult)) {
                    $additional_services_cost += $service['price'];
                } else {
                    echo json_encode(['success' => false, 'message' => "Service ID $service_id does not exist in the additional_services table."]);
                    exit;
                }
            }
        }

        // Fetch tax details
        $taxQuery = "SELECT rate FROM taxes WHERE id = '$tax_id'";
        $taxResult = mysqli_query($connection, $taxQuery);
        $tax = mysqli_fetch_assoc($taxResult);
        $tax_rate = $tax['rate'] ?? 12; // Default VAT rate in PH is 12%

        // Fetch product details for rental calculation
        $productQuery = "SELECT id, name, daily_rate FROM products WHERE id = '$product_id' AND status = 'available'";
        $productResult = mysqli_query($connection, $productQuery);

        if (mysqli_num_rows($productResult) > 0) {
            $product = mysqli_fetch_assoc($productResult);
            $daily_rate = $product['daily_rate'];
            $product_name = $product['name'];

            // Fetch customer name
            $customerQuery = "SELECT name FROM customers WHERE id = '$customer_id'";
            $customerResult = mysqli_query($connection, $customerQuery);
            $customer = mysqli_fetch_assoc($customerResult);
            $customer_name = $customer['name'] ?? 'Unknown';

            // Calculate rental duration
            $start_date = new DateTime($rental_start_date);
            $end_date = new DateTime($rental_end_date);
            $rental_days = $start_date->diff($end_date)->days;

            // Calculate rental cost
            $sub_total = ($daily_rate * $rental_days) + $additional_services_cost + $surcharges;

            // Apply discount BEFORE tax
            $discounted_amount = max(0, $sub_total - $discount_amount);

            // Compute VAT (12%)
            $tax_amount = $discounted_amount * ($tax_rate / 100);
            
            // Compute total amount
            $total_amount = $discounted_amount + $tax_amount;

            // Generate invoice number (Example: INV-YYYYMMDD-RAND)
            $invoice_no = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);

            // Insert invoice data
            $invoiceQuery = "INSERT INTO invoices (customer_id, product_id, product_name, daily_rate, amount, tax_rate, tax_amount, discount_amount, 
                             total_amount, payment_status, payment_method, tax_id, surcharges, issue_date, due_date, id)
                             VALUES ('$customer_id', '$product_id', '$product_name', '$daily_rate', '$sub_total', '$tax_rate', '$tax_amount', '$discount_amount', 
                             '$total_amount', '$payment_status', '$payment_method', '$tax_id', '$surcharges', '$rental_start_date', '$rental_end_date', '$invoice_no')";

            if (mysqli_query($connection, $invoiceQuery)) {
                $invoice_id = mysqli_insert_id($connection);

                // Audit log for invoice creation
                $currentUserId = $_SESSION['user_id'];
                $action = "Created";
                $recordType = "Invoice";
                $newData = json_encode([
                    'customer_id' => $customer_id,
                    'product_id' => $product_id,
                    'product_name' => $product_name,
                    'amount' => $total_amount,
                    'tax_rate' => $tax_rate,
                    'discount_amount' => $discount_amount,
                    'total_amount' => $total_amount,
                    'payment_status' => $payment_status,
                    'payment_method' => $payment_method,
                    'surcharges' => $surcharges,
                    'rental_start_date' => $rental_start_date,
                    'rental_end_date' => $rental_end_date
                ]);

                $auditQuery = "INSERT INTO audit_logs (user_id, action, record_type, record_id, new_data) 
                               VALUES ('$currentUserId', '$action', '$recordType', '$invoice_id', '$newData')";
                mysqli_query($connection, $auditQuery);

                // Insert additional services
                foreach ($selected_services as $service_id) {
                    $serviceInsertQuery = "INSERT INTO invoice_services (invoice_id, service_id) VALUES ('$invoice_id', '$service_id')";
                    mysqli_query($connection, $serviceInsertQuery);
                }

                // Update product status
                $updateProductQuery = "UPDATE products SET status = 'rented' WHERE id = '$product_id'";
                mysqli_query($connection, $updateProductQuery);

                if ($payment_status === 'paid') {
                    // Insert transaction record with payment method
                    $transactionQuery = "INSERT INTO transactions (type, amount, description, transaction_date, payment_method)
                                         VALUES ('income', '$total_amount', 'Payment for Invoice #$invoice_no', NOW(), '$payment_method')";
                    mysqli_query($connection, $transactionQuery);

                    // Mark as paid in `receivables`
                    $updateReceivableQuery = "UPDATE receivables SET current_amount = 0.00 WHERE invoice_id = '$invoice_no'";
                    mysqli_query($connection, $updateReceivableQuery);

                    // Remove asset entry
                    $deleteAssetQuery = "DELETE FROM assets WHERE asset_name = 'Accounts Receivable #$invoice_no' AND type = 'Account Receivable'";
                    mysqli_query($connection, $deleteAssetQuery);

                    echo json_encode(['success' => true, 'message' => 'Invoice and transaction added successfully, receivable marked as paid.']);
                } else {
                    // Insert into receivables
                    $receivableQuery = "INSERT INTO receivables (customer_name, invoice_id, due_date, current_amount, past_due_30, past_due_60, past_due_90, past_due_90plus)
                        VALUES ('$customer_name', '$invoice_id', '$rental_end_date', '$total_amount', '0.00', '0.00', '0.00', '0.00')";
                    mysqli_query($connection, $receivableQuery);

                    // Add receivable to assets
                    $assetQuery = "INSERT INTO assets (asset_name, value, type)
                                   VALUES ('Accounts Receivable #$invoice_no', '$total_amount', 'Account Receivable')";
                    mysqli_query($connection, $assetQuery);

                                        // Check if Accounts Receivable already exists
$checkCOAQuery = "SELECT balance FROM chart_of_accounts WHERE account_name = 'Accounts Receivable' AND category = 'Asset'";
$checkCOAResult = mysqli_query($connection, $checkCOAQuery);

if (mysqli_num_rows($checkCOAResult) > 0) {
    // Update existing balance
    $updateCOAQuery = "UPDATE chart_of_accounts SET balance = balance + '$total_amount' WHERE account_name = 'Accounts Receivable' AND category = 'Asset'";
    mysqli_query($connection, $updateCOAQuery);
} else {
    // Insert new record if not exists
    $insertCOAQuery = "INSERT INTO chart_of_accounts (account_name, category, balance)
                       VALUES ('Accounts Receivable', 'Asset', '$total_amount')";
    mysqli_query($connection, $insertCOAQuery);
}

                    echo json_encode(['success' => true, 'message' => 'Invoice added, receivable recorded.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding invoice: ' . mysqli_error($connection)]);
            }
        }
    }
}
?>
