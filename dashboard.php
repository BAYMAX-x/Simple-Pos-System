<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'config.php';

// KPIs
$totalProducts = $conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'] ?? 0;
$lowStockOnly = $conn->query("SELECT COUNT(*) AS c FROM products WHERE quantity > 0 AND quantity < 5")->fetch_assoc()['c'] ?? 0;
$outOfStock = $conn->query("SELECT COUNT(*) AS c FROM products WHERE quantity = 0")->fetch_assoc()['c'] ?? 0;
$okStock = $conn->query("SELECT COUNT(*) AS c FROM products WHERE quantity >= 5")->fetch_assoc()['c'] ?? 0;

$totalCustomers = $conn->query("SELECT COUNT(*) AS c FROM customers")->fetch_assoc()['c'] ?? 0;
$invoiceCount = $conn->query("SELECT COUNT(*) AS c FROM invoices")->fetch_assoc()['c'] ?? 0;
$totalRevenue = $conn->query("SELECT IFNULL(SUM(total),0) AS s FROM invoices")->fetch_assoc()['s'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <!-- changed: bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
</head>
<body>
    <div class="container">
        <!-- Sidebar Menu -->
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
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <h1 class="h4 mb-0">Dashboard...</h1>
                <span class="text-muted small">Updated <?php echo date('M j, Y g:i A'); ?></span>
            </div>

            <!-- KPI Cards -->
            <div class="row g-3 mb-3">
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3 py-2">
                            <div class="fs-3 text-primary"><i class="bi bi-box-seam"></i></div>
                            <div>
                                <div class="text-muted small">Products</div>
                                <div class="fs-5 fw-semibold"><?php echo (int)$totalProducts; ?></div>
                                <div class="small"><span class="badge bg-warning text-dark">Low: <?php echo (int)$lowStockOnly; ?></span> <span class="badge bg-danger">Out: <?php echo (int)$outOfStock; ?></span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3 py-2">
                            <div class="fs-3 text-success"><i class="bi bi-people"></i></div>
                            <div>
                                <div class="text-muted small">Customers</div>
                                <div class="fs-5 fw-semibold"><?php echo (int)$totalCustomers; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3 py-2">
                            <div class="fs-3 text-info"><i class="bi bi-receipt"></i></div>
                            <div>
                                <div class="text-muted small">Invoices</div>
                                <div class="fs-5 fw-semibold"><?php echo (int)$invoiceCount; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3 py-2">
                            <div class="fs-3 text-danger"><i class="bi bi-currency-dollar"></i></div>
                            <div>
                                <div class="text-muted small">Total Revenue</div>
                                <div class="fs-5 fw-semibold">Rs <?php echo number_format((float)$totalRevenue, 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-3">
                <div class="col-12">
                    <div class="card shadow-sm h-100">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h5 class="card-title mb-0">Inventory Status</h5>
                            </div>
                            <canvas id="stockChart" height="110"></canvas>
                            <div class="d-flex justify-content-center gap-2 mt-2 small">
                                <span class="badge bg-success">OK: <?php echo (int)$okStock; ?></span>
                                <span class="badge bg-warning text-dark">Low: <?php echo (int)$lowStockOnly; ?></span>
                                <span class="badge bg-danger">Out: <?php echo (int)$outOfStock; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    // Stock Chart
                    const stockCtx = document.getElementById('stockChart');
                    if (stockCtx && window.Chart) {
                        new Chart(stockCtx, {
                            type: 'doughnut',
                            data: {
                                labels: ['OK', 'Low', 'Out'],
                                datasets: [{
                                    data: [
                                        <?php echo (int)$okStock; ?>,
                                        <?php echo (int)$lowStockOnly; ?>,
                                        <?php echo (int)$outOfStock; ?>
                                    ],
                                    backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                plugins: { legend: { display: false } },
                                cutout: '62%'
                            }
                        });
                    }
                });
            </script>
        </div>
    </div>
</body>
</html>

