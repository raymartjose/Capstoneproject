<?php
include('assets/databases/dbconfig.php');

// Update receivables aging report
$sql = "
    UPDATE receivables
    SET 
        current_amount = CASE 
            WHEN DATEDIFF(NOW(), due_date) <= 0 THEN total_due 
            ELSE 0 
        END,
        past_due_30 = CASE 
            WHEN DATEDIFF(NOW(), due_date) BETWEEN 1 AND 30 THEN total_due 
            ELSE 0 
        END,
        past_due_60 = CASE 
            WHEN DATEDIFF(NOW(), due_date) BETWEEN 31 AND 60 THEN total_due 
            ELSE 0 
        END,
        past_due_90 = CASE 
            WHEN DATEDIFF(NOW(), due_date) BETWEEN 61 AND 90 THEN total_due 
            ELSE 0 
        END,
        past_due_90plus = CASE 
            WHEN DATEDIFF(NOW(), due_date) > 90 THEN total_due 
            ELSE 0 
        END
";

if ($connection->query($sql) === TRUE) {
    echo "Aging report updated successfully.";
} else {
    echo "Error updating aging report: " . $connection->error;
}

$connection->close();
?>
