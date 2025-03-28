<?php
include('assets/databases/dbconfig.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
    $payment_status = isset($_POST['payment_status']) ? $_POST['payment_status'] : '';
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';

    // Ensure that invoice_id is valid and payment status is set
    if ($invoice_id > 0 && !empty($payment_status)) {
        // If payment status is "cancel" or "overdue", we don't require a payment method
        if (($payment_status == 'cancel' || $payment_status == 'overdue') && empty($payment_method)) {
            // Set payment_method to "N/A" or any default value as it's not required for these statuses
            $payment_method = 'N/A'; 
        }

        // Update invoice payment status and method
        if (($payment_status != 'cancel' && $payment_status != 'overdue' && !empty($payment_method)) || $payment_method == 'N/A') {
            // Update the invoice with the new payment status and method
            $query = "UPDATE invoices SET payment_status = ?, payment_method = ?, payment_date = ? WHERE id = ?";
            $payment_date = ($payment_status == 'paid') ? date('Y-m-d') : NULL;  // Set payment_date only if the status is "paid"
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, 'sssi', $payment_status, $payment_method, $payment_date, $invoice_id);

            if (mysqli_stmt_execute($stmt)) {
                // If payment is made (status is "paid") or canceled (status is "cancel"), update receivables
                if ($payment_status == 'paid' || $payment_status == 'cancel') {
                    // Step 1: Remove from account receivable table using invoice_id
                    $delete_query = "DELETE FROM receivables WHERE invoice_id = ?";
                    $delete_stmt = mysqli_prepare($connection, $delete_query);
                    mysqli_stmt_bind_param($delete_stmt, 'i', $invoice_id);  // Using invoice_id directly
                    
                    if (mysqli_stmt_execute($delete_stmt)) {
                        // Check if any row was deleted
                        if (mysqli_affected_rows($connection) > 0) {
                            echo "Record deleted from receivables.";
                        } else {
                            echo "No matching record found in receivables for invoice ID $invoice_id.";
                        }
                    } else {
                        echo "Error deleting from receivables: " . mysqli_error($connection);
                    }

                    // If payment is "paid", insert into transactions table (income)
                    if ($payment_status == 'paid') {
                        $amount = getInvoiceAmount($invoice_id);  // Function to fetch the invoice amount
                        $transaction_query = "INSERT INTO transactions (type, amount, total_amount, transaction_date, description, payment_method) VALUES ('income', ?, ?, CURDATE(), ?, ?)";
                        $transaction_stmt = mysqli_prepare($connection, $transaction_query);
                        $description = "Payment for invoice ID #" . $invoice_id;
                        mysqli_stmt_bind_param($transaction_stmt, 'ddss', $amount, $amount, $description, $payment_method);
                        mysqli_stmt_execute($transaction_stmt);
                    }
                }

                // Step 2: Update the balance of 'Accounts Receivable' in the chart_of_accounts table
                updateAccountsReceivableBalance();

                // Step 3: Update or remove corresponding asset record in the assets table
                updateAssetRecord($invoice_id, $payment_status);

                echo "<script>alert('Invoice updated successfully.'); window.location.href='create_invoice.php';</script>";
            } else {
                echo "<script>alert('Error updating invoice.');</script>";
            }
        } else {
            echo "<script>alert('Please provide valid payment details.');</script>";
        }
    } else {
        echo "<script>alert('Please provide valid payment details.');</script>";
    }
}

// Function to fetch invoice amount (assuming you have a method for it)
function getInvoiceAmount($invoice_id) {
    global $connection;
    $query = "SELECT total_amount FROM invoices WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $invoice_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $total_amount);
    mysqli_stmt_fetch($stmt);
    return $total_amount;
}

// Function to update 'Accounts Receivable' and 'Cash' balance
function updateAccountsReceivableBalance() {
    global $connection;

    // Get total outstanding receivables
    $receivablesQuery = "SELECT SUM(total_due) AS total_receivables FROM receivables WHERE total_due > 0";
    $receivablesResult = $connection->query($receivablesQuery);
    $receivablesRow = $receivablesResult->fetch_assoc();
    $totalReceivables = $receivablesRow['total_receivables'] ?? 0.00;

                        // Insert or update the chart of accounts for Accounts Receivable
$checkARQuery = "SELECT balance FROM chart_of_accounts WHERE account_name = 'Accounts Receivable' AND category = 'Asset'";
$checkARResult = mysqli_query($connection, $checkARQuery);

if (mysqli_num_rows($checkARResult) > 0) {
    // Update existing AR balance
    $updateARQuery = "UPDATE chart_of_accounts 
                      SET balance = balance + '$total_amount' 
                      WHERE account_name = 'Accounts Receivable' 
                      AND category = 'Asset'";
    mysqli_query($connection, $updateARQuery);
} else {
    // Insert new AR entry if it doesn't exist
    $insertARQuery = "INSERT INTO chart_of_accounts (account_code, account_name, category, balance) 
                      VALUES ('10301010', 'Accounts Receivable', 'Asset', '$total_amount')";
    mysqli_query($connection, $insertARQuery);
}


    // Update the balance for 'Accounts Receivable' in the chart_of_accounts table
    $updateReceivablesQuery = "UPDATE chart_of_accounts SET balance = ? WHERE account_name = 'Accounts Payable'";
    $stmt = mysqli_prepare($connection, $updateReceivablesQuery);
    mysqli_stmt_bind_param($stmt, 'd', $totalReceivables);
    mysqli_stmt_execute($stmt);

    // If payment is made, update the 'Cash' balance in the chart_of_accounts table
    if ($payment_status == 'paid') {
        $amount = getInvoiceAmount($invoice_id); // Fetch the payment amount

        // Get current cash balance
        $cashQuery = "SELECT balance FROM chart_of_accounts WHERE account_name = 'Cash'";
        $cashResult = mysqli_query($connection, $cashQuery);
        $cashRow = mysqli_fetch_assoc($cashResult);
        $currentCashBalance = $cashRow['balance'] ?? 0.00;

        // Update the cash balance by adding the payment amount
        $newCashBalance = $currentCashBalance + $amount;
        $updateCashQuery = "UPDATE chart_of_accounts SET balance = ? WHERE account_name = 'Cash'";
        $updateCashStmt = mysqli_prepare($connection, $updateCashQuery);
        mysqli_stmt_bind_param($updateCashStmt, 'd', $newCashBalance);
        mysqli_stmt_execute($updateCashStmt);
    }
}


// Function to update or remove asset record in the assets table
function updateAssetRecord($invoice_id, $payment_status) {
    global $connection;

    // Fetch the asset linked to the invoice
    $invoice_number = getInvoiceNumber($invoice_id);  // Assuming you have a function to get invoice number
    $asset_query = "SELECT id, asset_name, value FROM assets WHERE asset_name LIKE '%$invoice_number%'";
    $asset_result = mysqli_query($connection, $asset_query);

    if ($asset_row = mysqli_fetch_assoc($asset_result)) {
        $asset_id = $asset_row['id'];
        $asset_value = $asset_row['value'];

        if ($payment_status == 'paid' || $payment_status == 'cancel') {
            // If payment is made or canceled, remove the asset
            $delete_asset_query = "DELETE FROM assets WHERE id = ?";
            $delete_asset_stmt = mysqli_prepare($connection, $delete_asset_query);
            mysqli_stmt_bind_param($delete_asset_stmt, 'i', $asset_id);
            mysqli_stmt_execute($delete_asset_stmt);
            echo "Asset record removed for invoice $invoice_number.";
        }
    }
}

// Function to fetch invoice number (assuming you have a method for it)
function getInvoiceNumber($invoice_id) {
    global $connection;
    $query = "SELECT id FROM invoices WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $invoice_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $invoice_number);
    mysqli_stmt_fetch($stmt);
    return $invoice_number;
}



?>
