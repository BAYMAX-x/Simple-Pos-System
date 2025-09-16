<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'config.php';

$message = '';

$customers = $conn->query("SELECT * FROM customers");
$products = $conn->query("SELECT * FROM products");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_invoice'])) {
    $customer_id = $_POST['customer_id'];
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];

    $total = 0;
    $valid = true;

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
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("INSERT INTO invoices (customer_id, total) VALUES (?, ?)");
            $stmt->bind_param("id", $customer_id, $total);
            $stmt->execute();
            $invoice_id = $conn->insert_id;

            foreach ($product_ids as $index => $pid) {
                $qty = $quantities[$index];
                $product = $conn->query("SELECT price FROM products WHERE id = $pid")->fetch_assoc();
                $price = $product['price'];

                $stmt2 = $conn->prepare("INSERT INTO invoice_items (invoice_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt2->bind_param("iiid", $invoice_id, $pid, $qty, $price);
                $stmt2->execute();

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
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="mb-0">Create Invoice</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInvoiceModal">New Invoice</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="modal fade" id="createInvoiceModal" tabindex="-1" aria-labelledby="createInvoiceLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createInvoiceLabel">Create Invoice</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="invoiceCustomer" class="form-label">Select Customer</label>
                                <select name="customer_id" id="invoiceCustomer" class="form-select" required>
                                    <?php
                                    if ($customers && $customers->num_rows) {
                                        $customers->data_seek(0);
                                        while ($c = $customers->fetch_assoc()): ?>
                                            <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                                        <?php endwhile;
                                    } else {
                                        echo '<option value="">No customers available</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <h6 class="fw-semibold">Select Products</h6>
                            <div id="products-container" class="mb-3">
                                <div class="d-flex gap-2 align-items-center mb-2">
                                    <select name="product_id[]" class="form-select" required>
                                        <?php
                                        if ($products && $products->num_rows) {
                                            $products->data_seek(0);
                                            while ($p = $products->fetch_assoc()) {
                                                echo "<option value='{$p['id']}'>{$p['name']} ({$p['code']}) - Rs " . number_format((float)$p['price'], 2) . "</option>";
                                            }
                                        } else {
                                            echo '<option value="">No products available</option>';
                                        }
                                        ?>
                                    </select>
                                    <input type="number" name="quantity[]" class="form-control" style="max-width:110px;" placeholder="Qty" min="1" required>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addProductRow()">+ Add Another Product</button>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="create_invoice" class="btn btn-primary">Create Invoice</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <hr>
        <h3>Recent Invoices</h3>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Actions</th>
                    <th>Date</th>
                </tr>
                <?php
                $result = $conn->query("
                    SELECT i.id, c.name as customer, i.total, i.created_at 
                    FROM invoices i
                    JOIN customers c ON i.customer_id = c.id
                    ORDER BY i.id DESC
                ");
                while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo (int)$row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['customer']); ?></td>
                        <td>Rs <?php echo number_format((float)$row['total'], 2); ?></td>
                        <td><a href="invoice_view.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-primary">View Bill</a></td>
                        <td><?php echo $row['created_at']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</div>

<script>
    const invoiceModal = document.getElementById('createInvoiceModal');
    invoiceModal?.addEventListener('shown.bs.modal', () => {
        document.getElementById('invoiceCustomer')?.focus();
    });
</script>
</body>
</html>
