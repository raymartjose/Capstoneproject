<?php
// Include database connection
include('assets/databases/dbconfig.php');

// Get the type of products requested (all, available, rented)
$type = $_GET['type'];
$limit = 5; // Limit for the table to show 5 rows at a time

// Query based on the selected type
if ($type == 'all') {
    $productQuery = "SELECT * FROM products ORDER BY created_at DESC LIMIT $limit";
} elseif ($type == 'available') {
    $productQuery = "SELECT * FROM products WHERE status = 'available' ORDER BY created_at DESC LIMIT $limit";
} elseif ($type == 'rented') {
    $productQuery = "SELECT * FROM products WHERE status = 'rented' ORDER BY created_at DESC LIMIT $limit";
} else {
    echo 'Invalid type';
    exit;
}

$productResult = mysqli_query($connection, $productQuery);

if (mysqli_num_rows($productResult) > 0) {
    // Start table structure
    echo '<table width="100%">
            <thead>
                <tr>
                    <td>Product Name</td>
                    <td>Category</td>
                    <td>Purchase Cost</td>
                    <td>Daily Rate</td>
                    <td>Status</td>
                </tr>
            </thead>
            <tbody>';

    // Loop through the products and display them in the table
    while ($row = mysqli_fetch_assoc($productResult)) {
        echo "<tr>
                <td>" . htmlspecialchars($row['name']) . "</td>
                <td>" . htmlspecialchars($row['category']) . "</td>
                <td>₱" . number_format($row['purchase_cost'], 2) . "</td>
                <td>₱" . number_format($row['daily_rate'], 2) . "</td>
                <td>" . htmlspecialchars($row['status']) . "</td>
              </tr>";
    }

    // End table
    echo '</tbody>
        </table>';
} else {
    echo 'No products found';
}
?>
