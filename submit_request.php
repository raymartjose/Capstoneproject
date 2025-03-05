<?php
session_start();
include('assets/databases/dbconfig.php');

$user_id = $_SESSION['user_id'];
$request_type = $_POST['request_type'];
$amount = $_POST['amount'];
$description = $_POST['description'];
$request_date = date('Y-m-d H:i:s');

try {
    if ($request_type === 'budget') {
        $sql = "INSERT INTO budget_requests (user_id, request_date, amount, description, status, created_at) 
                VALUES (?, ?, ?, ?, 'Pending', ?)";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("issss", $user_id, $request_date, $amount, $description, $request_date);
    } else {
        $category_id = $_POST['category_id'];
        $sql = "INSERT INTO expense (user_id, expense_date, amount, source, category_id, description, status, created_at) 
                VALUES (?, ?, ?, 'Requested', ?, ?, 'Pending', ?)";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("isssis", $user_id, $request_date, $amount, $category_id, $description, $request_date);
    }

    $stmt->execute();

    // Notification for the super admin
    $notification_sql = "INSERT INTO notifications (user_id, type, message, status, created_at) 
                         VALUES (?, 'request', ?, 'unread', ?)";
    $message = "New $request_type request from user $user_id";
    $notification_stmt = $connection->prepare($notification_sql);
    $notification_stmt->bind_param("iss", $admin_id, $message, $request_date);
    $admin_id = 1;  // Assuming the admin has user_id = 1
    $notification_stmt->execute();

    echo "Request submitted successfully!";
} catch (Exception $e) {
    echo "Failed to submit request: " . $e->getMessage();
}
?>
