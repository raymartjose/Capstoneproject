<?php
include ('assets/databases/dbconfig.php');

$type = $_GET['type'] ?? '';
// Filter out 'Returned' requests unless needed explicitly


if ($type === "budget") {
    $query = "SELECT id, amount, description, status, created_at FROM budget_requests WHERE status = 'Returned'";
} elseif ($type === "expense") {
    $query = "SELECT id, amount, description, status, created_at FROM expense WHERE status = 'Returned'";
} else {
    echo "<tr><td colspan='4'>Invalid request type</td></tr>";
    exit;
}

$result = $connection->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['amount']}</td>
                <td>{$row['description']}</td>
                <td>{$row['status']}</td>
                <td>{$row['created_at']}</td>
                <td><span class='las la-eye' style='cursor: pointer' onclick='loadRequestDetails({$row['id']}, \"{$type}\")'></span></td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='4'>No pending requests found.</td></tr>";
}
?>
