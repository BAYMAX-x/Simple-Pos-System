<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $code = $_POST['code'];
    $cost = $_POST['cost'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $desc = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO products (name, code, cost, price, quantity, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddis", $name, $code, $cost, $price, $quantity, $desc);
    if ($stmt->execute()) {
        $message = "Product added!";
    }
}

if (isset($_GET['delete_product'])) {
    $id = $_GET['delete_product'];
    $conn->query("DELETE FROM products WHERE id = $id");
    $message = "Product deleted.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Management</title>
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
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <h2 class="mb-0">Product Management</h2>
            <div class="d-flex align-items-center gap-2">
                <input id="productSearch" type="search" class="form-control" placeholder="Search name or code" style="max-width: 280px;">
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addProductModal">Add Product</button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addProductLabel">Add New Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="productName">Product Name</label>
                                    <input type="text" id="productName" name="name" class="form-control" placeholder="e.g. Wireless Mouse" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="productCode">Product Code</label>
                                    <input type="text" id="productCode" name="code" class="form-control" placeholder="e.g. WM-100" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="productCost">Cost</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rs</span>
                                        <input type="number" step="0.01" id="productCost" name="cost" class="form-control" placeholder="0.00">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="productPrice">Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rs</span>
                                        <input type="number" step="0.01" id="productPrice" name="price" class="form-control" placeholder="0.00">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="productQuantity">Quantity</label>
                                    <input type="number" id="productQuantity" name="quantity" class="form-control" placeholder="e.g. 25" min="0" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="productDescription">Description</label>
                                    <textarea id="productDescription" name="description" class="form-control" rows="2" placeholder="Optional details"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_product" class="btn btn-success">Save Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between">
            <h3 class="h5 mb-0">Products</h3>
        </div>
        <div class="table-responsive mt-2">
            <table class="table table-hover align-middle table-sticky">
                <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th>Name</th>
                        <th style="width:160px;">Code</th>
                        <th style="width:140px;">Price</th>
                        <th style="width:140px;">Qty</th>
                        <th style="width:120px;">Action</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                <?php
                $result = $conn->query("SELECT * FROM products ORDER BY id DESC");
                while ($row = $result->fetch_assoc()) {
                    $qty = (int)$row['quantity'];
                    $qtyBadgeClass = 'bg-success';
                    $qtyLabel = $qty;
                    if ($qty === 0) { $qtyBadgeClass = 'bg-danger'; $qtyLabel = 'Out'; }
                    elseif ($qty < 5) { $qtyBadgeClass = 'bg-warning text-dark'; $qtyLabel = 'Low (' . $qty . ')'; }
                ?>
                    <tr data-name="<?php echo htmlspecialchars($row['name']); ?>" data-code="<?php echo htmlspecialchars($row['code']); ?>">
                        <td><?php echo (int)$row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['code']); ?></span></td>
                        <td>Rs <?php echo number_format((float)$row['price'], 2); ?></td>
                        <td><span class="badge <?php echo $qtyBadgeClass; ?>"><?php echo $qtyLabel; ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo (int)$row['id']; ?>">Delete</button>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this product?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a id="confirmDelete" href="#" class="btn btn-danger">Delete</a>
                    </div>
                </div>
            </div>
        </div>

        <script>
            const searchInput = document.getElementById('productSearch');
            const tbody = document.getElementById('productsTableBody');
            searchInput?.addEventListener('input', () => {
                const q = searchInput.value.toLowerCase();
                for (const row of tbody.rows) {
                    const name = row.getAttribute('data-name')?.toLowerCase() || '';
                    const code = row.getAttribute('data-code')?.toLowerCase() || '';
                    row.style.display = (name.includes(q) || code.includes(q)) ? '' : 'none';
                }
            });

            const deleteModalEl = document.getElementById('deleteModal');
            if (deleteModalEl) {
                deleteModalEl.addEventListener('show.bs.modal', (event) => {
                    const button = event.relatedTarget;
                    const id = button?.getAttribute('data-id');
                    const link = deleteModalEl.querySelector('#confirmDelete');
                    if (id && link) link.setAttribute('href', `?delete_product=${id}`);
                });
            }

            const addProductModal = document.getElementById('addProductModal');
            addProductModal?.addEventListener('shown.bs.modal', () => {
                document.getElementById('productName')?.focus();
            });
        </script>
    </div>
</div>
</body>
</html>

