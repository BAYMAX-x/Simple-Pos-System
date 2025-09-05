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
    <!-- changed: bootstrap -->
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
        <h2>Customer Management</h2>
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" class="mb-3">
            <input type="text" name="name" class="form-control mb-2" placeholder="Customer Name" required>
            <input type="email" name="email" class="form-control mb-2" placeholder="Email">
            <input type="text" name="phone" class="form-control mb-2" placeholder="Phone">
            <textarea name="address" class="form-control mb-2" placeholder="Address"></textarea>
            <button type="submit" name="add_customer" class="btn btn-primary">Add Customer</button>
        </form>

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
</body>
</html>