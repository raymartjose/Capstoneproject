<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Report</title>

    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    padding: 20px;
}

.container {
    max-width: 900px;
    margin: 0 auto;
    background-color: #ffffff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.header {
    text-align: center;
    margin-bottom: 30px;
}

.header h1 {
    font-size: 32px;
    color: #2C3E50;
}

.contact-info p {
    font-size: 14px;
    color: #7F8C8D;
}

section {
    margin-bottom: 30px;
}

h2 {
    font-size: 24px;
    color: #2C3E50;
    margin-bottom: 10px;
    border-bottom: 2px solid #2C3E50;
    padding-bottom: 5px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th, td {
    text-align: left;
    padding: 8px;
    border: 1px solid #BDC3C7;
}

th {
    background-color: #ecf0f1;
}

td {
    background-color: #ffffff;
}

.score {
    background-color: #ecf0f1;
    padding: 20px;
    text-align: center;
    border-radius: 8px;
}

.score strong {
    font-size: 36px;
    color: #2C3E50;
}

footer {
    text-align: center;
    margin-top: 50px;
    font-size: 14px;
    color: #7F8C8D;
}

    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>TransUnion Credit Report</h1>
            <div class="contact-info">
                <p>Customer Service: 1-800-916-8800</p>
                <p>Email: support@transunion.com</p>
            </div>
        </header>

        <section class="personal-info">
            <h2>Personal Information</h2>
            <table>
                <tr>
                    <th>Name:</th>
                    <td>John Doe</td>
                </tr>
                <tr>
                    <th>Address:</th>
                    <td>1234 Elm St, Springfield, IL</td>
                </tr>
                <tr>
                    <th>Date of Birth:</th>
                    <td>01/01/1980</td>
                </tr>
                <tr>
                    <th>Social Security Number:</th>
                    <td>***-**-1234</td>
                </tr>
            </table>
        </section>

        <section class="credit-score">
            <h2>Credit Score</h2>
            <div class="score">
                <p>Your credit score: <strong>750</strong></p>
                <p>Status: Excellent</p>
            </div>
        </section>

        <section class="accounts">
            <h2>Accounts</h2>
            <table>
                <tr>
                    <th>Account Name</th>
                    <th>Status</th>
                    <th>Balance</th>
                    <th>Credit Limit</th>
                </tr>
                <tr>
                    <td>Chase Bank</td>
                    <td>Open</td>
                    <td>$1,500</td>
                    <td>$5,000</td>
                </tr>
                <tr>
                    <td>Wells Fargo</td>
                    <td>Closed</td>
                    <td>$0</td>
                    <td>$10,000</td>
                </tr>
                <tr>
                    <td>Citibank</td>
                    <td>Open</td>
                    <td>$3,000</td>
                    <td>$15,000</td>
                </tr>
            </table>
        </section>

        <section class="inquiries">
            <h2>Credit Inquiries</h2>
            <table>
                <tr>
                    <th>Date</th>
                    <th>Inquiry Type</th>
                    <th>Company</th>
                </tr>
                <tr>
                    <td>01/10/2025</td>
                    <td>Hard Inquiry</td>
                    <td>Chase Bank</td>
                </tr>
                <tr>
                    <td>12/05/2024</td>
                    <td>Soft Inquiry</td>
                    <td>Citibank</td>
                </tr>
                <tr>
                    <td>11/20/2024</td>
                    <td>Hard Inquiry</td>
                    <td>Wells Fargo</td>
                </tr>
            </table>
        </section>

        <footer class="footer">
            <p>&copy; 2025 TransUnion. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
