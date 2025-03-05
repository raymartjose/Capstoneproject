<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="icon" href="img/logo-sm.png">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/staff_request_form.css">
    <link rel= "stylesheet" href= "https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css" >
</head>
<body>



<input type="checkbox" id="nav-toggle">
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="img/logo1.png" alt="">
    </div>

    <div class="sidebar-menu">
        <ul>
        <li>
                <a href="staff_dashboard.php"><span class="las la-tachometer-alt"></span>
                <span>Dashboard</span></a>
            </li>
            <li>
                <a href="staff_add_product_services.php"><span class="las la-truck"></span>
                <span>Product & Services</span></a>
            </li>
            <li>
            <a href="staff_financial.php"  class="active"><span class="las la-balance-scale"></span>
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
                Financial
                </h2>
                </div>

                <div class="user-wrapper">

                    <span class="las la-bell" width="40px" height="40px"></span>
                    <div>
                        <h4>Ace</h4>
                        <small>staff</small>
                    </div>
                </div>
        </header>
        
        <main>


<div id="request-details">
    <h2>Request Details</h2>
    <form action="save_request.php" method="POST">

    <div class="form-group">
            <label for="request_type">Request Type:</label>
            <select id="request_type" name="request_type" required>
                <option value="budget">Budget</option>
                <option value="expense">Others</option>
            </select>
        </div>

        <div class="form-group">
        <label for="department">Department:</label>
        <select id="department" name="department" required>
                <option value="department">Department</option>
                <option value="department">HR</option>
        </select>
        </div>

        <div class="form-group">
            <label for="staff_name">Requestor:</label>
            <select id="staff_name" name="staff_name" required>
                <option value="staff_name">Staff Name</option>
                <option value="staff_name">Raymart</option>
        </select>
        </div>

        <div class="form-group">
            <label for="staff_id">Staff ID:</label>
            <input type="text" id="staff_id" name="staff_id" required>
        </div>

        <div class="form-group">
            <label for="position">Position:</label>
            <input type="text" id="position" name="position" required>
        </div>

        <div class="form-group">
            <label for="amount">Requested Amount:</label>
            <input type="number" id="amount" name="amount" required>
        </div>

        <div class="form-group full-width">
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4" required></textarea>
        </div>


    </div>



        <div id="documents">
    <h3>Documents</h3>

    <div id="attachments-container">
    <div class="attachment-row">
    <select name="categories[]" class="file-category">
                <option value="invoice">Invoice</option>
                <option value="receipt">Receipt</option>
                <option value="contract">Contract</option>
                <option value="other">Other</option>
            </select>
    
            <input type="file" name="attachments[]" class="attachment-file" onchange="handleFileUpload(this)">
            <a href="#" download="" class="download-link">Download</a>
            <input type="date" class="file-date">
            <input type="text" class="remarks1" name="remarks1[]" placeholder="Remarks">
            <button type="button" class="add-btn" onclick="addAttachmentRow1()">+</button>
    </div>
</div>

</div>

<div id="remarks">
    <div class="remarks-wrapper">
        <!-- Remarks Column -->
        <div class="remarks-container">
            <h3>Remarks</h3>
            <div class="remark-message">
                <p><strong>Name:</strong>Date:</p>
                <p>remarks</p>
            </div>
            <input type="text" id="remarkText" name="remarks[]" class="remarks" placeholder="Remarks">

        </div>

</div>
<div class="form-buttons">
    <button type="submit" name="save_request" class="save-btn">Save and Submit</button>

            <button type="button" class="cancel-btn">Cancel Request</button>
        </div>
    </form>

    <div class="transaction-graph-container">
    <h3>Transaction History</h3>
    <canvas id="transactionChart"></canvas>
</div>
</div>
        </main>
    </div>






   <script>
    function handleFileUpload(fileInput) {
        const file = fileInput.files[0];
        const downloadButton = fileInput.parentElement.querySelector(".download-btn");
        const downloadLink = fileInput.parentElement.querySelector(".download-link");
        const fileDateInput = fileInput.parentElement.querySelector(".file-date");

        // Make the download button visible once the file is uploaded
        downloadButton.style.display = 'inline-block';

        // Set the href attribute of the download link to the uploaded file's path
        const fileURL = URL.createObjectURL(file);
        const fileName = file.name;
        
        downloadLink.href = fileURL;
        downloadLink.download = fileName; // Set the file name for download
        const currentDate = new Date().toISOString().split('T')[0]; // Format the date as YYYY-MM-DD
    fileDateInput.value = currentDate;
    }

    function addAttachmentRow1() {
        let container = document.getElementById("attachments-container");
        let newRow = document.createElement("div");
        newRow.classList.add("attachment-row");
        newRow.innerHTML = `
            <select name="categories[]" class="file-category">
                <option value="invoice">Invoice</option>
                <option value="receipt">Receipt</option>
                <option value="contract">Contract</option>
                <option value="other">Other</option>
            </select>
            <input type="file" name="attachments[]" class="attachment-file" onchange="handleFileUpload(this)">
            <button type="button" class="download-btn" style="display:none;"><a href="#" download="" class="download-link">Download</a></button>
            <input type="date" class="file-date">
            <input type="text" class="remarks1" name="remarks1[]" placeholder="Remarks">
            <button type="button" class="remove-btn" onclick="removeAttachmentRow(this)">-</button>
        `;
        container.appendChild(newRow);
    }

    function removeAttachmentRow(button) {
        let row = button.parentElement; // Get the parent div of the button (the row)
        row.remove(); // Remove the row from the container
    }
</script>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById('transactionChart').getContext('2d');

        const testData = {
            labels: ['Date', 'when', 'the', 'account', 'was', '(Submit|Return|Approved|Rejected'],
            datasets: [
                {
                    label: 'Transaction Trends',
                    data: [0, 1, 2, 3, 4], // Mapped to statuses below
                    backgroundColor: 'rgb(12, 12, 12)',
                    borderColor: 'rgb(12, 12, 12)',
                    borderWidth: 2
                }
            ]
        };

        new Chart(ctx, {
            type: 'line',
            data: testData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1, // Ensures only whole number values are shown
                            callback: function (value) {
                                const status = ["Submit", "Return", "Reject", "Approved", "Cancel"];
                                return status[value] !== undefined ? status[value] : '';
                            }
                        }
                    }
                }
            }
        });
    });
</script>
<script>
    document.querySelector(".save-btn").addEventListener("click", function (e) {
    e.preventDefault(); // Prevent default form submission

    let form = document.querySelector("form");
    let formData = new FormData(form); // Automatically collects form inputs

    // Collect file attachments manually
    document.querySelectorAll("#attachments-container .attachment-row").forEach((row, index) => {
        let fileInput = row.querySelector("input[type='file']");
        if (fileInput && fileInput.files.length > 0) {
            formData.append(`attachments[${index}]`, fileInput.files[0]);
        }
        formData.append(`categories[${index}]`, row.querySelector(".file-category").value);
    });

    fetch("save_request.php", {
        method: "POST",
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Request saved successfully!");
            window.location.reload(); // Reload to reflect changes
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(error => console.error("Error:", error));
});

</script>


<style>
.transaction-graph-container {
    margin-top: 20px;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.transaction-graph-container h3 {
    text-align: center;
    margin-bottom: 10px;
}

#transactionChart {
    max-height: 300px;
}
</style>

</body>
</html>