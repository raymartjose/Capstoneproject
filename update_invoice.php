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
            $query = "UPDATE invoices SET payment_status = ?, payment_method = ? WHERE id = ?";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, 'ssi', $payment_status, $payment_method, $invoice_id);

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
?>
