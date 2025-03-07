<!DOCTYPE html>
<html lang="en">
    <?php
    session_start();  // Start the session
    require_once('tcpdf/tcpdf.php');

    ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="icon" href="img/logo-sm.png">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel= "stylesheet" href= "https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css" >
</head>
<?php
include('assets/databases/dbconfig.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $invoice_id = intval($_GET['id']); // Convert to integer to prevent SQL injection

    // Proceed with fetching invoice details
    $query = "SELECT i.*, c.name AS customer_name, c.email, c.phone, c.address,
    p.name AS product_name, p.category, p.purchase_cost, p.daily_rate, 
    p.brand, p.model, p.color, p.plate_number, p.status AS product_status
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    JOIN products p ON i.product_id = p.id
    WHERE i.id = ?";


$stmt = mysqli_prepare($connection, $query);
if ($stmt === false) {
    die('Error preparing the query: ' . mysqli_error($connection));
}

mysqli_stmt_bind_param($stmt, "i", $invoice_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $invoice = mysqli_fetch_assoc($result);        // Calculate rental days
    $rental_days = (strtotime($invoice['due_date']) - strtotime($invoice['issue_date'])) / (60 * 60 * 24);
    $daily_rate = floatval($invoice['daily_rate']); // Ensure it's numeric
    $sub_total = $rental_days * $daily_rate; // Rental cost
    $surcharges = floatval($invoice['surcharges']);
    $discount_amount = floatval($invoice['discount_amount']);
    $tax_rate = floatval($invoice['tax_rate']); // Example: 12% tax
    $customer_name = htmlspecialchars($invoice['customer_name']);
        $email = htmlspecialchars($invoice['email']);
        $total_amount = number_format($invoice['total_amount'], 2);
        $tax_amount = number_format($invoice['tax_amount'], 2);


    // Compute the final total


    // Format values for display
    $sub_total = number_format($sub_total, 2);


        $customer_name = htmlspecialchars($invoice['customer_name']);
        $email = htmlspecialchars($invoice['email']);
        $phone = htmlspecialchars($invoice['phone']);
        $address = htmlspecialchars($invoice['address']);
        $product_name = htmlspecialchars($invoice['product_name']);
        $plate_number = htmlspecialchars($invoice['plate_number']);
        $discount_amount = htmlspecialchars($invoice['discount_amount']);
        $tax_rate = htmlspecialchars($invoice['tax_rate']);
        $surcharges = htmlspecialchars($invoice['surcharges']);
        $amount = number_format($invoice['amount'], 2);
        $status = ucfirst($invoice['payment_status']);
        $issue_date = date("F j, Y", strtotime($invoice['issue_date']));
        $due_date = date("F j, Y", strtotime($invoice['due_date']));
    } else {
        echo "<script>alert('Invoice not found'); window.location.href='invoice.php';</script>";
        exit();
    }
} else {
    echo "alert('Invalid invoice ID');";
    exit();
}

ob_start();

if (isset($_POST['download_pdf'])) {
    // Create a new PDF document in landscape mode
    $pdf = new TCPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    
    // Define color scheme (adjust based on your logo colors)
    $headerColor = [0, 102, 51]; // Dark Green
    $accentColor = [0, 153, 204]; // Blue
    $backgroundColor = [230, 230, 230]; // Light Gray
    $tableHeaderColor = [34, 45, 50]; // Dark Charcoal for table headers

    // Set font
    $pdf->SetFont('helvetica', '', 9);

    // Logo
    $logo_path = 'img/logo1.png';
    $pdf->Image($logo_path, 10, 19, 50, 15);
    
    // Company Information
    $pdf->SetY(20);
    $pdf->SetX(70);
    $pdf->SetTextColor($headerColor[0], $headerColor[1], $headerColor[2]);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCell(0, 5, "2263 Beata Street, Pandacan, Manila\nEmail: accounting@cbbe-inc.com | inquire@cbbe-inc.com\nPhone: +63 (2) 8 564 9080", 0, 'L');

    // Invoice Header
    $pdf->SetY(19);
    $pdf->SetX(200);
    $pdf->SetTextColor($accentColor[0], $accentColor[1], $accentColor[2]);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'INVOICE', 0, 1, 'R');
    
    // Invoice Info (Invoice ID, Status, Date)
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetX(200);
    $pdf->Cell(0, 6, 'Invoice ID: #' . $invoice_id, 0, 1, 'R');
    $pdf->SetX(200);
    $pdf->Cell(0, 6, 'Status: ' . $status, 0, 1, 'R');
    $pdf->SetX(200);
    $pdf->Cell(0, 6, 'Invoice Date: ' . $issue_date, 0, 1, 'R');
    $pdf->SetX(200);
    $pdf->Cell(0, 6, 'Due Date: ' . $due_date, 0, 1, 'R');

    $pdf->Ln(8);

    // Billed To Section
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor($backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
    $pdf->Cell(0, 6, 'Billed To:', 0, 1, 'L', true);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 6, $customer_name . "\n" . $address . "\n" . $email . "\n" . $phone, 0, 'L');
    $pdf->Ln(5);

    // Invoice Table
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor($tableHeaderColor[0], $tableHeaderColor[1], $tableHeaderColor[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetX(25);
    $pdf->Cell(80, 8, 'Product', 1, 0, 'L', true);
    $pdf->Cell(40, 8, 'Plate#', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Rate', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Days', 1, 0, 'C', true);
    $pdf->Cell(50, 8, 'Total', 1, 1, 'R', true);

    // Table Rows with alternating row colors
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    $rowColor = [255, 255, 255]; // White for alternating rows
    $pdf->SetX(25);
    $pdf->Cell(80, 8, $product_name, 1, 0, 'L', false);
    $pdf->Cell(40, 8, $plate_number, 1, 0, 'C', false);
    $pdf->Cell(40, 8, number_format((float) str_replace(',', '', $daily_rate), 2), 1, 0, 'C', false);
    $pdf->Cell(30, 8, $rental_days, 1, 0, 'C', false);
    $pdf->Cell(50, 8, number_format((float) str_replace(',', '', $sub_total), 2), 1, 1, 'R', false);

    // Surcharges, Discounts, Taxes, and Totals
    $pdf->SetX(185);
    $pdf->Cell(60, 7, 'Surcharges:', 1, 0, 'R');
    $pdf->Cell(20, 7, number_format((float) str_replace(',', '', $surcharges), 2), 1, 1, 'R');

    $pdf->SetX(185);
    $pdf->Cell(60, 7, 'Discount:', 1, 0, 'R');
    $pdf->Cell(20, 7, number_format((float) str_replace(',', '', $discount_amount), 2), 1, 1, 'R');

    $pdf->SetX(185);
    $pdf->Cell(60, 7, 'Tax (' . $tax_rate . '%):', 1, 0, 'R');
    $pdf->Cell(20, 7, number_format((float) str_replace(',', '', $tax_amount), 2), 1, 1, 'R');

    // Total Amount
    $pdf->SetX(185);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor($accentColor[0], $accentColor[1], $accentColor[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(60, 8, 'Total Amount:', 1, 0, 'R', true);
    $pdf->Cell(20, 8, number_format((float) str_replace(',', '', $total_amount), 2), 1, 1, 'R', true);

    // Footer Message and Legal Disclaimer
    $pdf->SetY(-30); // Position just above the page bottom
    $pdf->SetX(10);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(150, 150, 150); // Light gray text color
    $footerMessage = "Thank you for your business! For any inquiries, feel free to contact us.";
    $pdf->MultiCell(0, 5, $footerMessage, 0, 'C');

    // Legal Disclaimer
    $legalDisclaimer = "Legal Disclaimer: All rental services are subject to C.B. Barangay Enterprises Towing and Trucking Services Inc. (CBBE) terms and conditions. Please refer to our website www.cbbe-inc.com for more details.";
    $pdf->MultiCell(0, 5, $legalDisclaimer, 0, 'C');

    // Output PDF
    $pdf->Output('invoice_' . $invoice_id . '.pdf', 'D');
    exit();
}

ob_end_clean();

?>

<body>
    <style>
        form {
    display: inline-block;
    margin-right: 5px; /* Adjust spacing between buttons */
}

a.btn {
    display: inline-block;
}

/* Invoice Main Container */
.d2c_main {
    background-color: #ffffff;
    border-radius: 8px;
    padding: 16px;
}

/* Invoice Card */
.d2c_invoice {
    background: #ffffff;
    border-radius: 8px;
    box-shadow: none;
    overflow: hidden;
}

/* Invoice Header */
.d2c_invoice_header {
    background: #f9f9f9;
    padding: 20px;
    border-bottom: 2px solid #ccc;
}
.d2c_invoice_header .row {
    align-items: center;
}
.d2c_invoice_header p {
    margin-bottom: 4px;
    font-size: 14px;
    font-weight: 500;
}
.d2c_invoice_header img {
    width: 60px;
    height: 60px;
    object-fit: contain;
}

/* Invoice Details Alignment */
.d2c_invoice_header .text-md-end {
    text-align: right;
}
@media (max-width: 768px) {
    .d2c_invoice_header .text-md-end {
        text-align: left;
        margin-top: 10px;
    }
}

/* Invoice Content */
.d2c_invoice_content{
    padding: 20px;
}
.d2c_invoice_content p {
    color: #333;
    font-size: 14px;
    margin-bottom: 6px;
}

/* Buttons */
.d2c_invoice_content .btn {
    padding: 10px 15px;
            border: none;
            background-color: #ed6978;
            color: white;
            cursor: pointer;
            border-radius: 5px;
}
.d2c_print_btn {
    background: #777;
    color: #fff;
    border: none;
    transition: background 0.3s;
}


/* Invoice Table */
.d2c_invoice_list {
    margin-top: 16px;
    border-radius: 8px;
    overflow: hidden;
}
.d2c_invoice_list table {
    width: 100%;
    border-collapse: collapse;
}
.d2c_invoice_list th, .d2c_invoice_list td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    font-size: 14px;
}
.d2c_invoice_list th {
    background: #f1f1f1;
    text-transform: uppercase;
    font-weight: bold;
}
.d2c_invoice_list td:nth-child(2), 
.d2c_invoice_list td:nth-child(3), 
.d2c_invoice_list td:nth-child(4) {
    text-align: center;
}

/* Responsive Table */
@media (max-width: 768px) {
    .d2c_invoice_list table {
        font-size: 12px;
    }
    .d2c_invoice_list th, .d2c_invoice_list td {
        font-size: 12px;
        padding: 8px;
    }
}
.d2c_invoice_content.text-end {
    text-align: left;
}
.col-md-6 img {
    max-width: 100%; /* Ensures image doesn't overflow */
    height: auto; /* Maintains aspect ratio */
    width: 200px; /* Adjust size as needed */
    object-fit: contain; /* Ensures the whole image is visible */
}

@media (max-width: 768px) {
    .col-md-6 img {
        width: 120px; /* Smaller size on mobile */
    }
}


    </style>

<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect to login if not logged in
    exit();
}

$user_name = $_SESSION['name'];  // User name from session
$user_role = $_SESSION['role_display'];  // User role from session

?>

<input type="checkbox" id="nav-toggle">
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="img/logo1.png" alt="">
    </div>

    <div class="sidebar-menu">
    <ul>
        <li>
                <a href="analytics.php" class="active"><span class="las la-tachometer-alt"></span>
                <span>Dashboard</span></a>
            </li>
            <li>
            <a href="super_financial.php"><span class="las la-balance-scale"></span>
            <span>Financial Request</span></a>
</li>
<li class="submenu">
            <a href="#"><span class="las la-sitemap"></span>
            <span>Financial Reports</span></a>
            <ul class="submenu-items">
                <li><a href="coa.php"><span class="las la-folder"></span> Chart of Accounts</a></li>
                <li><a href="balance_sheet.php"><span class="las la-chart-line"></span> Balance Sheet</a></li>
                <li><a href="account_receivable.php"><span class="las la-file-invoice"></span> Accounts Receivable</a></li>
            </ul>
        </li>
        <li>
                <a href="index.php"><span class="las la-file-invoice"></span>
                <span>Payroll</span></a>
            </li>
            <li>
            <a href="add_user.php"><span class="las la-users"></span>
                <span>User Management</span></a>
            </li>
            <li>
                <a href="audit_log.php"><span class="las la-file-invoice"></span>
                <span>Audit Logs</span></a>
            </li>
        </ul>
    </div>
</div>


    <div class="main-content">
        <header>
            <div class="header-title">
                <h2>
                <label for="nav-toggle">
                    <span class="las la-bars"></span>
                </label>
                Invoice
                </h2>
                </div>

                <div class="user-wrapper">

                <span class="las la-bell" id="notification-bell" style="cursor:pointer; position:relative;">
        <span id="overdue-count" style="
            position: absolute;
            top: 0;
            right: 0;
            background: red;
            color: white;
            border-radius: 50%;
            padding: 5px;
            font-size: 12px;
            display: none;">
        </span>
    </span>

    <div class="user-info">
    <h4><?php echo htmlspecialchars($user_name); ?></h4>
    <small><?php echo htmlspecialchars($user_role); ?></small>
    <div class="dropdown">
        <button class="settings-btn">
            <span class="las la-cog"></span>
        </button>
        <div class="dropdown-content">
        <a href="#" id="openChangePasswordModal"><span class="las la-key"></span> Change Password</a>
            <a href="logout.php"><span class="las la-sign-out-alt"></span> Logout</a>
        </div>
    </div>
</div>
                </div>
        </header>
        <style>
 /* Hide modal initially with smooth fade-in effect */
#changePasswordModal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

/* Show modal smoothly */
#changePasswordModal.show {
    display: block;
    opacity: 1;
}

/* Center modal content */
.modal-dialog {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 420px;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0px 10px 25px rgba(0, 0, 0, 0.3);
    transition: transform 0.3s ease-in-out;
}

/* When modal is opened, add a small bounce effect */
#changePasswordModal.show .modal-dialog {
    transform: translate(-50%, -50%) scale(1.05);
}

/* Modal Header */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.modal-title {
    font-size: 20px;
    font-weight: bold;
    color: #333;
}

/* Close Button */
.close {
    font-size: 22px;
    font-weight: bold;
    background: none;
    border: none;
    cursor: pointer;
    transition: color 0.3s;
}

.close:hover {
    color: red;
}

/* Modal Body */
.modal-body {
    margin-top: 15px;
}

/* Form Styles */
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    font-weight: bold;
    color: #555;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 16px;
}

/* Submit Button */
.btn-primary {
    width: 100%;
    padding: 10px;
    font-size: 16px;
    border-radius: 6px;
    background: #ed6978;
    border: none;
    color: white;
    transition: background 0.3s;
    cursor: pointer;
}

.btn-primary:hover {
    background: #d1697b;
}

/* Responsive */
@media screen and (max-width: 480px) {
    .modal-dialog {
        width: 90%;
        padding: 20px;
    }
}

        </style>
<!-- Change Password Modal -->
<div id="changePasswordModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Change Password</h5>
                        <button type="button" class="close" id="closeModal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="changePasswordForm" method="POST" action="change_password.php">
                            <div class="form-group">
                                <label for="currentPassword">Current Password</label>
                                <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label for="newPassword">New Password</label>
                                <input type="password" class="form-control" id="newPassword" name="new_password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <main>



        <div class="d2c_main p-4 ps-lg-3"> 
    <div class="card d2c_invoice">
        <!-- Invoice Header -->
        <div class="d2c_invoice_header p-4">
            <div class="row align-items-center">
                <!-- Logo and Address -->
                <div class="col-md-6 d-flex align-items-center order-1 order-md-1">
                    <img class="me-3" src="img/logo1.png" alt="CBBE Crane & Trucking Rental Services">
                    <div>
                        <p class="mb-1 fw-bold">2263 Beata Street, Pandacan, Manila</p>
                        <p class="mb-1">Email: accounting@cbbe-inc.com | inquire@cbbe-inc.com</p>
                        <p class="mb-0">Phone: +63 (2) 8 564 9080</p>
                    </div>
                </div>
                <!-- Invoice Details -->
                <div class="col-md-6 text-md-end">
                    <p class="mb-2"><span class="fw-semibold me-1">Invoice ID:</span> #<?php echo $invoice_id; ?></p>
                    <p class="mb-2"><span class="fw-semibold me-1">Status:</span> <span class="<?php echo ($status == 'Paid') ? 'text-success' : 'text-danger'; ?> fw-bold"><?php echo $status; ?></span></p>
                    <p class="mb-2"><span class="fw-semibold me-1">Invoice Date:</span> <?php echo $issue_date; ?></p>
                    <p class="mb-0"><span class="fw-semibold me-1">Due Date:</span> <?php echo $due_date; ?></p>
                </div>
            </div>
            
        </div>
        
        <!-- Invoice Body -->
        <div class="card-body pt-0">
    <div class="d2c_invoice_body">
        <div class="row">
            <!-- Billing Info -->
            <div class="col-md-6 col-xl">
                <div class="d2c_invoice_content text-end">
                    <h3 class="mb-2"><span class="fw-semibold me-1">Billed To:</span></h3>
                    <p class="mb-2"><?php echo $customer_name; ?></p>
                    <p class="mb-2"><?php echo $address; ?></p>
                    <p class="mb-2"><?php echo $email; ?></p>
                    <p class="mb-0"><?php echo $phone; ?></p>
                </div>
            </div>
           
        </div>
    </div>
</div>


                <!-- Invoice List -->
                <div class="d2c_invoice_list card table-responsive mt-4">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th style="min-width: 210px;">Product</th>
                                <th style="min-width: 50px;">Plate#</th>
                                <th style="min-width: 100px;">Daily Rate</th>
                                <th style="min-width: 100px;">Rental Days</th>
                                <th class="text-end" style="min-width: 100px;">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td><?php echo $product_name; ?></td>
                                <td><?php echo $plate_number; ?></td>
                                <td><?php echo $daily_rate; ?></td>
                                <td><?php echo $rental_days; ?></td>
                                <td class="text-end"><?php echo $sub_total; ?></td>
                            </tr>

                            <tr class="text-end">
                            <td colspan="3"></td>
                            <td>Surcharges:</td>
                            <td class="text-end"><?php echo $surcharges; ?></td>
                            </tr>

                            <tr class="text-end">
                            <td colspan="3"></td>
                            <td>Discount:</td>
                            <td class="text-end"><?php echo $discount_amount; ?></td>
                            </tr>

                            <tr class="text-end">
                            <td colspan="3"></td>
                                <td>Tax(<?php echo $tax_rate; ?>%)</td>
                                <td class="text-end"><?php echo $tax_amount; ?></td>
                            </tr>
                            <tr class="text-end">
                            <th colspan="3"></th>
                                <th>Total</th>
                                <th class="text-end"><?php echo $total_amount; ?></th>
                            </tr>
                        </tbody>
                    </table>
</div>

                    <div class="col-md-6 col-xl d-flex align-items-end">
                <div class="d2c_invoice_content">
                
</form>
<form action="send_email_process.php" method="POST" target="_blank">
                <input type="hidden" name="payment_status" value="<?php echo $status; ?>"> <!-- Replace with dynamic invoice ID -->
    <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>"> <!-- Replace with dynamic invoice ID -->
    <input type="hidden" name="email" value="<?php echo $email; ?>"> <!-- Replace with dynamic email -->
    <input type="hidden" name="customer_name" value="<?php echo $customer_name; ?>"> <!-- Replace with dynamic name -->
    <input type="hidden" name="amount" value="<?php echo $total_amount; ?>"> <!-- Replace with dynamic amount -->
    <input type="hidden" name="message" value="Dear <?php echo $customer_name; ?>, your invoice #<?php echo $invoice_id; ?> is due. Amount: ₱<?php echo $total_amount; ?>"> <!-- Replace with dynamic message -->

    <button type="submit" class="btn d2c_print_btn rounded" target="_blank" name="send_method" value="gmail">
        Send via Gmail
    </button>
    
</form>
                    
                    <!--<a href="email.php?invoice_id=<?php echo $invoice_id; ?>&customer_name=<?php echo urlencode($customer_name); ?>&email=<?php echo urlencode($email); ?>&amount=<?php echo $total_amount; ?>" class="btn">Send Email</a>-->
                    <form method="POST">
            <button type="submit" name="download_pdf" class="btn btn-primary">Download PDF</button>
        </form>
        <a href="create_invoice.php?invoice_id=<?php echo $invoice_id; ?>" class="btn d2c_edit_btn rounded">Edit</a>

                </div>
            </div>
                </div>
            </div>
        </div>
    </div>
</div>


        <!-- End:Main Body -->
        </main>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
document.addEventListener("DOMContentLoaded", function() {
    const bellIcon = document.getElementById("notification-bell");
    const pendingCount = document.getElementById("pending-count");

    // Create notification dropdown
    const notificationDropdown = document.createElement("div");
    notificationDropdown.id = "notification-dropdown";
    notificationDropdown.style.cssText = `
        display: none;
        position: absolute;
        top: 50px;
        right: 0;
        background: #fff;
        width: 340px;
        box-shadow: 0px 5px 15px rgba(0,0,0,0.2);
        border-radius: 10px;
        padding: 10px;
        z-index: 1000;
        font-family: 'Inter', Arial, sans-serif;
        animation: fadeIn 0.3s ease-in-out;
    `;

    const arrow = document.createElement("div");
    arrow.style.cssText = `
        position: absolute;
        top: -10px;
        right: 15px;
        width: 0;
        height: 0;
        border-left: 10px solid transparent;
        border-right: 10px solid transparent;
        border-bottom: 10px solid white;
    `;
    notificationDropdown.appendChild(arrow);

    const header = document.createElement("div");
    header.innerHTML = "<strong style='font-size: 16px; color: #222;'>Notifications</strong>";
    header.style.cssText = `
        padding: 12px;
        border-bottom: 1px solid #ddd;
        font-size: 15px;
        font-weight: 600;
        color: #222;
    `;
    notificationDropdown.appendChild(header);

    bellIcon.appendChild(notificationDropdown);

    function timeAgo(timestamp) {
    const now = new Date();
    const past = new Date(timestamp);
    const diff = Math.floor((now - past) / 1000);

    if (diff < 60) return `${diff} sec${diff !== 1 ? "s" : ""} ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)} min${Math.floor(diff / 60) !== 1 ? "s" : ""} ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} hour${Math.floor(diff / 3600) !== 1 ? "s" : ""} ago`;
    if (diff < 2592000) return `${Math.floor(diff / 86400)} day${Math.floor(diff / 86400) !== 1 ? "s" : ""} ago`;
    return past.toLocaleDateString(); // Fallback to date format
}


    function fetchNotifications() {
        fetch('super_fetch_notifications.php')
        .then(response => response.json())
        .then(data => {
            notificationDropdown.innerHTML = "";
            notificationDropdown.appendChild(arrow);
            notificationDropdown.appendChild(header);

            if (data.length > 0) {
                data.forEach(item => {
                    let notificationItem = document.createElement("div");
                    notificationItem.innerHTML = `
    <a href="request_form.php?id=${item.id}" class="notification-item" data-id="${item.id}" style="
        text-decoration: none;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px;
        border-bottom: 1px solid #eee;
        transition: background 0.2s;
        font-size: 14px;
    " onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='transparent'">
        <div style="flex-grow: 1;">
            <strong style="font-size: 15px; color: #222;">Request #${item.id}</strong><br>
            <span style="color: #555; font-size: 14px;">${item.request_type} request from ${item.staff_name} (${item.department})</span><br>
            <span style="
                display: inline-block;
                background: orange;
                color: white;
                font-size: 12px;
                font-weight: bold;
                padding: 3px 8px;
                border-radius: 5px;
                margin-top: 5px;
            ">Pending</span><br>
            <span style="color: #777; font-size: 12px;">${timeAgo(item.created_at)}</span>
        </div>
    </a>
`;
                    notificationDropdown.appendChild(notificationItem);
                });

                pendingCount.innerText = data.length;
                pendingCount.style.display = data.length > 0 ? "inline-block" : "none";
            } else {
                notificationDropdown.innerHTML += "<p style='text-align:center; padding: 20px; font-size: 14px; color: #555;'>No pending requests</p>";
                pendingCount.style.display = "none";
            }
        });
    }

    bellIcon.addEventListener("click", function(event) {
        event.stopPropagation();
        fetchNotifications();
        notificationDropdown.style.display = (notificationDropdown.style.display === "block") ? "none" : "block";
    });

    document.addEventListener("click", function(event) {
        if (!bellIcon.contains(event.target)) {
            notificationDropdown.style.display = "none";
        }
    });

    fetchNotifications();
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const openModalBtn = document.getElementById("openChangePasswordModal");
    const closeModalBtn = document.getElementById("closeModal");
    const modal = document.getElementById("changePasswordModal");

    if (openModalBtn && closeModalBtn && modal) {
        openModalBtn.addEventListener("click", function(event) {
            event.preventDefault(); // Prevent default anchor action
            modal.classList.add("show"); // Add 'show' class to display modal
        });

        closeModalBtn.addEventListener("click", function() {
            modal.classList.remove("show"); // Remove 'show' class to hide modal
        });

        // Close modal when clicking outside the modal dialog
        window.addEventListener("click", function(event) {
            if (event.target === modal) {
                modal.classList.remove("show");
            }
        });
    } else {
        console.error("Modal or trigger elements not found.");
    }
});
</script>



</body>
</html>