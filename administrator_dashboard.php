<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/add_customer_modal.css">
    <link rel="stylesheet" href="assets/css/customer_list.css">
    <link rel="stylesheet" href="assets/css/add_invoice.css">
    <link rel= "stylesheet" href= "https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css" >
</head>
<body>

<?php
include "assets_liabilities.php";
include "fetch_income_expense.php";
session_start();  // Start the session
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
        <h2><span class="lab la-accusoft"> <span>Finance</span></span></h2>
    </div>

    <div class="sidebar-menu">
        <ul>
        <li>
                <a href="administrator_dashboard.php" class="active"><span class="las la-tachometer-alt"></span>
                <span>Dashboard</span></a>
            </li>
            <li>
                <a href="add_product_services.php"><span class="las la-truck"></span>
                <span>Product & Services</span></a>
            </li>
            <li>
            <a href="financial.php"><span class="las la-balance-scale"></span>
            <span>Financial Request</span></a>
            <li>
                <a href="admin_analytics.php"><span class="las la-chart-bar"></span>
                <span>Analytics</span></a>
            </li>
            <li>
            <a href="admin_add_user.php"><span class="las la-users"></span>
                <span>User Management</span></a>
            </li>
            <li>
                <a href="admin_audit_log.php"><span class="las la-file-invoice"></span>
                <span>Audit Logs</span></a>
            </li>
            <li>
                <a href="logout.php"><span class="las la-sign-out-alt"></span>
                <span>Logout</span></a>
            </li>
        </ul>
    </div>
</div>

<!--Modal-->

<!--add customer-->
<div id="addCustomerModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('addCustomerModal').style.display='none'">&times;</span>
        <h2>Add Customer</h2>
        <form action="add_customer.php" method="POST">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" required>
            
            <label for="address">Address:</label>
            <textarea id="address" name="address" required></textarea>
            
            <button type="submit">Add Customer</button>
        </form>
    </div>
</div>
<!--/add customer-->

<!-- Customer List Modal -->
<div id="allCustomersModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('allCustomersModal').style.display='none'">&times;</span>
        <h3>All Customers</h3>
        <div class="customer-list">
            <?php
            // Database connection
            include('assets/databases/dbconfig.php');

            // Query to fetch all customers
            $query = "SELECT * FROM customers ORDER BY id DESC";
            $result = mysqli_query($connection, $query);

            // Check if there are any customers in the database
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<div class='customer'>
                    <div class='info'>
                        <img src='img/customer-logo.png' width='40px' height='40px' alt='Profile Picture'>
                        <div>
                            <h4>" . htmlspecialchars($row['name']) . "</h4>
                            <small>" . htmlspecialchars($row['email']) . "</small>
                            <p>" . htmlspecialchars($row['phone']) . "</p>
                            <p>" . htmlspecialchars($row['address']) . "</p>
                        </div>
                    </div>
                    <div class='contact'>
                        <span class='las la-history' style='cursor: pointer' onclick='viewTransactionHistory(" . $row['id'] . ")'></span>
                        <span class='las la-edit' style='cursor: pointer' onclick='openEditModal(" . $row['id'] . ")'></span>
                    </div>
                </div>";
        }
    } else {
        echo "<p>No customers found.</p>";
    }
            ?>
        </div>
    </div>
</div>
<!-- /Customer List Modal -->

<!-- Edit Customer Modal -->
<div id="editCustomerModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>Edit Customer</h2>
        <form id="editCustomerForm" action="edit_customer.php" method="POST">
            <input type="hidden" id="edit_customer_id" name="customer_id">
            <label for="name">Name:</label>
            <input type="text" id="edit_name" name="name" required>
            <label for="email">Email:</label>
            <input type="email" id="edit_email" name="email" required>
            <label for="phone">Phone:</label>
            <input type="text" id="edit_phone" name="phone" required>
            <label for="address">Address:</label>
            <textarea id="edit_address" name="address" required></textarea>
            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>
<!-- /Edit Customer Modal -->

<!-- Add Invoice Modal -->
<div id="addInvoiceModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('addInvoiceModal').style.display='none'">&times;</span>
        <h2>Create Invoice</h2>
        <form action="generate_invoice.php" method="POST">
            <!-- Hidden Product ID -->
            <input type="hidden" name="product_id" value="12345">

            <label for="customer_id">Customer:</label>
            <select id="customer_id" name="customer_id" required>
                <!-- PHP Code to fetch customers -->
                <?php
                include('assets/databases/dbconfig.php');
                $customerQuery = "SELECT id, name FROM customers ORDER BY name ASC";
                $customerResult = mysqli_query($connection, $customerQuery);
                while ($customer = mysqli_fetch_assoc($customerResult)) {
                    echo "<option value='" . $customer['id'] . "'>" . htmlspecialchars($customer['name']) . "</option>";
                }
                ?>
            </select>

            <div class="input-group">
            <!-- Product Selection -->
            <label for="product_id">Product:</label>
            <select id="product_id" name="product_id" required>
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

            <label for="additional_services">Additional Services:</label>
            <select id="additional_services" name="additional_services[]" multiple>
                <option value="none">None</option>
                <?php
                    $serviceQuery = "SELECT id, name, price FROM additional_services ORDER BY name ASC";
                    $serviceResult = mysqli_query($connection, $serviceQuery);
                    while ($service = mysqli_fetch_assoc($serviceResult)) {
                        echo "<option value='" . $service['id'] . "' data-price='" . $service['price'] . "'>" . 
                            htmlspecialchars($service['name'] . ' - ₱' . $service['price']) . "</option>";
                    }
                ?>
            </select>
            </div>

            <!-- Rental Start and End Date -->
            <div class="input-group">
                <label for="rental_start_date">Rental Start Date:</label>
                <input type="date" id="rental_start_date" name="rental_start_date" required>

                <label for="rental_end_date">Rental End Date:</label>
                <input type="date" id="rental_end_date" name="rental_end_date" required>
            </div>

            <!-- Amount and Discount -->
            <div class="input-group">

                <label for="surcharges">Surcharges:</label>
                <input type="number" id="surcharges" name="surcharges" step="0.01" value="0.00">

                <label for="discount_amount">Discount Amount:</label>
                <input type="number" id="discount_amount" name="discount_amount" step="0.01" value="0.00">

            </div>

            <div class="input-group">
                
            <label for="tax_id">Tax:</label>
            <select id="tax_id" name="tax_id" required>
                <option value="none">None</option>
                <?php
                $taxQuery = "SELECT id, name, rate FROM taxes ORDER BY name ASC";
                $taxResult = mysqli_query($connection, $taxQuery);
                while ($tax = mysqli_fetch_assoc($taxResult)) {
                    echo "<option value='" . $tax['id'] . "' data-rate='" . $tax['rate'] . "'>" . 
                        htmlspecialchars($tax['name']) . " (" . $tax['rate'] . "%)</option>";
                }
                ?>
            </select>

            <label for="amount">Total Amount:</label>
            <input type="number" id="amount" name="amount" step="0.01" readonly>

            </div>

            <div class="input-group">
            <!-- Payment Status -->
            <label for="payment_status">Payment Status:</label>
            <select id="payment_status" name="payment_status" required>
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
            </select>

            <!-- Payment Method -->
            <label for="payment_method">Payment Method:</label>
            <select id="payment_method" name="payment_method" required>
                <option value="cash">Cash</option>
                <option value="credit">Credit</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="online">E-Wallet</option>
            </select>
            </div>


            <button type="submit">Create Invoice</button>
        </form>
    </div>
</div>
<!-- /Add Invoice Modal -->




<!-- Modal for "See All Invoices" -->
<div id="seeAllInvoicesModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span onclick="document.getElementById('seeAllInvoicesModal').style.display='none'" class="close">&times;</span>
        <h3>All Invoices</h3>
        <div class="table-responsive">
            <table width="100%">
                <thead>
                    <tr>
                        <td>Invoice Number</td>
                        <td>Customer</td>
                        <td>Amount</td>
                        <td>Status</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query to fetch all invoices
                    $query_all = "SELECT i.id, c.name AS customer_name, i.amount, i.payment_status 
                                  FROM invoices i 
                                  JOIN customers c ON i.customer_id = c.id 
                                  ORDER BY i.issue_date DESC";
                    $result_all = mysqli_query($connection, $query_all);

                    if (mysqli_num_rows($result_all) > 0) {
                        while ($row_all = mysqli_fetch_assoc($result_all)) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($row_all['id']) . "</td>
                                    <td>" . htmlspecialchars($row_all['customer_name']) . "</td>
                                    <td>₱" . number_format($row_all['amount'], 2) . "</td>
                                    <td><span class='status " . ($row_all['payment_status'] == 'paid' ? 'green' : 'orange') . "'></span>" . ucfirst($row_all['payment_status']) . "</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>No invoices found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- /Modal for "See All Invoices" -->

<div id="transactionHistoryModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('transactionHistoryModal').style.display='none'">&times;</span>
        <h4>Customer Transaction History</h4>
        <div id="transactionHistoryTable"></div>
    </div>
</div>

<!--/Modal-->

    <div class="main-content">
        <header>
            <div class="header-title">
                <h2>
                <label for="nav-toggle">
                    <span class="las la-bars"></span>
                </label>
                Dashboard
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

            <div class="cards">
                <div class="card-single">
                    <div>
                    <h3>Expense</h3>
                        <h4><?php echo "₱" , number_format($totalExpense, 2); ?></h4>
                        <span>Track your expenses</span>
                    </div>
                    <div>
                        <span class="las la-receipt"></span>
                    </div>
                </div>

                <div class="card-single">
                    <div>
                    <h3>Assets</h3>
                        <h4><?php echo "₱" , number_format($totalAssets, 2); ?></h4>
                        <span>Track your assets</span>
                    </div>
                    <div>
                        <span class="las la-coins"></span>
                    </div>
                </div>

                <div class="card-single">
                    <div>
                    <h3>Liabilities</h3>
                        <h4><?php echo "₱" , number_format($totalLiabilities, 2); ?></h4>
                        <span>Track your liabilities</span>
                    </div>
                    <div>
                        <span class="las la-credit-card"></span>
                    </div>
                </div>

                <div class="card-single">
                    <div>
                        <h3>Income</h3>
                    <h4><?php echo "₱" , number_format($totalIncome, 2); ?></h4>
                        <span>Track your income</span>
                    </div>
                    <div>
                        <span class="lab la-google-wallet"></span>
                    </div>
                </div>
            </div>

        

            <div class="recent-grid">
    <div class="projects">
        <div class="card">
            <div class="card-header">
                <h3>Generated Invoices</h3>
                <button onclick="document.getElementById('addInvoiceModal').style.display='block'">Add Invoice <span class="las la-plus"></span></button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table width="100%">
                        <thead>
                            <tr>
                                <td>Invoice Number</td>
                                <td>Customer</td>
                                <td>Amount</td>
                                <td>Status</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            include('assets/databases/dbconfig.php');

                            // Get current date
                            $current_date = date('Y-m-d');

                            // Query to fetch the latest 5 invoices
                            $query = "SELECT i.id, c.name AS customer_name, i.amount, i.payment_status, i.due_date
                                      FROM invoices i
                                      JOIN customers c ON i.customer_id = c.id 
                                      ORDER BY i.issue_date DESC LIMIT 5";
                            $result = mysqli_query($connection, $query);

                            // Check if there are any invoices
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    // Check if the invoice is overdue and has a pending status
                                    if ($row['payment_status'] == 'pending' && $row['due_date'] < $current_date) {
                                        // Update the payment status of the invoice to 'overdue'
                                        $update_invoice_query = "UPDATE invoices 
                                                                  SET payment_status = 'overdue' 
                                                                  WHERE id = " . $row['id'];
                                        mysqli_query($connection, $update_invoice_query);

                                        // Update the status in the accounts_receivable table to 'overdue'
                                        $update_ar_query = "UPDATE accounts_receivable 
                                                            SET status = 'overdue' 
                                                            WHERE invoice_id = " . $row['id'];
                                        mysqli_query($connection, $update_ar_query);
                                    }

                                    // Display the invoice data
                                    echo "<tr>
                                            <td>" . htmlspecialchars($row['id']) . "</td>
                                            <td>" . htmlspecialchars($row['customer_name']) . "</td>
                                            <td>₱" . number_format($row['amount'], 2) . "</td>
                                            <td><span class='status " . ($row['payment_status'] == 'paid' ? 'green' : ($row['payment_status'] == 'overdue' ? 'red' : 'orange')) . "'></span>" . ucfirst($row['payment_status']) . "</td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>No invoices found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-header">
                <button onclick="document.getElementById('seeAllInvoicesModal').style.display='block'">See all <span class="las la-arrow-right"></span></button>
            </div>
        </div>
    </div>



                <div class="customers">
                        <div class="card">
                            <div class="card-header">
                                <h3>Customers</h3>
                                <button onclick="document.getElementById('addCustomerModal').style.display='block'">Add Customer <span class="las la-plus"></span></button>
                            </div>

                            <div class="card-body">
                            <?php
                                // Database connection
                                include('assets/databases/dbconfig.php');

                                // Query to get the total amount spent by each customer, and the most recent payment method
                                $query = "SELECT * FROM customers ORDER BY id DESC LIMIT 5"; // Limit to latest 5 customers

                                $result = mysqli_query($connection, $query);

                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        // Display customer details along with total spent and payment method
                                        echo "<div class='customer'>
                                                <div class='info'>
                                                    <img src='img/customer-logo.png' width='40px' height='40px' alt='Profile Picture'>
                                                    <div>
                                                        <h4>" . htmlspecialchars($row['name']) . "</h4>
                                                        <small>" . htmlspecialchars($row['email']) . "</small>
                                                    </div>
                                                </div>
                                                <div class='contact'>
                                                    <span class='las la-history' style='cursor: pointer' onclick='viewTransactionHistory(" . $row['id'] . ")'></span>
                                                    <span class='las la-edit' style='cursor: pointer' onclick='openEditModal(" . $row['id'] . ")'></span>
                                                </div>
                                            </div>";
                                    }
                                } else {
                                    echo "<p>No customers found.</p>";
                                }
                                ?>
                            </div>

                            <div class="card-header">
                                <button onclick="openAllCustomersModal()">See all <span class="las la-arrow-right"></span></button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

            <script src="assets/js/customer_list.js"></script>
            <script>
$(document).ready(function() {
    // Handling form submission via AJAX
    $("#addInvoiceForm").on("submit", function(e) {
        e.preventDefault();  // Prevent the default form submission

        var formData = $(this).serialize(); // Serialize form data

        $.ajax({
            url: "generate_invoice.php",  // Target PHP script
            type: "POST",  // Form submission method
            data: formData,  // Send the form data
            dataType: "json",  // Expect a JSON response
            success: function(response) {
                console.log(response);  // Debugging: Check what response looks like
                if (response.success) {
                    // If success, you can hide the modal or perform another action
                    alert(response.message);  // Show success message
                    $('#addInvoiceModal').hide();  // Hide the modal on success
                    $(".error-message").hide();  // Hide the error message (if any)
                } else {
                    // If failure, display the error message in the modal
                    $(".error-message").text(response.message).show();  // Show error message
                    $('#addInvoiceModal').show(); // Ensure the modal remains open
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX errors
                console.log("AJAX Error: " + error);  // Log the error for debugging
                $(".error-message").text("An error occurred. Please try again.").show();
                $('#addInvoiceModal').show(); // Ensure the modal remains open
            }
        });
    });
});

</script>
<script>
    window.onclick = function(event) {
    var modal = document.getElementById('taxReportModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
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

<script>
// Function to show the transaction history
function viewTransactionHistory(customerId) {
    // Open the modal
    document.getElementById('transactionHistoryModal').style.display = 'block';

    // Make an AJAX request to fetch transaction history for the customer
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_customer_transactions.php?customer_id=' + customerId, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById('transactionHistoryTable').innerHTML = xhr.responseText;
        }
    };
    xhr.send();
}
</script>

<script>
function openEditModal(customerId) {
    // Fetch customer details using AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_customer_details.php?id=' + customerId, true);
    xhr.onload = function() {
        if (xhr.status == 200) {
            var customer = JSON.parse(xhr.responseText);
            document.getElementById('edit_customer_id').value = customer.id;
            document.getElementById('edit_name').value = customer.name;
            document.getElementById('edit_email').value = customer.email;
            document.getElementById('edit_phone').value = customer.phone;
            document.getElementById('edit_address').value = customer.address;
            document.getElementById('editCustomerModal').style.display = 'block';
        }
    };
    xhr.send();
}

function closeEditModal() {
    document.getElementById('editCustomerModal').style.display = 'none';
}
</script>
</body>
</html>