<?php
include('assets/databases/dbconfig.php');

// Function to get the total assets
function getTotalAssets() {
    global $connection;
    $query = "SELECT SUM(value) AS total_assets FROM assets";
    $result = mysqli_query($connection, $query);
    if ($result) {
        $data = mysqli_fetch_assoc($result);
        return $data['total_assets'];
    }
    return 0;
}

// Function to get the total liabilities
function getTotalLiabilities() {
    global $connection;
    $query = "SELECT SUM(amount) AS total_liabilities FROM liabilities";
    $result = mysqli_query($connection, $query);
    if ($result) {
        $data = mysqli_fetch_assoc($result);
        return $data['total_liabilities'];
    }
    return 0;
}

// Get total assets and liabilities
$totalAssets = getTotalAssets();
$totalLiabilities = getTotalLiabilities();
?>
