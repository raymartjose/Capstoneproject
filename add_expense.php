<?php
include "assets/databases/dbconfig.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $department = $_POST['department'];
    $employee_id = $_POST['employee'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $expense_date = $_POST['expense_date'];
    $description = $_POST['description'];

    // Get current month and year
    $currentMonth = date('m');
    $currentYear = date('Y');

    // Extract month and year from selected expense date
    $selectedMonth = date('m', strtotime($expense_date));
    $selectedYear = date('Y', strtotime($expense_date));

    // Check if the selected date is within the current month
    if ($selectedMonth != $currentMonth || $selectedYear != $currentYear) {
        echo "<script>alert('Expense date must be within the current month!'); window.history.back();</script>";
        exit();
    }

    // Check if there is an approved company budget for the month
    $budgetQuery = $connection->prepare("
        SELECT amount FROM company_budget 
        WHERE month = ? AND year = ?
    ");
    $budgetQuery->bind_param("ii", $currentMonth, $currentYear);
    $budgetQuery->execute();
    $result = $budgetQuery->get_result();
    $budgetData = $result->fetch_assoc();
    $totalBudget = $budgetData['amount'] ?? 0;

    if ($totalBudget <= 0) {
        echo "<script>alert('Expense cannot be saved. No company budget available for this month.'); window.history.back();</script>";
        exit();
    }

    // Check approved budget for the department
    $deptBudgetQuery = $connection->prepare("
        SELECT SUM(amount) AS approved_budget 
        FROM requests 
        WHERE request_type = 'budget_requests' 
        AND status = 'Approved' 
        AND department = ? 
        AND MONTH(created_at) = ? 
        AND YEAR(created_at) = ?
    ");
    $deptBudgetQuery->bind_param("sii", $department, $currentMonth, $currentYear);
    $deptBudgetQuery->execute();
    $result = $deptBudgetQuery->get_result();
    $budgetData = $result->fetch_assoc();
    $approvedBudget = $budgetData['approved_budget'] ?? 0;

    if ($approvedBudget <= 0) {
        echo "<script>alert('Expense cannot be saved. No approved budget for this department.'); window.history.back();</script>";
        exit();
    }

    // Insert into employee_expenses
    $insertQuery = $connection->prepare("
        INSERT INTO employee_expenses (employee_id, expense_date, category, description, amount, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $insertQuery->bind_param("ssssd", $employee_id, $expense_date, $category, $description, $amount);

    if ($insertQuery->execute()) {
        echo "<script>alert('Expense saved successfully!'); window.location.href='budget_report.php';</script>";
    } else {
        echo "<script>alert('Error saving expense. Please try again.'); window.history.back();</script>";
    }
}
?>
