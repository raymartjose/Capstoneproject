<?php
// Include database connection
session_start();
include('assets/databases/dbconfig.php');

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = $_POST['name'];
    $category = $_POST['category'];
    $purchase_cost = $_POST['purchase_cost'];
    $daily_rate = $_POST['daily_rate'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $color = $_POST['color'];
    $plate_number = $_POST['plate_number'];
    $product_status = $_POST['product_status']; // Get the product status (paid or pending)

    // Prepare SQL query to insert data into products table
    $sql = "INSERT INTO products (name, category, purchase_cost, daily_rate, brand, model, color, plate_number, product_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepare statement
    if ($stmt = $connection->prepare($sql)) {
        // Bind parameters to the SQL query
        $stmt->bind_param("ssddsssss", $name, $category, $purchase_cost, $daily_rate, $brand, $model, $color, $plate_number, $product_status);

        // Execute the statement
        if ($stmt->execute()) {
            // Get the last inserted product ID
            $product_id = $stmt->insert_id;

            // Audit log for the add product action
            $currentUserId = $_SESSION['user_id']; // Logged-in user ID

            // Prepare audit log data
            $action = "Added";
            $recordType = "Product";
            $recordId = $product_id; // Get the ID of the newly added product
            $oldDataJson = json_encode([]); // No previous data since it's an add action
            $newData = [
                'name' => $name,
                'category' => $category,
                'purchase_cost' => $purchase_cost,
                'daily_rate' => $daily_rate,
                'brand' => $brand,
                'model' => $model,
                'color' => $color,
                'plate_number' => $plate_number,
                'product_status' => $product_status
            ];
            $newDataJson = json_encode($newData); // Convert new data to JSON format

            // Insert the action into the audit logs
            $auditQuery = "INSERT INTO audit_logs (user_id, action, record_type, record_id, old_data, new_data) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $auditStmt = $connection->prepare($auditQuery);
            $auditStmt->bind_param("ississ", $currentUserId, $action, $recordType, $recordId, $oldDataJson, $newDataJson);
            $auditStmt->execute();

            // Insert into COGS table
            $cogs_sql = "INSERT INTO cogs (product_id, depreciation, maintenance, total_cogs) 
                         VALUES (?, 0.0, 0.0, ?)";
            if ($cogs_stmt = $connection->prepare($cogs_sql)) {
                $cogs_stmt->bind_param("id", $product_id, $purchase_cost);
                if ($cogs_stmt->execute()) {
                    echo "Product added successfully, and COGS record inserted!";
                } else {
                    echo "Error inserting into COGS table: " . $cogs_stmt->error;
                }
                $cogs_stmt->close();
            } else {
                echo "Error preparing COGS query: " . $connection->error;
            }

            // Handle the assets or liabilities based on product status
            // Always insert into assets table
$asset_name = ($name == 'Truck' || $name == 'Vehicle') ? 'Truck' : $name; // Set to "Truck" or "Vehicle" if applicable
$assets_sql = "INSERT INTO assets (asset_name, value, type) 
               VALUES (?, ?, 'Equipment')";

if ($assets_stmt = $connection->prepare($assets_sql)) {
    $assets_stmt->bind_param("sd", $asset_name, $purchase_cost);
    if ($assets_stmt->execute()) {
        echo "Asset record inserted!";
    } else {
        echo "Error inserting into Assets table: " . $assets_stmt->error;
    }
    $assets_stmt->close();
} else {
    echo "Error preparing Assets query: " . $connection->error;
}

// Always insert into Chart of Accounts as an Asset
function getAccountCode($connection, $account_name, $category) {
    // Check if the account already exists
    $checkQuery = "SELECT account_code FROM chart_of_accounts WHERE account_name = ? AND category = ?";
    if ($checkStmt = $connection->prepare($checkQuery)) {
        $checkStmt->bind_param("ss", $account_name, $category);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            // Fetch the existing account_code
            $checkStmt->bind_result($existingAccountCode);
            $checkStmt->fetch();
            $checkStmt->close();
            return $existingAccountCode;
        }
        $checkStmt->close();
    }

    // If no existing account, generate a new account_code
    $newAccountCode = strtoupper(substr($account_name, 0, 3)) . rand(100, 999); // Example: "EQU123"
    return $newAccountCode;
}

// Get or create account_code for Equipment (Asset)
$equipmentAccountCode = getAccountCode($connection, 'Equipment', 'Asset');

// Check if Equipment account exists
$checkCoaQuery = "SELECT balance FROM chart_of_accounts WHERE account_name = 'Equipment' AND category = 'Asset'";
$checkCoaStmt = $connection->prepare($checkCoaQuery);
$checkCoaStmt->execute();
$checkCoaStmt->store_result();

if ($checkCoaStmt->num_rows > 0) {
    // Update balance if account exists
    $updateCoaQuery = "UPDATE chart_of_accounts SET balance = balance + ? WHERE account_name = 'Equipment' AND category = 'Asset'";
    $updateCoaStmt = $connection->prepare($updateCoaQuery);
    $updateCoaStmt->bind_param("d", $purchase_cost);
    $updateCoaStmt->execute();
    $updateCoaStmt->close();
} else {
    // Insert new Equipment account if not exists
    $insertCoaQuery = "INSERT INTO chart_of_accounts (account_code, account_name, category, balance) 
                       VALUES (?, 'Equipment', 'Asset', ?)";
    $insertCoaStmt = $connection->prepare($insertCoaQuery);
    $insertCoaStmt->bind_param("sd", $equipmentAccountCode, $purchase_cost);
    $insertCoaStmt->execute();
    $insertCoaStmt->close();
}
$checkCoaStmt->close();





// If status is "pending", insert into liabilities
if ($product_status == 'pending') {
    $liability_name = "Loans Payable"; 
    $liabilities_sql = "INSERT INTO liabilities (liability_name, amount, due_date) 
                        VALUES (?, ?, CURDATE())"; // Due date set to today

    if ($liabilities_stmt = $connection->prepare($liabilities_sql)) {
        $liabilities_stmt->bind_param("sd", $liability_name, $purchase_cost);
        if ($liabilities_stmt->execute()) {
            echo "Liability record inserted!";
        } else {
            echo "Error inserting into Liabilities table: " . $liabilities_stmt->error;
        }
        $liabilities_stmt->close();
    } else {
        echo "Error preparing Liabilities query: " . $connection->error;
    }

    // Get or create account_code for Liability
    $liabilityAccountCode = getAccountCode($connection, $liability_name, 'Liability');

    // Check if Liability account exists
    $checkLiabilityQuery = "SELECT balance FROM chart_of_accounts WHERE account_name = ? AND category = 'Liability'";
    $checkLiabilityStmt = $connection->prepare($checkLiabilityQuery);
    $checkLiabilityStmt->bind_param("s", $liability_name);
    $checkLiabilityStmt->execute();
    $checkLiabilityStmt->store_result();

    if ($checkLiabilityStmt->num_rows > 0) {
        // Update balance if account exists
        $updateLiabilityQuery = "UPDATE chart_of_accounts SET balance = balance + ? WHERE account_name = ? AND category = 'Liability'";
        $updateLiabilityStmt = $connection->prepare($updateLiabilityQuery);
        $updateLiabilityStmt->bind_param("ds", $purchase_cost, $liability_name);
        $updateLiabilityStmt->execute();
        $updateLiabilityStmt->close();
    } else {
        // Insert new Liability account if not exists
        $insertLiabilityQuery = "INSERT INTO chart_of_accounts (account_code, account_name, category, balance) 
                                 VALUES (?, ?, 'Liability', ?)";
        $insertLiabilityStmt = $connection->prepare($insertLiabilityQuery);
        $insertLiabilityStmt->bind_param("ssd", $liabilityAccountCode, $liability_name, $purchase_cost);
        $insertLiabilityStmt->execute();
        $insertLiabilityStmt->close();
    }
    $checkLiabilityStmt->close();
}


        } else {
            echo "Error: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    }

    // Close the database connection
    $connection->close();
}
?>
