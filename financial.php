<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/financial.css">
    <link rel="stylesheet" href="assets/css/request_form.css">
    <link rel= "stylesheet" href= "https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css" >
</head>
<body>

<?php
include "fetch_count.php";
include "fetch_request.php";
session_start();
include('assets/databases/dbconfig.php');

$timeout_duration = 600;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

// Restrict access if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if session token matches the one stored in the database
$sql = "SELECT session_token FROM users WHERE id = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($stored_token);
$stmt->fetch();
$stmt->close();

if ($_SESSION['session_token'] !== $stored_token) {
    session_unset();
    session_destroy();
    header("Location: login.php?session_expired=1");
    exit();
}

$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];
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
                <a href="analytics.php"><span class="las la-chart-bar"></span>
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
<!-- Request Details Modal -->
<div class="modal-background" id="requestDetailsModal" style="display: none;">
    <div class="modal-content">
        <button class="close" onclick="closeModal('requestDetailsModal')">&times;</button>
        <div class="modal-header">
            <button class="tab-button" onclick="showTab('detailsTab')">Request Details</button>
            <button class="tab-button" onclick="showTab('documentTab')">Document</button>
            <button class="tab-button" onclick="showTab('remarksTab')">Remarks</button>
        </div>

        <div class="modal-body">
            <!-- Request Details Tab -->
            <div class="tab-content" id="detailsTab">
                <h3>Request Details</h3>
                <div id="requestDetailsContent">
                    <!-- Details will be dynamically loaded here -->
                </div>
                <div>
                    <button>Save Changes</button>
                </div>
            </div>

            <!-- Document Tab -->
            <div class="tab-content" id="documentTab" style="display: none;">
                <h3>Upload Document</h3>
                <label for="docCategory">Category:</label>
                <select id="docCategory">
                    <option value="financial">Financial</option>
                    <option value="other">Other</option>
                </select>
                <label for="uploadDocument">Upload File:</label>
                <input type="file" id="uploadDocument">
                <button onclick="uploadDocument()">Upload</button>
            </div>

            <!-- Remarks Tab -->
            <div class="tab-content" id="remarksTab" style="display: none;">
                <h3>Remarks</h3>
                <div id="remarksHistory">
                    <!-- Remarks history will be dynamically loaded here -->
                </div>
                <textarea id="remarksInput" placeholder="Add your remarks"></textarea>
                <button onclick="submitRemark()">Save Remark</button>
            </div>
        </div>

        <div class="modal-footer">
            <button onclick="submitRequest()">Submit</button>
        </div>
    </div>
</div>





<div class="modal-background" id="financialRequestModal">
    <div class="modal-content">
        <button class="close-button" onclick="closeModal()">×</button>
        <form action="submit_request.php" method="POST">
            <h3>Financial Request</h3>
            <label for="requestType">Request Type:</label>
            <select id="requestType" name="request_type" onchange="toggleExpenseCategory()">
                <option value="budget">Budget</option>
                <option value="expense">Expense</option>
            </select>

            <div id="expenseCategoriesDiv" style="display: none;">
                <label for="category_id">Expense Category:</label>
                <select id="category_id" name="category_id">
                    <?php
                    include('assets/databases/dbconfig.php');
                    $categories = $connection->query("SELECT id, name FROM expense_categories");
                    while ($category = $categories->fetch_assoc()) {
                        echo "<option value='{$category['id']}'>{$category['name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <label for="amount">Amount:</label>
            <input type="number" id="amount" name="amount" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea>

            <button type="submit">Submit Request</button>
        </form>
    </div>
</div>


<!--Modal-->

    <div class="main-content">
        <header>
            <div class="header-title">
                <h2>
                <label for="nav-toggle">
                    <span class="las la-bars"></span>
                </label>
                Financial Request
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
            <h3>Pending Budget Request</h3>
            <h4>
            <?php echo $returned_budget_count; ?>
            </h4>
            <span></span>
        </div>
        <div>
            <span class="las la-credit-card"></span>
        </div>
    </div>

    <div class="card-single">
        <div>
            <h3>Pending Expense Request</h3>
            <h4>
            <?php echo $returned_expense_count; ?>
            </h4>
            <span></span>
        </div>
        <div>
            <span class="las la-money-bill-wave"></span>
        </div>
    </div>


    <div class="card-single" onclick="loadRequestData('approved')">
        <div>
            <h3>Approved Request</h3>
            <h4>
                <?php echo $totalApprovedRequests; ?> <!-- Total approved requests -->
            </h4>
            <span></span>
        </div>
        <div>
            <span class="las la-clipboard-check"></span>
        </div>
    </div>

    <div class="card-single" onclick="loadRequestData('rejected')">
        <div>
            <h3>Rejected Request</h3>
            <h4>
            <?php echo $totalRejectedRequests; ?>
            </h4>
            <span></span>
        </div>
        <div>
            <span class="las la-file-excel"></span>
        </div>
    </div>


</div>

        

            <div class="recent-grid">
    <div class="projects">
        <div class="card">
            <div class="card-header">
                <h3>List</h3>
                <button onclick="openModal()">Add Request <span class="las la-plus"></span></button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table width="100%">
                        <thead>
                            <tr>
                            <th>ID</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>     
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-header">
                <button>See all <span class="las la-arrow-right"></span></button>
            </div>
        </div>
    </div>



                <div class="customers">
                        <div class="card">
                            <div class="card-header">
                                <h3>Notifications</h3>
                                <span class="las la-bell"></span>
                            </div>

                            <div class="card-body">
                            
                            <?php
                            include ('assets/databases/dbconfig.php');
                            $staff_id = $_SESSION['user_id'];
                            $notifications = $connection->query("SELECT * FROM notifications WHERE user_id = $staff_id AND status = 'unread'");
                            while($notification = $notifications->fetch_assoc()) {
                                echo "<div class='customer'>
                                        <div class='info'>
                                            <h4>{$notification['message']}</h4>
                                            <small>{$notification['created_at']}</small>
                                        </div>
                                    </div>";
                            }
                            ?>

                        </div>

                        

                        <div class="card-header">
                            <button>Mark all as read</button>
                        </div>
                    </div>
                </div>


                </div>
            </div>
        </main>
    </div>


    <script>
function openModal() {
    document.getElementById("financialRequestModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("financialRequestModal").style.display = "none";
}

function toggleExpenseCategory() {
    const requestType = document.getElementById("requestType").value;
    const expenseCategoriesDiv = document.getElementById("expenseCategoriesDiv");

    if (requestType === "expense") {
        expenseCategoriesDiv.style.display = "block";
    } else {
        expenseCategoriesDiv.style.display = "none";
    }
}
</script>
<script>
    // JavaScript function to load pending requests based on request type
function loadPendingRequests(requestType) {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", `fetch_requests.php?type=${requestType}`, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.querySelector(".table-responsive tbody").innerHTML = xhr.responseText;
            document.querySelector(".card-header h3").textContent = `Pending ${requestType.charAt(0).toUpperCase() + requestType.slice(1)} Requests`;
        }
    };
    xhr.send();
}

// Add event listeners to the cards
document.querySelectorAll('.card-single').forEach(card => {
    card.addEventListener('click', function() {
        const requestType = this.querySelector('h3').textContent.toLowerCase().includes("budget") ? "budget" : "expense";
        loadPendingRequests(requestType);
    });
});
</script>
<script>
function loadRequestData(type) {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", `fetch_request1.php?type=${type}`, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            // Select the tbody element within the table-responsive container
            document.querySelector(".table-responsive tbody").innerHTML = xhr.responseText;
            document.querySelector(".card-header h3").textContent = type.charAt(0).toUpperCase() + type.slice(1) + " Requests";
        }
    };
    xhr.send();
}
</script>
<script>
    function openModal(modalId) {
    document.getElementById(modalId).style.display = "flex";
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = "none";
}

function showTab(tabId) {
    const tabs = document.querySelectorAll(".tab-content");
    const buttons = document.querySelectorAll(".tab-button");

    tabs.forEach(tab => {
        tab.style.display = tab.id === tabId ? "block" : "none";
    });

    buttons.forEach(button => {
        button.classList.toggle("active", button.textContent === tabId.split("Tab")[0]);
    });
}

function loadRequestDetails(id, category) {
            fetch(`fetch_request_details.php?id=${id}&category=${category}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('requestDetailsContent').innerHTML = data;
                    document.getElementById('requestDetailsModal').style.display = 'block';
                })
                .catch(error => console.error('Error loading request details:', error));
        }

function uploadDocument() {
    // Handle document upload
    alert("Document uploaded!");
}

function submitRemark() {
    // Handle remark submission
    const remark = document.getElementById("remarksInput").value;
    if (remark.trim()) {
        // Append remark to remarks history
        const remarksHistory = document.getElementById("remarksHistory");
        const newRemark = document.createElement("div");
        newRemark.textContent = remark;
        remarksHistory.appendChild(newRemark);
        document.getElementById("remarksInput").value = "";
    }
}

function submitRequest() {
    alert("Submitted!");
    closeModal("requestDetailsModal");
}


</script>

</body>
</html>