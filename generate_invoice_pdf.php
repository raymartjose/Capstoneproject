<?php
session_start();
require_once('tcpdf/tcpdf.php');

include('assets/databases/dbconfig.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $invoice_id = intval($_GET['id']);
    $query = "SELECT i.*, c.name AS customer_name, c.email, c.phone, c.address,
                 p.name AS product_name, p.category, p.purchase_cost, p.daily_rate, 
                 p.brand, p.model, p.color, p.plate_number, p.status AS product_status
          FROM invoices i
          JOIN customers c ON i.customer_id = c.id
          JOIN products p ON i.product_id = p.id
          WHERE i.id = ?";

    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $invoice_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($invoice = mysqli_fetch_assoc($result)) {
        $rental_days = (strtotime($invoice['due_date']) - strtotime($invoice['issue_date'])) / (60 * 60 * 24);
        $daily_rate = floatval($invoice['daily_rate']);
        $sub_total = $rental_days * $daily_rate;
        $surcharges = floatval($invoice['surcharges']);
        $discount_amount = floatval($invoice['discount_amount']);
        $tax_rate = floatval($invoice['tax_rate']);
        $tax_amount = ($tax_rate / 100) * $sub_total;
        $total_amount = ($sub_total + $surcharges + $tax_amount) - $discount_amount;

        // Create a new TCPDF object
        $pdf = new TCPDF('P', 'mm', 'A4');
        $pdf->AddPage();

        // Set title
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Invoice', 0, 1, 'C');

        // Set font for details
        $pdf->SetFont('Helvetica', '', 12);
        
        // Add company info
        $pdf->Cell(0, 10, "CBBE Crane & Trucking Rental Services", 0, 1);
        $pdf->Cell(0, 10, "2263 Beata Street, Pandacan, Manila", 0, 1);
        $pdf->Cell(0, 10, "Email: accounting@cbbe-inc.com | inquire@cbbe-inc.com", 0, 1);
        $pdf->Cell(0, 10, "Phone: +63 (2) 8 564 9080", 0, 1);

        // Add invoice details
        $pdf->Cell(0, 10, "Invoice ID: #" . $invoice_id, 0, 1);
        $pdf->Cell(0, 10, "Status: " . ucfirst($invoice['payment_status']), 0, 1);
        $pdf->Cell(0, 10, "Invoice Date: " . date("F j, Y", strtotime($invoice['issue_date'])), 0, 1);
        $pdf->Cell(0, 10, "Due Date: " . date("F j, Y", strtotime($invoice['due_date'])), 0, 1);

        // Add billing info
        $pdf->Cell(0, 10, "Billed To:", 0, 1);
        $pdf->Cell(0, 10, $invoice['customer_name'], 0, 1);
        $pdf->Cell(0, 10, $invoice['address'], 0, 1);
        $pdf->Cell(0, 10, $invoice['email'], 0, 1);
        $pdf->Cell(0, 10, $invoice['phone'], 0, 1);

        // Add product info
        $pdf->Cell(0, 10, "Product: " . $invoice['product_name'], 0, 1);
        $pdf->Cell(0, 10, "Plate Number: " . $invoice['plate_number'], 0, 1);
        $pdf->Cell(0, 10, "Daily Rate: " . $invoice['daily_rate'], 0, 1);
        $pdf->Cell(0, 10, "Rental Days: " . $rental_days, 0, 1);
        
        // Add pricing details
        $pdf->Cell(0, 10, "Sub Total: " . number_format($sub_total, 2), 0, 1);
        $pdf->Cell(0, 10, "Tax (" . $tax_rate . "%): " . number_format($tax_amount, 2), 0, 1);
        $pdf->Cell(0, 10, "Surcharges: " . number_format($surcharges, 2), 0, 1);
        $pdf->Cell(0, 10, "Discount: -" . number_format($discount_amount, 2), 0, 1);
        $pdf->Cell(0, 10, "Total Amount: " . number_format($total_amount, 2), 0, 1);

        // Output the PDF
        $pdf->Output('invoice_' . $invoice_id . '.pdf', 'I');
    } else {
        echo "<script>alert('Invoice not found'); window.location.href='invoice.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('Invalid invoice ID'); window.location.href='invoice.php';</script>";
    exit();
}
?>
