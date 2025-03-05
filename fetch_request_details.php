<?php
include('assets/databases/dbconfig.php');

// Retrieve request ID and category from the URL parameters
$requestId = $_GET['id'] ?? 0; // Default to 0 if 'id' is not provided
$category = $_GET['category'] ?? ''; // Default to an empty string if 'category' is not set

// Check if the category is valid and fetch the data
if ($category == 'budget') {
    // Query for the budget_requests table
    $query = "SELECT * FROM budget_requests WHERE id = $requestId";
    $result = $connection->query($query);

    if ($result->num_rows > 0) {
        $request = $result->fetch_assoc();
        echo "<p><strong>Budget Request ID:</strong> {$request['id']}</p>";
        echo "<p><strong>Amount:</strong> {$request['amount']}</p>";
        echo "<p><strong>Description:</strong> {$request['description']}</p>";
        echo "<p><strong>Status:</strong> {$request['status']}</p>";
        echo "<p><strong>Date Created:</strong> {$request['created_at']}</p>";
    } else {
        echo "<p>No details found for this budget request.</p>";
    }

    $result->free(); // Free the result set
} elseif ($category == 'expense') {
    // Query for the expense table
    $query = "SELECT * FROM expense WHERE id = $requestId";
    $result = $connection->query($query);

    if ($result->num_rows > 0) {
        $expense = $result->fetch_assoc();
        echo "<p><strong>Expense ID:</strong> {$expense['id']}</p>";
        echo "<p><strong>Amount:</strong> {$expense['amount']}</p>";
        echo "<p><strong>Description:</strong> {$expense['description']}</p>";
        echo "<p><strong>Status:</strong> {$expense['status']}</p>";
        echo "<p><strong>Date Created:</strong> {$expense['created_at']}</p>";
    } else {
        echo "<p>No details found for this expense.</p>";
    }

    $result->free(); // Free the result set
} else {
    echo "<p>Invalid category selected. Please check the URL and try again.</p>";
}

$connection->close(); // Close the connection
?>
