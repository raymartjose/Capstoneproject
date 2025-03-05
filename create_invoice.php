<!DOCTYPE html>
<html lang="en">
    <?php
    session_start();  // Start the session
    ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="icon" href="img/logo-sm.png">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/request_form.css">
    <link rel= "stylesheet" href= "https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css" >
    
</head>
<body>
<?php
include('assets/databases/dbconfig.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if editing (invoice_id is passed)
$invoice_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;
$invoice = [];

// Fetch invoice details if editing
if ($invoice_id > 0) {
    $query = "SELECT i.*, c.name AS customer_name, c.email, c.phone, c.address,
              p.name AS product_name, p.category, p.purchase_cost, p.daily_rate, 
              p.brand, p.model, p.color, p.plate_number, p.status AS product_status,
              i.payment_status
              FROM invoices i
              JOIN customers c ON i.customer_id = c.id
              JOIN products p ON i.product_id = p.id
              WHERE i.id = ?";

    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $invoice_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $invoice = mysqli_fetch_assoc($result);
    } else {
        echo "<script>alert('Invoice not found'); window.location.href='invoice.php';</script>";
        exit();
    }
}
?>


<style>
    /* Invoice Form Styles */
    .invoice-form {
        max-width: auto;
        margin: auto;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        font-family: Arial, sans-serif;
    }

    .form-section {
        margin-bottom: 20px;
    }

    .form-section h3 {
        color: #ed6978;
        border-bottom: 2px solid #ed6978;
        padding-bottom: 5px;
        margin-bottom: 15px;
    }

    input, select {
        width: 100%;
        padding: 8px;
        margin-bottom: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .input-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .input-group input,
    .input-group select {
        flex: 1;
    }

    .btn {
        background: #ed6978;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: block;
        width: 100%;
        font-size: 16px;
    }

    .btn:hover {
        background: #d1697b;
    }

    .btn-add {
        background: #ed6978;
        color: white;
        padding: 5px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: inline-block;
        font-size: 16px;
        margin-top: 10px;
    }

    .btn-add:hover {
        background: #d1697b;
    }

    /* Product Table Styling */
    .product-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .product-table th, .product-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: center;
    }

    .product-table th {
        background: #28a745;
        color: white;
    }

    .btn-remove {
        padding: 5px 10px;
        background: #ed6978;
        color: white;
        border: none;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-remove:hover {
        background: #d1697b;
    }
</style>

<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect to login if not logged in
    exit();
}

$user_name = $_SESSION['name'];  // User name from session
$user_role = $_SESSION['role'];  // User role from session

?>

<input type="checkbox" id="nav-toggle">
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="img/logo1.png" alt="">
    </div>

    <div class="sidebar-menu">
        <ul>
        <li>
                <a href="staff_dashboard.php" class="active"><span class="las la-tachometer-alt"></span>
                <span>Dashboard</span></a>
            </li>
            <li>
                <a href="staff_add_product_services.php"><span class="las la-truck"></span>
                <span>Product & Services</span></a>
            </li>
            <li>
            <a href="staff_financial.php"><span class="las la-balance-scale"></span>
            <span>Financial Request</span></a>
            </li>
            <li>
                <a href="staff_payroll.php"><span class="las la-users"></span>
                <span>Staffing & Payroll</span></a>
            </li>
            <li>
                <a href="staff_audit_log.php"><span class="las la-file-invoice"></span>
                <span>Audit Logs</span></a>
            </li>
            <li>
                <a href="logout.php"><span class="las la-sign-out-alt"></span>
                <span>Logout</span></a>
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
                Generate Invoice
                </h2>
                </div>

                <div class="user-wrapper">

                    <span class="las la-bell" width="40px" height="40px"></span>
                    <div>
                        <h4><?php echo htmlspecialchars($user_name); ?></h4>
                        <small><?php echo htmlspecialchars($user_role); ?></small>
                    </div>
                </div>
        </header>
        
        <main>
            

        <form action="update_invoice.php" method="POST" class="invoice-form">
    <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">

    <h3>Customer Details</h3>
    <label for="customer_id">Customer Name:</label>
    <input type="text" name="customer_name" value="<?php echo isset($invoice['customer_name']) ? $invoice['customer_name'] : ''; ?>" readonly>

    <label for="email">Email:</label>
    <input type="email" name="email" value="<?php echo isset($invoice['email']) ? $invoice['email'] : ''; ?>" readonly>

    <label for="phone">Phone:</label>
    <input type="text" name="phone" value="<?php echo isset($invoice['phone']) ? $invoice['phone'] : ''; ?>" readonly>

    <label for="address">Address:</label>
    <input type="text" name="address" value="<?php echo isset($invoice['address']) ? $invoice['address'] : ''; ?>" readonly>

    <h3>Product Details</h3>
    <label for="product_name">Product:</label>
    <input type="text" name="product_name" value="<?php echo isset($invoice['product_name']) ? $invoice['product_name'] : ''; ?>" readonly>

    <label for="plate_number">Plate Number:</label>
    <input type="text" name="plate_number" value="<?php echo isset($invoice['plate_number']) ? $invoice['plate_number'] : ''; ?>" readonly>

    <label for="daily_rate">Daily Rate:</label>
    <input type="text" name="daily_rate" value="<?php echo isset($invoice['daily_rate']) ? $invoice['daily_rate'] : ''; ?>" readonly>

    <h3>Invoice Details</h3>
    <label for="issue_date">Issue Date:</label>
    <input type="date" name="issue_date" value="<?php echo isset($invoice['issue_date']) ? $invoice['issue_date'] : ''; ?>" required>

    <label for="due_date">Due Date:</label>
    <input type="date" name="due_date" value="<?php echo isset($invoice['due_date']) ? $invoice['due_date'] : ''; ?>" required>

    <label for="surcharges">Surcharges:</label>
    <input type="text" name="surcharges" value="<?php echo isset($invoice['surcharges']) ? $invoice['surcharges'] : ''; ?>">

    <label for="discount_amount">Discount:</label>
    <input type="text" name="discount_amount" value="<?php echo isset($invoice['discount_amount']) ? $invoice['discount_amount'] : ''; ?>">

    <label for="tax_rate">Tax Rate (%):</label>
    <input type="text" name="tax_rate" value="<?php echo isset($invoice['tax_rate']) ? $invoice['tax_rate'] : ''; ?>">

    <label for="total_amount">Total Amount:</label>
    <input type="text" name="total_amount" value="<?php echo isset($invoice['total_amount']) ? number_format($invoice['total_amount'], 2) : ''; ?>" readonly>

    <!-- Payment Method -->
    <div class="form-section">
        <div class="input-group">
            <label for="payment_status">Payment Status:</label>
            <select id="payment_status" name="payment_status" required>
                <option value="pending" <?php echo (isset($invoice['payment_status']) && $invoice['payment_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="paid" <?php echo (isset($invoice['payment_status']) && $invoice['payment_status'] == 'paid') ? 'selected' : ''; ?>>Paid</option>
                <option value="overdue" <?php echo (isset($invoice['payment_status']) && $invoice['payment_status'] == 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                <option value="cancel" <?php echo (isset($invoice['payment_status']) && $invoice['payment_status'] == 'cancel') ? 'selected' : ''; ?>>Cancel</option>
            </select>

            <label for="payment_method">Payment Method:</label>
            <select id="payment_method" name="payment_method" required>
                <option value="cash" <?php echo (isset($invoice['payment_method']) && $invoice['payment_method'] == 'cash') ? 'selected' : ''; ?>>Cash</option>
                <option value="credit" <?php echo (isset($invoice['payment_method']) && $invoice['payment_method'] == 'credit') ? 'selected' : ''; ?>>Credit</option>
                <option value="bank_transfer" <?php echo (isset($invoice['payment_method']) && $invoice['payment_method'] == 'bank_transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                <option value="online" <?php echo (isset($invoice['payment_method']) && $invoice['payment_method'] == 'online') ? 'selected' : ''; ?>>E-Wallet</option>
            </select>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Update Invoice</button>
</form>

    




        <!-- End:Main Body -->
        </main>
    </div>
    <script>
    function addProductRow() {
        let container = document.getElementById("product-container");
        let newRow = document.createElement("div");
        newRow.classList.add("product-row");
        newRow.innerHTML = `
            <div class="input-group">
                <label>Product:</label>
                <select name="product_id[]" required>
                    <option value="none">None</option>
                    <?php
                    $productQuery = "SELECT id, name, daily_rate FROM products WHERE status = 'available'";
                    $productResult = mysqli_query($connection, $productQuery);
                    while ($product = mysqli_fetch_assoc($productResult)) {
                        echo "<option value='" . $product['id'] . "' data-daily-rate='" . $product['daily_rate'] . "'>" 
                            . htmlspecialchars($product['name']) . " - " . $product['daily_rate'] . " per day</option>";
                    }
                    ?>
                </select>

            <label>Product ID:</label>
            <input type="number" name="pid[]" required>

            <label>Plate #:</label>
            <input type="number" name="plate[]" required>

            <label>Subtotal:</label>
            <input type="number" name="stotal[]" required>

                <button type="button" class="btn-remove" onclick="removeProductRow(this)">-</button>
            </div>
        `;
        container.appendChild(newRow);
    }

    function removeProductRow(button) {
        let row = button.parentElement; // Get the parent div
        row.remove(); // Remove the row
    }
</script>




    <script>
    document.addEventListener("DOMContentLoaded", function() {
    const productSelect = document.getElementById("product_id");
    const rentalStartDate = document.getElementById("rental_start_date");
    const rentalEndDate = document.getElementById("rental_end_date");
    const surchargesInput = document.getElementById("surcharges");
    const discountInput = document.getElementById("discount_amount");
    const taxSelect = document.getElementById("tax_id");
    const totalAmountInput = document.getElementById("amount");
    const additionalServicesSelect = document.getElementById("additional_services");

    function calculateTotalAmount() {
        const selectedProduct = productSelect.options[productSelect.selectedIndex];
        const dailyRate = parseFloat(selectedProduct.getAttribute("data-daily-rate")) || 0;
        const surcharges = parseFloat(surchargesInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        
        // Calculate additional services cost
        let additionalServicesCost = 0;
        for (let option of additionalServicesSelect.selectedOptions) {
            additionalServicesCost += parseFloat(option.getAttribute("data-price")) || 0;
        }
        
        // Get selected tax rate
        const selectedTax = taxSelect.options[taxSelect.selectedIndex];
        const taxRate = parseFloat(selectedTax.getAttribute("data-rate")) || 0;

        if (rentalStartDate.value && rentalEndDate.value && dailyRate > 0) {
            const startDate = new Date(rentalStartDate.value);
            const endDate = new Date(rentalEndDate.value);
            const rentalDays = (endDate - startDate) / (1000 * 60 * 60 * 24);

            if (rentalDays > 0) {
                const rentalAmount = dailyRate * rentalDays;
                
                // Calculate subtotal before discount
                const subtotal = rentalAmount + additionalServicesCost + surcharges;
                
                // Apply discount
                const discountedSubtotal = subtotal - discount;
                
                // Calculate tax on the discounted amount
                const taxAmount = discountedSubtotal * (taxRate / 100);
                
                // Total amount including tax
                const totalAmount = discountedSubtotal + taxAmount;
                totalAmountInput.value = totalAmount.toFixed(2);
            } else {
                totalAmountInput.value = "0.00";
            }
        } else {
            totalAmountInput.value = "0.00";
        }
    }

    productSelect.addEventListener("change", calculateTotalAmount);
    rentalStartDate.addEventListener("change", calculateTotalAmount);
    rentalEndDate.addEventListener("change", calculateTotalAmount);
    surchargesInput.addEventListener("input", calculateTotalAmount);
    discountInput.addEventListener("input", calculateTotalAmount);
    additionalServicesSelect.addEventListener("change", calculateTotalAmount);
    taxSelect.addEventListener("change", calculateTotalAmount);
});

</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const paymentStatusSelect = document.getElementById("payment_status");
        const paymentMethodSelect = document.getElementById("payment_method");

        // Function to toggle the payment method based on payment status
        function togglePaymentMethod() {
            if (paymentStatusSelect.value === "paid") {
                paymentMethodSelect.disabled = false; // Enable the payment method
            } else {
                paymentMethodSelect.disabled = true; // Disable the payment method
            }
        }

        // Initial check on page load
        togglePaymentMethod();

        // Listen for changes to the payment status
        paymentStatusSelect.addEventListener("change", togglePaymentMethod);
    });
</script>


</body>
</html>