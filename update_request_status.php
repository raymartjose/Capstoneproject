<?php
include ('assets/databases/dbconfig.php');

$data = json_decode(file_get_contents("php://input"));
$request_id = $data->id;
$status = $data->status;
$type = $data->type;

try {
    if ($type === "budget") {
        // Update budget request status
        if (!$connection->query("UPDATE budget_requests SET status = '$status' WHERE id = $request_id")) {
            throw new Exception("Failed to update budget request status: " . $connection->error);
        }

        // Insert approved budget requests into budget_approved table
        if ($status === "approved") {
            $budgetRequest = $connection->query("SELECT * FROM budget_requests WHERE id = $request_id")->fetch_assoc();
            if (!$connection->query("INSERT INTO budget_approved (user_id, amount, description, approved_date) VALUES 
                                      ({$budgetRequest['user_id']}, {$budgetRequest['amount']}, '{$budgetRequest['description']}', NOW())")) {
                throw new Exception("Failed to insert into budget_approved: " . $connection->error);
            }
        }
    } else {
        // Update expense request status
        if (!$connection->query("UPDATE expense SET status = '$status' WHERE id = $request_id")) {
            throw new Exception("Failed to update expense request status: " . $connection->error);
        }

        // Insert approved expense requests into expense_approved table
        if ($status === "approved") {
            $expenseRequest = $connection->query("SELECT * FROM expense WHERE id = $request_id")->fetch_assoc();
            if (!$connection->query("INSERT INTO expense_approved (user_id, amount, description, approved_date) VALUES 
                                      ({$expenseRequest['user_id']}, {$expenseRequest['amount']}, '{$expenseRequest['description']}', NOW())")) {
                throw new Exception("Failed to insert into expense_approved: " . $connection->error);
            }
        }
    }

    $requestTable = ($type === "budget") ? "budget_requests" : "expense";
    $request = $connection->query("SELECT user_id FROM {$requestTable} WHERE id = $request_id")->fetch_assoc();
    $staff_id = $request['user_id'];
    $message = $status === "approved" ? "Your $type request has been approved" : "Your $type request has been rejected";
    if (!$connection->query("INSERT INTO notifications (user_id, type, message, status) VALUES ($staff_id, 'request', '$message', 'unread')")) {
        throw new Exception("Failed to insert notification: " . $connection->error);
    }

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    error_log($e->getMessage());  // Log the error to the server log
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

?>
