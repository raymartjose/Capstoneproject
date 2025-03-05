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

    // Insert into contracts table
    $sql_contract = "INSERT INTO contracts (contract_id, company_name, company_address, company_contact, 
        client_name, client_address, client_contact, equipment_type, rental_period, location, 
        operator_provided, rental_rate, discounts, late_payment_penalty, late_return_penalty, 
        governing_law, total_amount) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_contract = $connection->prepare($sql_contract);
    $stmt_contract->bind_param("ssssssssssssssssd", $contract_id, $company_name, $company_address, 
        $company_contact, $client_name, $client_address, $client_contact, 
        $equipment_type, $rental_period, $location, $operator_provided, $rental_rate, 
        $discounts, $late_payment_penalty, $late_return_penalty, $governing_law, $total_amount);

    if ($stmt_contract->execute()) {
        $last_contract_id = $connection->insert_id; // Get inserted contract ID
        
        // Set invoice details
        $issue_date = date("Y-m-d");
        $due_date = date("Y-m-d", strtotime("+30 days")); // Default 30-day due period

        // Insert into invoices table
        $sql_invoice = "INSERT INTO invoices (contract_id, customer_id, product_name, daily_rate, amount, 
            due_date, payment_status, issue_date, total_amount) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)";

        $stmt_invoice = $connection->prepare($sql_invoice);
        $stmt_invoice->bind_param("iissdssd", $last_contract_id, $client_contact, $equipment_type, $rental_rate, 
            $total_amount, $due_date, $issue_date, $total_amount);

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
