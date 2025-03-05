<?php
include "assets/databases/dbconfig.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "SELECT id, name, email, status, role FROM users WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }

    $stmt->close();
    $connection->close();
}
?>
