<?php
include('assets/databases/dbconfig.php');

$type = $_GET['type'] ?? '';

// Default query for approved requests
if ($type === 'approved') {
    $approved_expense_query = "SELECT id, amount, description, approved_date FROM expense_approved";
    $approved_budget_query = "SELECT id, amount, description, approved_date FROM budget_approved";
    
    // Fetch approved expenses
    $approved_expenses = $connection->query($approved_expense_query);
    while ($row = $approved_expenses->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['amount']}</td>
                <td>{$row['description']}</td>
                <td>Approved</td>
                <td>{$row['approved_date']}</td>
                <td><span class='las la-eye' style='cursor: pointer'></span></td>
              </tr>";
    }
    
    // Fetch approved budget requests
    $approved_budgets = $connection->query($approved_budget_query);
    while ($row = $approved_budgets->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['amount']}</td>
                <td>{$row['description']}</td>
                <td>Approved</td>
                <td>{$row['approved_date']}</td>
                <td><span class='las la-eye' style='cursor: pointer'></span></td>
              </tr>";
    }
} 

// Default query for rejected requests
elseif ($type === 'rejected') {
    $rejected_expense_query = "SELECT id, amount, description, status, created_at FROM expense WHERE status = 'rejected'";
    $rejected_budget_query = "SELECT id, amount, description, status, created_at FROM budget_requests WHERE status = 'rejected'";

    // Fetch rejected expenses
    $rejected_expenses = $connection->query($rejected_expense_query);
    while ($row = $rejected_expenses->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['amount']}</td>
                <td>{$row['description']}</td>
                <td>Rejected</td>
                <td>{$row['created_at']}</td>
                <td><span class='las la-eye' style='cursor: pointer'></span></td>
              </tr>";
    }

    // Fetch rejected budget requests
    $rejected_budgets = $connection->query($rejected_budget_query);
    while ($row = $rejected_budgets->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['amount']}</td>
                <td>{$row['description']}</td>
                <td>Rejected</td>
                <td>{$row['created_at']}</td>
                <td><span class='las la-eye' style='cursor: pointer'></span></td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='5'>Invalid request type</td></tr>";
}
?>
