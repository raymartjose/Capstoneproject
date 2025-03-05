<?php
include('assets/databases/dbconfig.php');

header('Content-Type: application/json');

// Read JSON input from core system
$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $contract_id = $data['contract_id'];
    $company_name = $data['company_name'];
    $company_address = $data['company_address'];
    $company_contact = $data['company_contact'];
    $client_name = $data['client_name'];
    $client_address = $data['client_address'];
    $client_contact = $data['client_contact'];
    $equipment_type = $data['equipment_type'];
    $rental_period = $data['rental_period'];
    $location = $data['location'];
    $operator_provided = $data['operator_provided'];
    $rental_rate = $data['rental_rate'];
    $discounts = $data['discounts'];
    $late_payment_penalty = $data['late_payment_penalty'];
    $late_return_penalty = $data['late_return_penalty'];
    $governing_law = $data['governing_law'];
    $total_amount = $data['total_amount'];
    $income_category = $data['income_category']; // Added field
    $due_date = $data['due_date']; // Added field
    $payment_status = $data['payment_status']; // Added field
    $payment_method = $data['payment_method']; // Added field
    $issue_date = date("Y-m-d"); // Set to today's date
    $payment_date = isset($data['payment_date']) ? $data['payment_date'] : null; // Optional field
    $recurring = isset($data['recurring']) ? $data['recurring'] : 0; // Default to 0
    $next_invoice_date = isset($data['next_invoice_date']) ? $data['next_invoice_date'] : null; // Optional field
    $next_billing_date = isset($data['next_billing_date']) ? $data['next_billing_date'] : null; // Optional field
    $billing_period = isset($data['billing_period']) ? $data['billing_period'] : 'monthly'; // Default to 'monthly'
    $reminder_flag = isset($data['reminder_flag']) ? $data['reminder_flag'] : 0; // Default to 0
    $receivable_status = isset($data['receivable_status']) ? $data['receivable_status'] : 'pending'; // Default to 'pending'
    $expense_type = isset($data['expense_type']) ? $data['expense_type'] : null; // Optional field
    $tax_amount = isset($data['tax_amount']) ? $data['tax_amount'] : 0.00; // Default to 0
    $discount_amount = isset($data['discount_amount']) ? $data['discount_amount'] : 0.00; // Default to 0
    $discount_rate = isset($data['discount_rate']) ? $data['discount_rate'] : 0.00; // Default to 0
    $tax_rate = isset($data['tax_rate']) ? $data['tax_rate'] : 0.00; // Default to 0
    $currency = isset($data['currency']) ? $data['currency'] : 'PHP'; // Default to 'PHP'
    $tax_id = isset($data['tax_id']) ? $data['tax_id'] : null; // Optional field
    $tax_breakdown = isset($data['tax_breakdown']) ? $data['tax_breakdown'] : null; // Optional field
    $surcharges = isset($data['surcharges']) ? $data['surcharges'] : 0.00; // Default to 0
    $additional_services = isset($data['additional_services']) ? $data['additional_services'] : null; // Optional field

    // Insert into contracts table
    $sql_contract = "INSERT INTO contracts (contract_id, company_name, company_address, company_contact, 
        client_name, client_address, client_contact, equipment_type, rental_period, location, 
        operator_provided, rental_rate, discounts, late_payment_penalty, late_return_penalty, 
        governing_law, total_amount, income_category, due_date, payment_status, payment_method, 
        issue_date, payment_date, recurring, next_invoice_date, next_billing_date, billing_period, 
        reminder_flag, receivable_status, expense_type, tax_amount, discount_amount, discount_rate, 
        tax_rate, currency, tax_id, tax_breakdown, surcharges, additional_services) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_contract = $connection->prepare($sql_contract);
    $stmt_contract->bind_param("ssssssssssssssssssssssssssssssss", $contract_id, $company_name, $company_address, 
        $company_contact, $client_name, $client_address, $client_contact, $equipment_type, $rental_period, 
        $location, $operator_provided, $rental_rate, $discounts, $late_payment_penalty, $late_return_penalty, 
        $governing_law, $total_amount, $income_category, $due_date, $payment_status, $payment_method, 
        $issue_date, $payment_date, $recurring, $next_invoice_date, $next_billing_date, $billing_period, 
        $reminder_flag, $receivable_status, $expense_type, $tax_amount, $discount_amount, $discount_rate, 
        $tax_rate, $currency, $tax_id, $tax_breakdown, $surcharges, $additional_services);

    if ($stmt_contract->execute()) {
        $last_contract_id = $connection->insert_id; // Get inserted contract ID
        
        // Set invoice details
        $due_date_invoice = isset($data['due_date']) ? $data['due_date'] : date("Y-m-d", strtotime("+30 days")); // Default 30-day due period

        // Insert into invoices table
        $sql_invoice = "INSERT INTO invoices (contract_id, customer_id, product_name, daily_rate, amount, 
            due_date, payment_status, issue_date, total_amount) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)";

        $stmt_invoice = $connection->prepare($sql_invoice);
        $stmt_invoice->bind_param("iissdssd", $last_contract_id, $client_contact, $equipment_type, $rental_rate, 
            $total_amount, $due_date_invoice, $issue_date, $total_amount);

        if ($stmt_invoice->execute()) {
            echo json_encode(["status" => "success", "message" => "Contract and invoice stored successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error storing invoice: " . $stmt_invoice->error]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Error storing contract: " . $stmt_contract->error]);
    }

    // Close statements and connection
    $stmt_contract->close();
    $stmt_invoice->close();
    $connection->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
}
?>
