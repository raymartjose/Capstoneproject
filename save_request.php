<?php
session_start();
include "assets/databases/dbconfig.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $connection->begin_transaction();

        // Insert into requests table
        $stmt = $connection->prepare("INSERT INTO requests (request_type, department, staff_name, staff_id, position, amount, description, status, created_at) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
        $stmt->execute([
            $_POST["request_type"], 
            $_POST["department"], 
            $_POST["staff_name"], 
            $_POST["staff_id"], 
            $_POST["position"], 
            $_POST["amount"], 
            $_POST["description"]
        ]);

        // Get the last inserted request ID
        $request_id = $connection->insert_id;

        // Log initial status in request_transactions table
        $stmt = $connection->prepare("INSERT INTO request_transactions (request_id, status, changed_by, changed_at, remarks) 
                                      VALUES (?, 'Pending', ?, NOW(), ?)");
        $stmt->execute([$request_id, $_SESSION['name'], 'Initial request submission']);

        // Handle file uploads
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
                    $stmt->execute([$request_id, $name, $filePath, $fileCategory, $fileRemark]);
                }
            }
        }

        // Handle remarks if provided
        if (!empty($_POST['remarks'])) {
            foreach ($_POST['remarks'] as $remark) {
                if (!empty(trim($remark))) {
                    $stmt = $connection->prepare("INSERT INTO remarks (request_id, user_name, remark, created_at) 
                                                  VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$request_id, $_SESSION['name'], $remark]);
                }
            }
        }

        $connection->commit();
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        $connection->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
?>
