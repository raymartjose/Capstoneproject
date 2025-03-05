<?php
include "assets/databases/dbconfig.php";

$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m'); // Default to current month
$startDate = $month . "-01";
$endDate = date("Y-m-t", strtotime($startDate)); // Get last day of the selected month

$sql = "SELECT 
            e.department, 
            COUNT(DISTINCT e.id) AS employee_count, 
            COUNT(p.id) AS payroll_count, 
            SUM(p.gross_pay) AS total_gross_pay, 
            SUM(p.net_pay) AS total_net_pay 
        FROM employees e
        LEFT JOIN payroll p ON e.employee_id = p.employee_id 
        WHERE p.pay_period_start BETWEEN ? AND ?
        GROUP BY e.department";

$stmt = $connection->prepare($sql);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$chartData = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $chartData[] = $row;
    }
}

echo json_encode($chartData);
$stmt->close();
$connection->close();
?>
