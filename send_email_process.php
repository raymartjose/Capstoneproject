<?php
/*
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Include PHPMailer
include('assets/databases/dbconfig.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $invoice_id = intval($_POST['invoice_id']);
    $email = trim($_POST['email']);
    $customer_name = trim($_POST['customer_name']);
    $amount = trim($_POST['amount']);
    $message = trim($_POST['message']);

    if (empty($invoice_id) || empty($email) || empty($customer_name) || empty($amount)) {
        die("Some required values are empty. Please check your input.");
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'joseraymart301@gmail.com'; // Your Gmail
        $mail->Password = 'canv sjjt jqlg zprz'; // Use an App Password from Google
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('joseraymart301@gmail.com', 'Raymart Jose');
        $mail->addAddress($email, $customer_name);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Invoice #$invoice_id Payment Details";
        $mail->Body = nl2br($message);

        // Send email
        if ($mail->send()) {
            echo "<script>alert('Email sent successfully!'); window.location.href='invoice.php';</script>";
        } else {
            echo "<script>alert('Failed to send email.'); window.location.href='invoice.php';</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('Failed to send email. Error: {$mail->ErrorInfo}'); window.location.href='invoice.php';</script>";
    }
} else {
    echo "<script>alert('Invalid request method.'); window.location.href='invoice.php';</script>";
}
    

    
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $invoice_id = intval($_POST['invoice_id']);
    $email = urlencode(trim($_POST['email']));
    $customer_name = urlencode(trim($_POST['customer_name']));
    $amount = urlencode(trim($_POST['amount']));
    $message = urlencode(trim($_POST['message']));

    if (empty($invoice_id) || empty($email) || empty($customer_name) || empty($amount)) {
        die("Some required values are empty. Please check your input.");
    }

    // Generate Gmail compose link
    $gmail_link = "https://mail.google.com/mail/?view=cm&fs=1" .
        "&to=$email" .
        "&su=Invoice%20#$invoice_id%20Payment%20Details" .
        "&body=$message";

    // Redirect to Gmail
    header("Location: $gmail_link");
    exit();
} else {
    echo "<script>alert('Invalid request method.'); window.location.href='invoice.php';</script>";
}

*/


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Include PHPMailer
include('assets/databases/dbconfig.php');

session_start(); // Ensure session is started

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['name'];  // User's name from session
$user_role = $_SESSION['role_display'];  // User's role from session
$user_email = $_SESSION['email']; // User's email from session (ensure this is stored in the session)

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $invoice_id = intval($_POST['invoice_id']);
    $email = trim($_POST['email']);
    $customer_name = trim($_POST['customer_name']);
    $amount = trim($_POST['amount']);
    $status = trim($_POST['payment_status']); // Add invoice status from form (Pending, Paid, etc.)
    $send_method = trim($_POST['send_method']); // "gmail" or "smtp"

    if (empty($invoice_id) || empty($email) || empty($customer_name) || empty($amount) || empty($status) || empty($send_method)) {
        die("Some required values are empty. Please check your input.");
    }

    // Define message based on status
    $status_messages = [
        "Pending" => "Your invoice is currently pending payment. Kindly ensure that payment is processed before the due date to avoid any delays.",
        "Overdue" => "This is a reminder that your invoice is overdue. To avoid further penalties, please settle the outstanding amount as soon as possible.",
        "Cancel" => "Please be informed that invoice #$invoice_id has been cancelled. If you have any questions or need further clarification, feel free to reach out to us.",
        "Paid" => "Thank you! Your invoice #$invoice_id has been successfully paid. If you need a receipt or any further assistance, please let us know."
    ];

    $status_message = $status_messages[$status] ?? "Invoice status update.";

    // Email content
    $subject = "Invoice Status Update – #$invoice_id from C.B. Barangay Enterprises";
    $body = "
Dear $customer_name,

We hope this email finds you well. Below is the latest status of your invoice with C.B. Barangay Enterprises Towing and Trucking Services Inc. (CBBE).

Invoice Details:
Invoice Number: #$invoice_id
Amount Due: ₱$amount
Status: $status

$status_message

Payment Details:
Please process payment using the following details:
Bank: Test
Account Number: Test
Online Payment: [Insert Payment Link]

If you have already processed the payment, please disregard this notice. Should you need any further assistance, feel free to contact us at accounting@cbbe-inc.com or call +63 (2) 8 564 9080.

Best regards,
$user_name - $user_role
C.B. Barangay Enterprises Towing and Trucking Services Inc. (CBBE)
2263 Beata Street, Pandacan, Manila.
accounting@cbbe-inc.com | inquire@cbbe-inc.com | +63 (2) 8 564 9080.
";

    if ($send_method === "gmail") {
        // Gmail link without attachments (attachments need to be handled manually)
        $gmail_link = "https://mail.google.com/mail/?view=cm&fs=1" . 
            "&to=" . urlencode($email) . 
            "&su=" . urlencode($subject) . 
            "&body=" . urlencode($body);

        // Redirect to Gmail compose page
        header("Location: $gmail_link");
        exit();
       
    } elseif ($send_method === "smtp") {
        // Use PHPMailer to send email via SMTP
        $mail = new PHPMailer(true);

        try {
            // SMTP Server settings
            $mail->isSMTP();
            $mail->Host = 'sandbox.smtp.mailtrap.io'; // Change this if using Mailtrap
            $mail->SMTPAuth = true;
            $mail->Username = 'a792075b023a58';  // Mailtrap Username
            $mail->Password = '7373fd82ab1aea';  // Mailtrap Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 2525; // Mailtrap default port

            // Recipients
            $mail->setFrom($user_email, $user_name); // Set sender as logged-in user
            $mail->addAddress($email, $customer_name);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = nl2br($body); // Convert newlines to HTML <br>

            // Send email
            if ($mail->send()) {
                echo "<script>alert('Email sent successfully!'); window.location.href='invoice.php';</script>";
            } else {
                echo "<script>alert('Failed to send email.'); window.location.href='invoice.php';</script>";
            }
        } catch (Exception $e) {
            echo "<script>alert('Failed to send email. Error: {$mail->ErrorInfo}'); window.location.href='invoice.php';</script>";
        }
    } else {
        echo "<script>alert('Invalid send method.'); window.location.href='invoice.php';</script>";
    }
} else {
    echo "<script>alert('Invalid request method.'); window.location.href='invoice.php';</script>";
}




/*
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Include PHPMailer
include('assets/databases/dbconfig.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $invoice_id = intval($_POST['invoice_id']);
    $email = trim($_POST['email']);
    $customer_name = trim($_POST['customer_name']);
    $amount = trim($_POST['amount']);
    $status = trim($_POST['payment_status']);

    // Check if required fields are empty
    if (empty($invoice_id) || empty($email) || empty($customer_name) || empty($amount) || empty($status)) {
        die("Some required values are empty. Please check your input.");
    }

    // Define message based on status
    $status_message = match ($status) {
        "Pending" => "Your invoice is currently pending payment. Kindly ensure that payment is processed before the due date to avoid any delays.",
        "Due Soon" => "This is a reminder that your invoice is due soon. To avoid any late payment penalties, we kindly request that you settle the outstanding amount at your earliest convenience.",
        "Cancelled" => "Please be informed that invoice #$invoice_id has been cancelled. If you have any questions or need further clarification, feel free to reach out to us.",
        "Paid" => "Thank you! Your invoice #$invoice_id has been successfully paid. If you need a receipt or any further assistance, please let us know.",
        default => "Invoice status update."
    };

    // Email content
    $subject = "Invoice Status Update – #$invoice_id from C.B. Barangay Enterprises";
    $body = "
    Dear $customer_name,<br><br>

    We hope this email finds you well. Below is the latest status of your invoice with C.B. Barangay Enterprises Towing and Trucking Services Inc. (CBBE).<br><br>

    <strong>Invoice Details:</strong><br>
    Invoice Number: <b>#$invoice_id</b><br>
    Amount Due: <b>₱$amount</b><br>
    Status: <b>$status</b><br><br>

    $status_message<br><br>

    <strong>Payment Details:</strong><br>
    Bank: Test<br>
    Account Number: Test<br>
    Online Payment: <a href='#'>[Insert Payment Link]</a><br><br>

    If you have already processed the payment, please disregard this notice. Should you need any further assistance, feel free to contact us at <a href='mailto:accounting@cbbe-inc.com'>accounting@cbbe-inc.com</a> or call <b>+63 (2) 8 564 9080</b>.<br><br>

    Thank you for your prompt attention to this matter.<br><br>

    <strong>Best regards,</strong><br>
    <b>Staff</b><br>
    <i>C.B. Barangay Enterprises Towing and Trucking Services Inc. (CBBE)</i><br>
    2263 Beata Street, Pandacan, Manila.<br>
    <a href='mailto:accounting@cbbe-inc.com'>accounting@cbbe-inc.com</a> | <a href='mailto:inquire@cbbe-inc.com'>inquire@cbbe-inc.com</a> | +63 (2) 8 564 9080.
    ";

    // Use PHPMailer to send email via Mailtrap
    $mail = new PHPMailer(true);

    try {
        // SMTP Server settings for Mailtrap
        $mail->isSMTP();
        $mail->Host = 'sandbox.smtp.mailtrap.io';  // Mailtrap SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'a792075b023a58';  // Replace with your Mailtrap credentials
        $mail->Password = '7373fd82ab1aea';  // Replace with your Mailtrap credentials
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 2525; // Mailtrap default port

        // Recipients
        $mail->setFrom('truckingcrane@gmail.com', 'CBBE Invoice System');
        $mail->addAddress($email, $customer_name);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // Send email
        if ($mail->send()) {
            echo "<script>alert('Email sent successfully via Mailtrap!'); window.location.href='invoice.php';</script>";
        } else {
            echo "<script>alert('Failed to send email.'); window.location.href='invoice.php';</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('Failed to send email. Error: {$mail->ErrorInfo}'); window.location.href='invoice.php';</script>";
    }
} else {
    echo "<script>alert('Invalid request method.'); window.location.href='invoice.php';</script>";
}
*/
?>