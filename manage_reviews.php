<?php
session_start();
include_once '../db_config.php';
include_once 'admin_authentication.php';

// ==========================================
// FETCH ALL ORDERS
// ==========================================
$orders = [];

$stmt = $con->prepare("CALL Orders_Select(NULL, NULL, NULL)");
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $orders[] = $row;
        }
        $res->free();
    }

    $stmt->close();
    flush_stored_results($con);
}

// ==========================================
// FILTER ORDERS
// ==========================================
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$filtered_orders = $orders;

// Filter by status
if (!empty($filter_status)) {
    $filtered_orders = array_filter($filtered_orders, function ($order) use ($filter_status) {
        return $order['order_status'] === $filter_status;
    });
}

// Search by order number or email
if (!empty($search)) {
    $filtered_orders = array_filter($filtered_orders, function ($order) use ($search) {
        return stripos($order['order_number'], $search) !== false ||
            stripos($order['user_email'], $search) !== false ||
            stripos($order['delivery_name'], $search) !== false;
    });
}

ob_start();
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1" style="color:#0d9488;">
                <i class="fa-solid fa-box me-2"></i> Manage Orders
            </h2>
            <p class="text-muted mb-0">View and manage all customer orders</p>
        </div>
        <div>
            <span class="badge bg-primary fs-5 px-3 py-2">
                <?= count($orders) ?> Total Orders
            </span>
        </div>
    </div>

    <!-- Search & Filter Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="manage_orders.php" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fa-solid fa-search"></i>
                        </span>
                        <input type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search by Order #, Email, or Name"
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Confirmed" <?= $filter_status === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="Processing" <?= $filter_status === 'Processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="Shipped" <?= $filter_status === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="Delivered" <?= $filter_status === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="Cancelled" <?= $filter_status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn text-white flex-grow-1" style="background-color:#0d9488;font-size:small;">
                            <i class="fa-solid fa-filter me-1"></i> Filter
                        </button>
                        <a href="manage_orders.php" class="btn btn-outline-secondary" style="font-size:small;">
                            <i class="fa-solid fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <?php if (count($filtered_orders) > 0): ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Order Status</th>
                                <th>Payment Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filtered_orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong class="text-primary">
                                            <?= htmlspecialchars($order['order_number']) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($order['delivery_name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($order['user_email']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <small><?= date('d M Y', strtotime($order['order_date'])) ?><br>
                                            <?= date('h:i A', strtotime($order['order_date'])) ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-success">₹<?= number_format($order['total_amount'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $order['payment_method'] === 'cod' ? 'info' : 'primary' ?>">
                                            <?= $order['payment_method'] === 'cod' ? 'COD' : 'Online' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = match ($order['order_status']) {
                                            'Pending' => 'warning',
                                            'Confirmed' => 'info',
                                            'Processing' => 'primary',
                                            'Shipped' => 'primary',
                                            'Delivered' => 'success',
                                            'Cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $status_class ?>">
                                            <?= $order['order_status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $payment_class = match ($order['payment_status']) {
                                            'Paid' => 'success',
                                            'Pending' => 'warning',
                                            'Failed' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $payment_class ?>">
                                            <?= $order['payment_status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="order_details.php?id=<?= $order['id'] ?>"
                                                class="btn btn-outline-primary"
                                                title="View Details">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <button type="button"
                                                class="btn btn-outline-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#updateModal<?= $order['id'] ?>"
                                                title="Update Status">
                                                <i class="fa-solid fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Update Status Modal -->
                                <div class="modal fade" id="updateModal<?= $order['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header" style="background-color:#0d9488; color:white;">
                                                <h5 class="modal-title">
                                                    <i class="fa-solid fa-edit me-2"></i>
                                                    Update Order Status
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="update_order_status.php">
                                                <div class="modal-body">
                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

                                                    <p class="mb-3">
                                                        <strong>Order #<?= htmlspecialchars($order['order_number']) ?></strong><br>
                                                        <small class="text-muted"><?= htmlspecialchars($order['delivery_name']) ?></small>
                                                    </p>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Order Status</label>
                                                        <select name="order_status" class="form-select" required>
                                                            <option value="Pending" <?= $order['order_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                            <option value="Confirmed" <?= $order['order_status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                            <option value="Processing" <?= $order['order_status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                                                            <option value="Shipped" <?= $order['order_status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                                            <option value="Delivered" <?= $order['order_status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                                            <option value="Cancelled" <?= $order['order_status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Payment Status</label>
                                                        <select name="payment_status" class="form-select" required>
                                                            <option value="Pending" <?= $order['payment_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                            <option value="Paid" <?= $order['payment_status'] === 'Paid' ? 'selected' : '' ?>>Paid</option>
                                                            <option value="Failed" <?= $order['payment_status'] === 'Failed' ? 'selected' : '' ?>>Failed</option>
                                                            <option value="Refunded" <?= $order['payment_status'] === 'Refunded' ? 'selected' : '' ?>>Refunded</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        Cancel
                                                    </button>
                                                    <button type="submit" name="update_status" class="btn text-white" style="background-color:#0d9488;font-size:small;">
                                                        <i class="fa-solid fa-save me-2"></i> Update Status
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Empty State -->
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa-solid fa-box-open" style="font-size: 5rem; color:#e5e7eb;"></i>
                <h4 class="mt-4 text-muted">No Orders Found</h4>
                <p class="text-muted">
                    <?= !empty($search) || !empty($filter_status)
                        ? 'Try adjusting your filters'
                        : 'No orders have been placed yet' ?>
                </p>
                <?php if (!empty($search) || !empty($filter_status)): ?>
                    <a href="manage_orders.php" class="btn btn-outline-secondary mt-3">
                        <i class="fa-solid fa-redo me-2"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mt-4">
        <?php
        $stats = [
            'Pending' => 0,
            'Confirmed' => 0,
            'Processing' => 0,
            'Shipped' => 0,
            'Delivered' => 0,
            'Cancelled' => 0
        ];

        foreach ($orders as $order) {
            if (isset($stats[$order['order_status']])) {
                $stats[$order['order_status']]++;
            }
        }

        $total_revenue = array_sum(array_column($orders, 'total_amount'));
        ?>

        <div class="col-md-2 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Revenue</h6>
                    <h4 class="fw-bold text-success mb-0">₹<?= number_format($total_revenue, 2) ?></h4>
                </div>
            </div>
        </div>

        <?php
        $status_config = [
            'Pending' => ['icon' => 'clock', 'color' => 'warning'],
            'Confirmed' => ['icon' => 'check', 'color' => 'info'],
            'Shipped' => ['icon' => 'truck', 'color' => 'primary'],
            'Delivered' => ['icon' => 'check-circle', 'color' => 'success'],
            'Cancelled' => ['icon' => 'times-circle', 'color' => 'danger']
        ];

        foreach (['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'] as $status):
            $config = $status_config[$status];
        ?>
            <div class="col-md-2 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">
                            <i class="fa-solid fa-<?= $config['icon'] ?> me-1"></i>
                            <?= $status ?>
                        </h6>
                        <h4 class="fw-bold text-<?= $config['color'] ?> mb-0">
                            <?= $stats[$status] ?>
                        </h4>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .table tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }

    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
    }
</style>

<?php
$content = ob_get_clean();
include 'admin_layout.php';
?>