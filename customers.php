<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_customer'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $phone, $address);
    if ($stmt->execute()) {
        $message = "Customer added!";
    }
}

if (isset($_GET['delete_customer'])) {
    $id = $_GET['delete_customer'];
    $conn->query("DELETE FROM customers WHERE id = $id");
    $message = "Customer deleted.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="users.php">User Management</a></li>
            <li><a href="customers.php">Customer Management</a></li>
            <li><a href="products.php">Product Management</a></li>
            <li><a href="invoices.php">Invoice Management</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <div class="main">
        <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center mb-3">
            <h2 class="mb-0">Customer Management</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">Add Customer</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCustomerLabel">Add New Customer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="customerName" class="form-label">Customer Name</label>
                                <input type="text" id="customerName" name="name" class="form-control" placeholder="Customer Name" required>
                            </div>
                            <div class="mb-3">
                                <label for="customerEmail" class="form-label">Email</label>
                                <input type="email" id="customerEmail" name="email" class="form-control" placeholder="Email">
                            </div>
                            <div class="mb-3">
                                <label for="customerPhone" class="form-label">Phone</label>
                                <input type="text" id="customerPhone" name="phone" class="form-control" placeholder="Phone">
                            </div>
                            <div>
                                <label for="customerAddress" class="form-label">Address</label>
                                <textarea id="customerAddress" name="address" class="form-control" placeholder="Address"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_customer" class="btn btn-primary">Save Customer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <h3>Customers List</h3>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Action</th>
                </tr>
                <?php
                $result = $conn->query("SELECT * FROM customers");
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['phone']}</td>
                        <td><a href='?delete_customer={$row['id']}' class='btn btn-sm btn-outline-danger' onclick='return confirm(\"Delete customer?\")'>Delete</a></td>
                    </tr>";
                }
                ?>
            </table>
        </div>
    </div>
</div>

<script>
    const addCustomerModal = document.getElementById('addCustomerModal');
    addCustomerModal?.addEventListener('shown.bs.modal', () => {
        document.getElementById('customerName')?.focus();
    });
</script>
</body>
</html>
