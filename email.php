email.php
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




<style>
        body {
            font-family: Arial, sans-serif;
            max-width: auto;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 2px 2px 10px rgba(0,0,0,0.1);
        }
        label {
            font-weight: bold;
        }
        input, textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 4px;
        }
        button:hover {
            background-color: #0056b3;
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
<?php


if (isset($_GET['invoice_id'], $_GET['customer_name'], $_GET['email'], $_GET['amount'])) {
    $invoice_id = intval($_GET['invoice_id']);
    $customer_name = urldecode($_GET['customer_name']);
    $email = urldecode($_GET['email']);
    $amount = urldecode($_GET['amount']);
} else {
    echo "<script>alert('Missing email details.'); window.location.href='invoice.php';</script>";
    exit();
}
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
                Invoice
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

        <h2>Send Invoice Email</h2>
    <form action="send_email_process.php" method="post">
        <label>Customer Name:</label>
        <input type="text" name="customer_name" value="<?php echo htmlspecialchars($customer_name); ?>" readonly><br>

        <label>Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly><br>

        <label>Invoice Amount:</label>
        <input type="text" name="amount" value="<?php echo htmlspecialchars($amount); ?>" readonly><br>

        <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
        
        <label>Message:</label>
        <textarea name="message">Dear <?php echo htmlspecialchars($customer_name); ?>, your invoice of <?php echo htmlspecialchars($amount); ?> is due. Please make the payment.</textarea><br>

        <button type="submit">Send Email</button>
        
    </form>

        </main>
    </div>
 


</body>
</html>
