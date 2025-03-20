<?php
include('assets/databases/dbconfig.php');

$query = "SELECT 
            SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) AS paid,
            SUM(CASE WHEN payment_status IN ('pending', 'overdue') THEN total_amount ELSE 0 END) AS pending_overdue,
            SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END) AS cash_amount,
            SUM(CASE WHEN payment_method = 'credit' THEN total_amount ELSE 0 END) AS credit_amount,
            SUM(CASE WHEN payment_method = 'bank_transfer' THEN total_amount ELSE 0 END) AS bank_transfer_amount,
            SUM(CASE WHEN payment_method = 'online' THEN total_amount ELSE 0 END) AS online_amount,
            COUNT(CASE WHEN payment_method = 'cash' THEN 1 END) AS cash_count,
            COUNT(CASE WHEN payment_method = 'credit' THEN 1 END) AS credit_count,
            COUNT(CASE WHEN payment_method = 'bank_transfer' THEN 1 END) AS bank_transfer_count,
            COUNT(CASE WHEN payment_method = 'online' THEN 1 END) AS online_count,
            COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) AS pending,
            COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) AS paid_count,
            COUNT(CASE WHEN payment_status = 'overdue' THEN 1 END) AS overdue
          FROM invoices";

$result = $connection->query($query);
$data = $result->fetch_assoc();

$response = [
    "payments" => [
        "paid" => (float)$data["paid"],
        "pending_overdue" => (float)$data["pending_overdue"]
    ],
    "payment_methods" => [
        "cash" => [
            "count" => (int)$data["cash_count"],
            "amount" => (float)$data["cash_amount"]
        ],
        "credit" => [
            "count" => (int)$data["credit_count"],
            "amount" => (float)$data["credit_amount"]
        ],
        "bank_transfer" => [
            "count" => (int)$data["bank_transfer_count"],
            "amount" => (float)$data["bank_transfer_amount"]
        ],
        "online" => [
            "count" => (int)$data["online_count"],
            "amount" => (float)$data["online_amount"]
        ]
    ],
    "invoice_status" => [
        "pending" => (int)$data["pending"],
        "paid" => (int)$data["paid_count"],
        "overdue" => (int)$data["overdue"]
    ]
];

header('Content-Type: application/json');
echo json_encode($response);
?>
