<?php
session_start();
include "assets/databases/dbconfig.php";

$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;  // Get the request ID from the form

if ($request_id === 0) {
    $_SESSION['error'] = "Invalid request ID.";
    header("Location: " . ($_GET['page'] ?? 'super_financial.php')); // Redirect based on the page
    exit();
}

// Identify the source page for redirects
$source_page = $_GET['page'] ?? 'super_financial.php';  // Default to super_financial.php

// Get the request details
$query = "SELECT request_type, amount, created_at FROM requests WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) {
    $_SESSION['error'] = "Request not found.";
    header("Location: $source_page");
    exit();
}

$request_type = $request['request_type'];
$request_amount = $request['amount'];
$request_date = new DateTime($request['created_at']);
$month = $request_date->format('m');
$year = $request_date->format('Y');

// Track status change
$status = "";
if (isset($_POST['approve'])) {
    $status = "Approved";
} elseif (isset($_POST['reject'])) {
    $status = "Rejected";
} elseif (isset($_POST['return'])) {
    $status = "Returned";
} elseif (isset($_POST['save'])) {
    $status = "Pending";
} elseif (isset($_POST['cancel'])) {
    $status = "Cancelled";
}

// If status is set, update the request and log the transaction
if ($status !== "") {
    $query = "UPDATE requests SET status = ? WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("si", $status, $request_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Request $status successfully!";
        
        // Log the transaction
        $stmt = $connection->prepare("INSERT INTO request_transactions (request_id, changed_by, status, changed_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $request_id, $_SESSION['name'], $status);
        $stmt->execute();
        $stmt->close();

        // Fetch total company budget
        $budgetQuery = $connection->prepare("SELECT amount FROM company_budget WHERE month = ? AND year = ?");
        $budgetQuery->bind_param("ii", $month, $year);
        $budgetQuery->execute();
        $budgetResult = $budgetQuery->get_result();
        $budgetData = $budgetResult->fetch_assoc();
        $_SESSION['totalBudget'] = $budgetData['amount'] ?? 0;
        $budgetQuery->close();

        // Fetch total approved budget requests
        $approvedQuery = $connection->prepare("SELECT SUM(amount) AS approved_budget FROM requests WHERE status = 'Approved' AND MONTH(created_at) = ? AND YEAR(created_at) = ?");
        $approvedQuery->bind_param("ii", $month, $year);
        $approvedQuery->execute();
        $approvedResult = $approvedQuery->get_result();
        $approvedData = $approvedResult->fetch_assoc();
        $_SESSION['approvedBudget'] = $approvedData['approved_budget'] ?? 0; // Store approved budget
        $approvedQuery->close();
        
    } else {
        $_SESSION['error'] = "Failed to $status the request.";
    }
}

// Handle file uploads if any
if (!empty($_FILES['attachments']['name'][0])) {
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    foreach ($_FILES['attachments']['name'] as $index => $name) {
        $fileTmpPath = $_FILES['attachments']['tmp_name'][$index];
        $fileCategory = $_POST["categories"][$index] ?? 'other';
        $fileRemark = $_POST["remarks1"][$index] ?? '';  // Capture the remark for each file
        $filePath = $uploadDir . basename($name);

        if (move_uploaded_file($fileTmpPath, $filePath)) {
            $stmt = $connection->prepare("INSERT INTO attachments (request_id, file_name, file_path, category, remark, uploaded_at) 
                                          VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("issss", $request_id, $name, $filePath, $fileCategory, $fileRemark);
            if (!$stmt->execute()) {
                error_log("Attachment Insert Error: " . $stmt->error);
            }
        } else {
            error_log("File Upload Failed for: " . $name);
        }
    }
}

// Handle remarks if any
if (!empty($_POST['remarks'])) {
    foreach ($_POST['remarks'] as $remark) {
        if (!empty(trim($remark))) {
            $stmt = $connection->prepare("INSERT INTO remarks (request_id, user_name, remark, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iss", $request_id, $_SESSION['name'], $remark);
            if (!$stmt->execute()) {
                error_log("Remarks Insert Error: " . $stmt->error);
            }
        }
    }
}

// Set a session variable to trigger page reload and clear form inputs
$_SESSION['reload'] = true;

// Redirect back to the appropriate page (super_financial or staff_financial)
header("Location: $source_page");
exit();
?>
