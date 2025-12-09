<?php
include_once 'db_config.php';
include_once 'admin_authentication.php';

if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'admin@example.com';
}
$admin_email = $_SESSION['admin_email'];

// =======================
// DELETE ORDER
// =======================
if (isset($_POST['delete_order'])) {
    $order_id = (int)$_POST['order_id'];

    $stmt = $con->prepare("DELETE FROM orders WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            setcookie('success', "Order deleted successfully", time() + 5);
        } else {
            setcookie('error', "Error deleting order: " . $stmt->error, time() + 5);
        }
        echo "<script>window.location.href='admin_orders.php';</script>";
        $stmt->close();
    }
}

// =======================
// UPDATE ORDER STATUS
// =======================
if (isset($_POST['update_order'])) {
    $order_id = (int)$_POST['order_id'];
    $order_status = trim($_POST['order_status']);
    $payment_status = trim($_POST['payment_status']);

    $stmt = $con->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssi", $order_status, $payment_status, $order_id);

        if ($stmt->execute()) {
            setcookie('success', "Order updated successfully", time() + 5);
        } else {
            setcookie('error', "Error updating order: " . $stmt->error, time() + 5);
        }
        echo "<script>window.location.href='admin_orders.php';</script>";
        $stmt->close();
    }
}

// =======================
// FETCH ORDERS
// =======================
$rows = [];
$allRows = [];
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$total = 0;
$totalPages = 1;
$offset = ($page - 1) * $perPage;

if ($search !== '') {
    $stmt = $con->prepare("SELECT * FROM orders WHERE order_number LIKE ? OR delivery_name LIKE ? OR user_email LIKE ? ORDER BY order_date DESC");
    if ($stmt) {
        $searchParam = "%$search%";
        $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) $allRows[] = $r;
            $res->free();
        }
        $stmt->close();
    }

    $total = count($allRows);
    $totalPages = (int) ceil(max(1, $total) / $perPage);
    $offset = ($page - 1) * $perPage;
    $rows = array_slice($allRows, $offset, $perPage);
} else {
    $countStmt = $con->prepare("SELECT COUNT(*) AS cnt FROM orders");
    if ($countStmt) {
        $countStmt->execute();
        $cres = $countStmt->get_result();
        if ($cres) {
            $crow = $cres->fetch_assoc();
            $total = intval($crow['cnt'] ?? 0);
            $cres->free();
        }
        $countStmt->close();
    }

    $totalPages = (int) ceil(max(1, $total) / $perPage);
    $offset = ($page - 1) * $perPage;

    $sel = $con->prepare("SELECT * FROM orders ORDER BY order_date DESC LIMIT ? OFFSET ?");
    if ($sel) {
        $sel->bind_param("ii", $perPage, $offset);
        $sel->execute();
        $res = $sel->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) $rows[] = $r;
            $res->free();
        }
        $sel->close();
    }
}

$totalPages = $totalPages ?? 1;
$offset = $offset ?? 0;

ob_start();
?>

<div class="">
    <!-- Heading -->
    <div class="mb-3">
        <h4 class="mb-0" style="color:var(--teal)">Manage Orders</h4>
    </div>

    <!-- Search Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="admin_dashboard.php" class="btn btn-outline-secondary" style="font-size:small;" style="font-size: small;">
            <i class="fa fa-arrow-left me-1"></i> Back
        </a>
        <form class="d-flex" method="GET" action="admin_orders.php">
            <input type="text" name="search" style="font-size:small;" class="form-control me-2"
                placeholder="Search order #, customer name, email"
                value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;">
                Search
            </button>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:5%;">S.No</th>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Order Status</th>
                        <th>Payment Status</th>
                        <th>Amount</th>
                        <th style="width:8%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No orders found.</td>
                        </tr>
                        <?php else: foreach ($rows as $i => $order): ?>
                            <!-- Table Row -->
                            <tr class="order-row" style="cursor: pointer;"
                                data-bs-toggle="collapse"
                                data-bs-target="#orderDetails<?= $order['id'] ?>">
                                <td><?= htmlspecialchars($offset + $i + 1) ?></td>
                                <td><strong class="text-primary">#<?= htmlspecialchars($order['order_number']) ?></strong></td>
                                <td><?= htmlspecialchars($order['delivery_name']) ?></td>
                                <td>
                                    <span class="badge bg-<?=
                                                            $order['order_status'] == 'Delivered' ? 'success' : ($order['order_status'] == 'Cancelled' ? 'danger' : ($order['order_status'] == 'Returned' ? 'secondary' : ($order['order_status'] == 'Shipped' ? 'primary' : ($order['order_status'] == 'Processing' || $order['order_status'] == 'Confirmed' ? 'info' : 'warning'))))
                                                            ?>">
                                        <?= htmlspecialchars($order['order_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?=
                                                            $order['payment_status'] == 'Paid' ? 'success' : ($order['payment_status'] == 'Failed' ? 'danger' : ($order['payment_status'] == 'Refunded' ? 'secondary' : 'warning'))
                                                            ?>">
                                        <?= htmlspecialchars($order['payment_status']) ?>
                                    </span>
                                </td>
                                <td><strong style="color:#0d9488;">₹<?= number_format($order['total_amount'], 2) ?></strong></td>
                                <td>
                                    <i class="fa fa-chevron-down text-muted"></i>
                                </td>
                            </tr>

                            <!-- Accordion Details Row -->
                            <tr class="collapse-row">
                                <td colspan="7" class="p-0 border-0">
                                    <div class="collapse" id="orderDetails<?= $order['id'] ?>">
                                        <div class="card card-body border-0" style="background-color:#f9fafb;">

                                            <!-- Order Details -->
                                            <div class="row mb-3">
                                                <!-- Customer Info -->
                                                <div class="col-md-4">
                                                    <div class="card border-0 h-100" style="background-color:#f0fdfa;">
                                                        <div class="card-body">
                                                            <h6 class="fw-bold mb-3" style="color:#0d9488;">
                                                                <i class="fa fa-user me-2"></i>Customer Information
                                                            </h6>
                                                            <p class="mb-2"><strong>Name:</strong> <?= htmlspecialchars($order['delivery_name']) ?></p>
                                                            <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($order['delivery_email']) ?></p>
                                                            <p class="mb-0"><strong>Mobile:</strong> <?= htmlspecialchars($order['delivery_mobile']) ?></p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Order Info -->
                                                <div class="col-md-4">
                                                    <div class="card border-0 h-100" style="background-color:#fef3c7;">
                                                        <div class="card-body">
                                                            <h6 class="fw-bold mb-3" style="color:#d4af37;">
                                                                <i class="fa fa-file-invoice me-2"></i>Order Information
                                                            </h6>
                                                            <p class="mb-2"><strong>Order #:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
                                                            <p class="mb-2"><strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($order['order_date'])) ?></p>
                                                            <p class="mb-0"><strong>Payment:</strong> <?= strtoupper($order['payment_method']) ?></p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Delivery Address -->
                                                <div class="col-md-4">
                                                    <div class="card border-0 h-100" style="background-color:#e0f2fe;">
                                                        <div class="card-body">
                                                            <h6 class="fw-bold mb-3" style="color:#0369a1;">
                                                                <i class="fa fa-map-marker-alt me-2"></i>Delivery Address
                                                            </h6>
                                                            <p class="mb-0"><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Order Items -->
                                            <div class="mb-3">
                                                <h6 class="fw-bold mb-3" style="color:#0d9488;">
                                                    <i class="fa fa-box me-2"></i>Order Items
                                                </h6>
                                                <?php
                                                $items_query = "SELECT oi.*, p.name as product_name 
                                                           FROM order_items oi 
                                                           LEFT JOIN products p ON oi.product_id = p.id 
                                                           WHERE oi.order_id = ?";
                                                $items_stmt = $con->prepare($items_query);
                                                $items_stmt->bind_param("i", $order['id']);
                                                $items_stmt->execute();
                                                $items_result = $items_stmt->get_result();
                                                ?>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered bg-white">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Product</th>
                                                                <th>Price</th>
                                                                <th>Discount</th>
                                                                <th>Qty</th>
                                                                <th>Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php while ($item = $items_result->fetch_assoc()): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                                                    <td>₹<?= number_format($item['price'], 2) ?></td>
                                                                    <td class="text-danger">-₹<?= number_format($item['discount'], 2) ?></td>
                                                                    <td><?= $item['quantity'] ?></td>
                                                                    <td>₹<?= number_format($item['subtotal'], 2) ?></td>
                                                                </tr>
                                                            <?php endwhile; ?>
                                                            <?php $items_result->free();
                                                            $items_stmt->close(); ?>
                                                        </tbody>
                                                        <tfoot class="table-light">
                                                            <tr>
                                                                <th colspan="4" class="text-end">Subtotal:</th>
                                                                <th>₹<?= number_format($order['subtotal'], 2) ?></th>
                                                            </tr>
                                                            <tr>
                                                                <th colspan="4" class="text-end">Discount:</th>
                                                                <th class="text-danger">-₹<?= number_format($order['discount'], 2) ?></th>
                                                            </tr>
                                                            <tr>
                                                                <th colspan="4" class="text-end">Shipping Fee:</th>
                                                                <th>₹<?= number_format($order['shipping_fee'], 2) ?></th>
                                                            </tr>
                                                            <tr>
                                                                <th colspan="4" class="text-end">Grand Total:</th>
                                                                <th style="color:#0d9488; font-size:1.05rem;">₹<?= number_format($order['total_amount'], 2) ?></th>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="d-flex gap-2 justify-content-end">
                                                <button class="btn btn-sm btn-success"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editOrderModal<?= $order['id'] ?>"
                                                    onclick="event.stopPropagation();">
                                                    <i class="fa fa-edit me-1"></i> Update Status
                                                </button>
                                                <form method="POST" style="display:inline-block"
                                                    onsubmit="event.stopPropagation(); return confirm('Delete this order?');">
                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                    <button type="submit" name="delete_order" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-trash me-1"></i> Delete
                                                    </button>
                                                </form>
                                            </div>

                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Order Modal -->
                            <div class="modal fade" id="editOrderModal<?= $order['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <div class="modal-header" style="background:#0d9488; color:#fff;">
                                                <h5 class="modal-title">
                                                    <i class="fa fa-edit me-2"></i>Update Order Status
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Order Number</label>
                                                    <input type="text" class="form-control"
                                                        value="<?= htmlspecialchars($order['order_number']) ?>" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Order Status</label>
                                                    <select name="order_status" class="form-select" required>
                                                        <option value="Pending" <?= $order['order_status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="Confirmed" <?= $order['order_status'] == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                        <option value="Processing" <?= $order['order_status'] == 'Processing' ? 'selected' : '' ?>>Processing</option>
                                                        <option value="Shipped" <?= $order['order_status'] == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                                        <option value="Delivered" <?= $order['order_status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                                        <option value="Cancelled" <?= $order['order_status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                        <option value="Returned" <?= $order['order_status'] == 'Returned' ? 'selected' : '' ?>>Returned</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Payment Status</label>
                                                    <select name="payment_status" class="form-select" required>
                                                        <option value="Pending" <?= $order['payment_status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="Paid" <?= $order['payment_status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                                                        <option value="Failed" <?= $order['payment_status'] == 'Failed' ? 'selected' : '' ?>>Failed</option>
                                                        <option value="Refunded" <?= $order['payment_status'] == 'Refunded' ? 'selected' : '' ?>>Refunded</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" name="update_order"
                                                    class="btn text-white fw-semibold" style="background:#0d9488;">
                                                    <i class="fa fa-save me-1"></i> Update
                                                </button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-3" aria-label="Pagination">
                <ul class="pagination mb-0">
                    <?php
                    $qsBase = '';
                    if ($search !== '') $qsBase .= '&search=' . urlencode($search);
                    $prevDisabled = $page <= 1 ? ' disabled' : '';
                    $nextDisabled = $page >= $totalPages ? ' disabled' : '';
                    $prevPage = $page - 1;
                    $nextPage = $page + 1;
                    ?>
                    <li class="page-item<?= $prevDisabled ?>">
                        <a class="page-link btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;"
                            href="?page=<?= $prevPage . $qsBase ?>">« Prev</a>
                    </li>
                    <?php
                    $range = 3;
                    $startp = max(1, $page - $range);
                    $endp = min($totalPages, $page + $range);
                    for ($p = $startp; $p <= $endp; $p++):
                        $active = $p === $page ? ' active' : '';
                    ?>
                        <li class="page-item<?= $active ?>">
                            <a class="page-link" href="?page=<?= $p . $qsBase ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item<?= $nextDisabled ?>">
                        <a class="page-link btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;"
                            href="?page=<?= $nextPage . $qsBase ?>">Next »</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

</div>

<style>
    .order-row:hover {
        background-color: #f0fdfa;
    }

    .collapse-row td {
        transition: all 0.3s ease;
    }

    .order-row[aria-expanded="true"] .fa-chevron-down {
        transform: rotate(180deg);
        transition: transform 0.3s ease;
    }

    .fa-chevron-down {
        transition: transform 0.3s ease;
    }
</style>

<script>
    // Prevent row click when clicking buttons inside accordion
    document.querySelectorAll('.collapse').forEach(collapse => {
        collapse.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
</script>

<?php
$content_admin = ob_get_clean();
include 'admin_layout.php';
?>