<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: invoices.php");
    exit();
}

$invoiceId = (int)$_GET['id'];
include 'config.php';

// Fetch invoice + customer
$stmt = $conn->prepare("SELECT i.id, i.total, i.created_at, c.name AS customer_name, c.email, c.phone, c.address FROM invoices i JOIN customers c ON i.customer_id = c.id WHERE i.id = ?");
$stmt->bind_param("i", $invoiceId);
$stmt->execute();
$invoiceResult = $stmt->get_result();
$invoice = $invoiceResult->fetch_assoc();

if (!$invoice) {
    $stmt->close();
    $conn->close();
    header("Location: invoices.php");
    exit();
}

// Fetch invoice items
$stmtItems = $conn->prepare("SELECT ii.quantity, ii.price, p.name, p.code FROM invoice_items ii JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = ?");
$stmtItems->bind_param("i", $invoiceId);
$stmtItems->execute();
$itemsResult = $stmtItems->get_result();
$items = [];
$subtotal = 0;
while ($row = $itemsResult->fetch_assoc()) {
    $lineTotal = (float)$row['price'] * (int)$row['quantity'];
    $row['line_total'] = $lineTotal;
    $subtotal += $lineTotal;
    $items[] = $row;
}

$stmt->close();
$stmtItems->close();
$conn->close();

$storedTotal = (float)$invoice['total'];
$calculatedTotal = $subtotal;
$grandTotal = $storedTotal > 0 ? $storedTotal : $calculatedTotal;
$adjustment = round($calculatedTotal - $grandTotal, 2);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #<?php echo htmlspecialchars($invoice['id']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f6f8fb;
        }
        .invoice-wrapper {
            max-width: 960px;
            margin: 24px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.12);
            padding: 36px 42px;
        }
        .invoice-header {
            border-bottom: 1px solid #e7ecf3;
            padding-bottom: 24px;
        }
        .invoice-brand {
            font-size: 28px;
            font-weight: 700;
            color: #1a73e8;
        }
        .badge-soft {
            background: rgba(13, 110, 253, 0.12);
            color: #0d6efd;
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 12px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .invoice-meta th {
            width: 140px;
            color: #6c757d;
            font-weight: 600;
            font-size: 13px;
        }
        .invoice-meta td {
            font-weight: 500;
            font-size: 13px;
        }
        .table-invoice thead {
            background: #f1f4f9;
        }
        .table-invoice th {
            font-size: 13px;
            color: #6c757d;
            letter-spacing: 0.02em;
        }
        .table-invoice td {
            font-size: 14px;
        }
        .totals-box {
            max-width: 320px;
            margin-left: auto;
        }
        @media print {
            body {
                background: #fff;
            }
            .invoice-wrapper {
                margin: 0;
                box-shadow: none;
                border-radius: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="no-print d-flex justify-content-between align-items-center py-3">
        <a href="invoices.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Invoices</a>
        <div class="d-flex gap-2">
            <button class="btn btn-dark btn-sm" onclick="window.print()">Print / Save PDF</button>
        </div>
    </div>
</div>

<section class="invoice-wrapper">
    <div class="invoice-header d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <div class="invoice-brand">Simple POS</div>
            <div class="text-muted small">123 Market Street, Colombo<br>support@simplepos.demo<br>+94 77 123 4567</div>
        </div>
        <div class="text-end">
            <span class="badge-soft">Invoice</span>
            <h2 class="h4 mt-2 mb-1">#<?php echo (int)$invoice['id']; ?></h2>
            <div class="text-muted">Issued <?php echo date('M d, Y g:i A', strtotime($invoice['created_at'])); ?></div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <h6 class="text-uppercase text-muted small">Billed To</h6>
            <div class="fw-semibold"><?php echo htmlspecialchars($invoice['customer_name']); ?></div>
            <div class="text-muted small">
                <?php if (!empty($invoice['email'])) echo htmlspecialchars($invoice['email']) . '<br>'; ?>
                <?php if (!empty($invoice['phone'])) echo htmlspecialchars($invoice['phone']) . '<br>'; ?>
                <?php if (!empty($invoice['address'])) echo nl2br(htmlspecialchars($invoice['address'])); ?>
            </div>
        </div>
        <div class="col-md-6 mt-3 mt-md-0">
            <h6 class="text-uppercase text-muted small">Details</h6>
            <table class="table table-borderless table-sm invoice-meta mb-0">
                <tr>
                    <th>Invoice #</th>
                    <td><?php echo (int)$invoice['id']; ?></td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td><?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></td>
                </tr>
                <tr>
                    <th>Time</th>
                    <td><?php echo date('g:i A', strtotime($invoice['created_at'])); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><span class="badge bg-success-subtle text-success-emphasis">Paid</span></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="table-responsive mt-4">
        <table class="table table-invoice align-middle">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Item</th>
                    <th scope="col" class="text-center">Code</th>
                    <th scope="col" class="text-end">Unit Price</th>
                    <th scope="col" class="text-center">Qty</th>
                    <th scope="col" class="text-end">Line Total</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($items) === 0): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">No items recorded for this invoice.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td class="fw-medium"><?php echo htmlspecialchars($item['name']); ?></td>
                    <td class="text-center text-muted small"><?php echo htmlspecialchars($item['code']); ?></td>
                    <td class="text-end">Rs <?php echo number_format((float)$item['price'], 2); ?></td>
                    <td class="text-center"><?php echo (int)$item['quantity']; ?></td>
                    <td class="text-end">Rs <?php echo number_format((float)$item['line_total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="p-3 rounded-3 bg-light">
                <h6 class="text-uppercase small text-muted mb-2">Payment Info</h6>
                <div class="small text-muted">
                    Payment Method: Cash<br>
                    Notes: Thank you for your purchase!
                </div>
            </div>
        </div>
        <div class="col-md-6 mt-3 mt-md-0">
            <div class="totals-box">
                <table class="table table-borderless table-sm">
                    <tr>
                        <th class="text-muted">Subtotal:</th>
                        <td class="text-end">Rs <?php echo number_format($calculatedTotal, 2); ?></td>
                    </tr>
                    <?php if (abs($adjustment) >= 0.01): ?>
                    <tr>
                        <th class="text-muted"><?php echo $adjustment > 0 ? 'Discount:' : 'Surcharge:'; ?></th>
                        <td class="text-end">Rs <?php echo number_format(abs($adjustment), 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="table-light">
                        <th class="text-muted">Grand Total:</th>
                        <td class="text-end fw-semibold">Rs <?php echo number_format($grandTotal, 2); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-5 text-center small text-muted">
        This invoice was generated by Simple POS System. For assistance contact support@simplepos.demo.
    </div>
</section>
</body>
</html>





