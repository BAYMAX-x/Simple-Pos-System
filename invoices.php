<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'config.php';

$message = '';

// Fetch customers and products
$customers = $conn->query("SELECT * FROM customers");
$products = $conn->query("SELECT * FROM products");

// Create Invoice
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_invoice'])) {
    $customer_id = $_POST['customer_id'];
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];

    $total = 0;
    $valid = true;

    // Validate stock
    foreach ($product_ids as $index => $pid) {
        $qty = $quantities[$index];
        $product = $conn->query("SELECT * FROM products WHERE id = $pid")->fetch_assoc();
        if ($qty > $product['quantity']) {
            $valid = false;
            $message = "Not enough stock for: " . $product['name'];
            break;
        }
        $total += $product['price'] * $qty;
    }

    if ($valid) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert invoice
            $stmt = $conn->prepare("INSERT INTO invoices (customer_id, total) VALUES (?, ?)");
            $stmt->bind_param("id", $customer_id, $total);
            $stmt->execute();
            $invoice_id = $conn->insert_id;

            // Insert items and reduce stock
            foreach ($product_ids as $index => $pid) {
                $qty = $quantities[$index];
                $product = $conn->query("SELECT price FROM products WHERE id = $pid")->fetch_assoc();
                $price = $product['price'];

                // Insert item
                $stmt2 = $conn->prepare("INSERT INTO invoice_items (invoice_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt2->bind_param("iiid", $invoice_id, $pid, $qty, $price);
                $stmt2->execute();

                // Reduce stock
                $conn->query("UPDATE products SET quantity = quantity - $qty WHERE id = $pid");
            }

            $conn->commit();
            $message = "Invoice created successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error creating invoice.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Invoice</title>
    <!-- changed: bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    <script>
        function addProductRow() {
            const container = document.getElementById('products-container');
            const row = document.createElement('div');
            row.className = 'd-flex gap-2 align-items-center mb-2';
            row.innerHTML = `
                <select name="product_id[]" class="form-select" required>
                    <?php
                    $products->data_seek(0);
                    while ($p = $products->fetch_assoc()) {
                        echo "<option value='{$p['id']}'>{$p['name']} ({$p['code']}) - Rs " . number_format((float)$p['price'], 2) . "</option>";
                    }
                    ?>
                </select>
                <input type="number" name="quantity[]" class="form-control" style="max-width:110px;" placeholder="Qty" min="1" required>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(row);
        }
    </script>
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
        <h2>Create Invoice</h2>
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <h3>Select Customer</h3>
            <select name="customer_id" class="form-select mb-3" required>
                <?php while ($c = $customers->fetch_assoc()): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                <?php endwhile; ?>
            </select>

            <h3>Select Products</h3>
            <div id="products-container" class="mb-3">
                <div class="d-flex gap-2 align-items-center mb-2">
                    <select name="product_id[]" class="form-select" required>
                        <?php
                        $products->data_seek(0);
                        while ($p = $products->fetch_assoc()) {
                            echo "<option value='{$p['id']}'>{$p['name']} ({$p['code']}) - Rs " . number_format((float)$p['price'], 2) . "</option>";
                        }
                        ?>
                    </select>
                    <input type="number" name="quantity[]" class="form-control" style="max-width:110px;" placeholder="Qty" min="1" required>
                </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm mb-3" onclick="addProductRow()">+ Add Another Product</button><br>
            <button type="submit" name="create_invoice" class="btn btn-primary">Create Invoice</button>
        </form>

        <hr>
        <h3>Recent Invoices</h3>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Date</th>
                </tr>
                <?php
                $result = $conn->query("
                    SELECT i.id, c.name as customer, i.total, i.created_at 
                    FROM invoices i
                    JOIN customers c ON i.customer_id = c.id
                    ORDER BY i.id DESC
                ");
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['customer']}</td>
                        <td>Rs " . number_format((float)$row['total'], 2) . "</td>
                        <td>{$row['created_at']}</td>
                    </tr>";
                }
                ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>