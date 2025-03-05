<?php
include "assets/databases/dbconfig.php";

$role = isset($_GET['role']) ? $_GET['role'] : 'staff';
$query = "SELECT id, name, email, status, role FROM users WHERE role = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("s", $role);
$stmt->execute();
$result = $stmt->get_result();

$output = "";
if ($result->num_rows > 0) {
    while ($user = $result->fetch_assoc()) {
        $output .= "<tr>";
        $output .= "<td>" . htmlspecialchars($user['id']) . "</td>";
        $output .= "<td>" . htmlspecialchars($user['name']) . "</td>";
        $output .= "<td>" . htmlspecialchars($user['email']) . "</td>";
        $output .= "<td>" . htmlspecialchars($user['role']) . "</td>";
        $output .= "<td>" . htmlspecialchars($user['status']) . "</td>";
        $output .= "<td>
                                                        <button class='btn-edit las la-edit' 
                                                            onclick=\"openEditModal(
                                                                '" . htmlspecialchars($user['id']) . "',
                                                                '" . htmlspecialchars(addslashes($user['name'])) . "',
                                                                '" . htmlspecialchars(addslashes($user['email'])) . "',
                                                                '" . htmlspecialchars($user['role']) . "',
                                                                '" . htmlspecialchars($user['status']) . "'
                                                            )\">
                                                        </button>
                                                    </td>";
        $output .= "</tr>";
    }
} else {
    $output .= "<tr><td colspan='3'>No users found</td></tr>";
}



$stmt->close();
$connection->close();
?>
