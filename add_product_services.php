<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1">
    <title>Integrated Finance System</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/add_product_services.css">
    <link rel= "stylesheet" href= "https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css" >
</head>
<body>

<?php
include "available_rented.php";

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

<!-- Add Product Modal -->
<div id="addProductModal" class="modal">
    <div class="modal-content">
        <span class="close1" onclick="document.getElementById('addProductModal').style.display='none'">&times;</span>
        <h2>Add Product</h2>
        <form action="add_product.php" method="post">
            <label for="productName">Name</label>
            <input type="text" id="productName" name="name" required>

            <label for="productCategory">Category</label>
            <input type="text" id="productCategory" name="category" required>

            <label for="purchaseCost">Purchase Cost (PHP)</label>
            <input type="number" id="purchaseCost" name="purchase_cost" required>

            <label for="dailyRate">Daily Rate (PHP)</label>
            <input type="number" id="dailyRate" name="daily_rate" required>

            <label for="brand">Brand</label>
            <input type="text" id="brand" name="brand" required>

            <label for="model">Model</label>
            <input type="text" id="model" name="model" required>

            <label for="color">Color</label>
            <input type="text" id="color" name="color" required>

            <label for="plateNumber">Plate Number</label>
            <input type="text" id="plateNumber" name="plate_number" required>

            <label for="productStatus">Product Status</label>
            <select id="productStatus" name="product_status" required>
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
            </select>

            <button type="submit">Add Product</button>
        </form>
    </div>
</div>

<!-- /Add Product Modal -->

<!-- Add Service Modal -->
<div id="addServiceModal" class="modal">
    <div class="modal-content">
        <span class="close1" onclick="document.getElementById('addServiceModal').style.display='none'">&times;</span>
        <h2>Add Service</h2>
        <form action="add_service.php" method="post">
            <label for="serviceName">Name</label>
            <input type="text" id="serviceName" name="name" required>
            
            <label for="serviceDescription">Description</label>
            <textarea id="serviceDescription" name="description" required></textarea>
            
            <label for="servicePrice">Price (PHP)</label>
            <input type="number" id="servicePrice" name="price" required>
            
            <button type="submit">Add Service</button>
        </form>
    </div>
</div>
<!-- /Add Service Modal -->

<!-- Product Modal for All Products -->
<div id="viewAllProductModal" class="modal">
    <div class="modal-content">
        <span class="close1" onclick="document.getElementById('viewAllProductModal').style.display='none'">&times;</span>
        <h2>All Products</h2>
        <div class="table-responsive">
            <table width="100%">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Purchase Cost</th>
                        <th>Daily Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    include('assets/databases/dbconfig.php');
                    $productQueryAll = "SELECT * FROM products ORDER BY created_at DESC";
                    $productResultAll = mysqli_query($connection, $productQueryAll);
                    while ($row = mysqli_fetch_assoc($productResultAll)) {
                        echo "<tr>
                            <td>" . htmlspecialchars($row['name']) . "</td>
                            <td>" . htmlspecialchars($row['status']) . "</td>
                            <td>" . number_format($row['purchase_cost'], 2) . "</td>
                            <td>" . number_format($row['daily_rate'], 2) . "</td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- /Product Modal for All Products -->

<!-- Service Modal for All Services -->
<div id="viewAllServiceModal" class="modal">
    <div class="modal-content">
        <span class="close1" onclick="document.getElementById('viewAllServiceModal').style.display='none'">&times;</span>
        <h2>All Services</h2>
        <div class="table-responsive">
            <table width="100%">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    include('assets/databases/dbconfig.php');
                    $serviceQueryAll = "SELECT * FROM additional_services ORDER BY created_at DESC";
                    $serviceResultAll = mysqli_query($connection, $serviceQueryAll);
                    while ($row = mysqli_fetch_assoc($serviceResultAll)) {
                        echo "<tr>
                            <td>" . htmlspecialchars($row['name']) . "</td>
                            <td>" . htmlspecialchars($row['description']) . "</td>
                            <td>" . number_format($row['price'], 2) . "</td>
                            <td><button class='edit-btn las la-edit' data-id='" . $row['id'] . "' data-name='" . htmlspecialchars($row['name']) . "' data-description='" . htmlspecialchars($row['description']) . "' data-price='" . $row['price'] . "'></button></td>

                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- /Service Modal for All Services -->

<!-- Edit Modal for Services -->
<div id="editModal" class="modal">
    <div class="modal-content">
    <span class="close1" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
        <h2>Edit Service</h2>
        <form id="editForm" action="update_service.php" method="POST">
            <input type="hidden" id="serviceId" name="id">
            <label for="name">Service Name:</label>
            <input type="text" id="name" name="name" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea>

            <label for="price">Price:</label>
            <input type="number" id="price" name="price" step="0.01" required>

            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>
<!-- /Edit Modal for Services -->
<!--Modal-->

    <div class="main-content">
        <header>
            <div class="header-title">
                <h2>
                <label for="nav-toggle">
                    <span class="las la-bars"></span>
                </label>
                Product & Servies
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
    <!-- Product Card -->
    <div class="card-single" onclick="showProductDetails('all')">
        <div>
            <h3>Product</h3>
            <h4>
                <?php
                // Query to count the total number of products
                include('assets/databases/dbconfig.php');
                $productQuery = "SELECT COUNT(*) AS total_products FROM products";
                $productResult = mysqli_query($connection, $productQuery);
                
                // Fetch the result and display the total
                if ($productRow = mysqli_fetch_assoc($productResult)) {
                    echo $productRow['total_products'];
                } else {
                    echo '0';
                }
                ?>
            </h4>
            <span>Total Product</span>
        </div>
        <div>
            <span class="las la-truck"></span>
        </div>
    </div>

    <div class="card-single" onclick="showProductDetails('available')">
        <div>
            <h3>Available Product</h3>
            <h4><?php echo $totalAvailable; ?></h4> <!-- Display total available products -->
            <span>Total available</span>
        </div>
        <div>
            <span class="las la-truck"></span>
        </div>
    </div>

    <div class="card-single" onclick="showProductDetails('rented')">
        <div>
            <h3>Rented Product</h3>
            <h4><?php echo $totalRented; ?></h4> <!-- Display total rented products -->
            <span>Total rented</span>
        </div>
        <div>
            <span class="las la-truck"></span>
        </div>
    </div>

    <!-- Services Card -->
    <div class="card-single">
        <div>
            <h3>Services</h3>
            <h4>
                <?php
                // Query to count the total number of services
                $serviceQuery = "SELECT COUNT(*) AS total_services FROM additional_services";
                $serviceResult = mysqli_query($connection, $serviceQuery);
                
                // Fetch the result and display the total
                if ($serviceRow = mysqli_fetch_assoc($serviceResult)) {
                    echo $serviceRow['total_services'];
                } else {
                    echo '0';
                }
                ?>
            </h4>
            <span>Total Services</span>
        </div>
        <div>
            <span class="las la-tools"></span>
        </div>
    </div>
</div>

        

            <div class="recent-grid">
    <div class="projects">
        <div class="card">
        <div class="card-header">
                <h3>Product List</h3>
                <button id="prod-btn" onclick="document.getElementById('addProductModal').style.display='block'">Add Product <span class="las la-plus"></span></button>
            </div>

            <div class="card-body">
                <div class="table-responsive" id="product-table-container">
                    <!-- The table will be dynamically populated based on the clicked card -->
                </div>
            </div>
            <div class="card-header">
                <button id="view-prod-btn" onclick="document.getElementById('viewAllProductModal').style.display='block'">See all <span class="las la-arrow-right"></span></button>
            </div>
        </div>
    </div>



                <div class="customers">
                        <div class="card">
                            <div class="card-header">
                                <h3>Additional Services</h3>
                                <button id="serv-btn" onclick="document.getElementById('addServiceModal').style.display='block'">Add Service <span class="las la-plus"></span></button>
                            </div>

                            <div class="card-body">
                            <?php
                include('assets/databases/dbconfig.php');
                
                // Query to get 5 additional services
                $serviceQuery = "SELECT * FROM additional_services ORDER BY created_at DESC LIMIT 2";
                $serviceResult = mysqli_query($connection, $serviceQuery);   
                
                // Check if query executed and returned results
                if (mysqli_num_rows($serviceResult) > 0) {
                    // Loop through the result set
                    while ($row = mysqli_fetch_assoc($serviceResult)) {
                        echo '<div class="customer">';
                        echo '  <div class="info">';
                        echo '      <img src="img/add_serv.png" width="50px" height="50px" alt="Profile Picture">';
                        echo '      <div>';
                        echo '          <h4>' . $row['name'] . '</h4>';
                        echo '          <small>' . $row['description'] . '</small>';
                        echo '      </div>';
                        echo '  </div>';
                        echo '  <div class="contact">';
                        echo '      <button class="edit-btn las la-edit" data-id="' . $row['id'] . '" data-name="' . htmlspecialchars($row['name']) . '" data-description="' . htmlspecialchars($row['description']) . '" data-price="' . $row['price'] . '" data-toggle="modal" data-target="#editServiceModal"></button>';
                        echo '  </div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No additional services available.</p>';
                }
                ?>
        </div>

        <div class="card-header">
            <button id="view-serv-btn" onclick="document.getElementById('viewAllServiceModal').style.display='block'">See all <span class="las la-arrow-right"></span></button>
        </div>
    </div>
</div>

<?php
$connection->close();
?>

                </div>
            </div>
        </main>
    </div>

    <script>
    document.querySelector("prod-btn").onclick = function() {
        document.getElementById("addProductModal").style.display = "block";
    };
    
    document.querySelector("serv-btn").onclick = function() {
        document.getElementById("addServiceModal").style.display = "block";
    };
    
    window.onclick = function(event) {
        if (event.target == document.getElementById("addProductModal")) {
            document.getElementById("addProductModal").style.display = "none";
        } else if (event.target == document.getElementById("addServiceModal")) {
            document.getElementById("addServiceModal").style.display = "none";
        }
    };

    document.querySelector("view-prod-btn").onclick = function() {
        document.getElementById("viewAllProductModal").style.display = "block";
    };

    // Open service modal
    document.querySelector("view-serv-btn").onclick = function() {
        document.getElementById("viewAllServiceModal").style.display = "block";
    };

    // Close modal when clicked outside
    window.onclick = function(event) {
        if (event.target == document.getElementById("viewAllProductModal")) {
            document.getElementById("viewAllProductModal").style.display = "none";
        } else if (event.target == document.getElementById("viewAllServiceModal")) {
            document.getElementById("viewAllServiceModal").style.display = "none";
        }
    };
</script>

<script>
document.querySelectorAll('.edit-btn').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const name = this.getAttribute('data-name');
        const description = this.getAttribute('data-description');
        const price = this.getAttribute('data-price');

        // Populate the modal with data
        document.getElementById('serviceId').value = id;
        document.getElementById('name').value = name;
        document.getElementById('description').value = description;
        document.getElementById('price').value = price;

        // Open the modal
        document.getElementById('editModal').style.display = 'block';
    });
});


// Close modal when clicking outside of it
window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        document.getElementById('editModal').style.display = 'none';
    }
};
</script>

<script>
// JavaScript function to handle card clicks and display product details in table
function showProductDetails(type) {
    // Create XMLHttpRequest to fetch the products based on type
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_products.php?type=' + type, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            // On success, populate the table with the response
            document.getElementById('product-table-container').innerHTML = xhr.responseText;
        }
    };
    xhr.send();
}
</script>

</body>
</html>