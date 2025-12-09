<?php
include_once 'db_config.php';
include_once 'user_authentication.php';

$user_email = $_SESSION['user_email'];

// ==========================================
// FETCH USER'S ORDERS
// ==========================================
$orders = [];

$stmt = $con->prepare("CALL Orders_Select(NULL, ?, NULL)");
if ($stmt) {
    $stmt->bind_param("s", $user_email);
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
// FILTER ORDERS (Optional)
// ==========================================
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtered_orders = $orders;

if (!empty($filter_status)) {
    $filtered_orders = array_filter($orders, function ($order) use ($filter_status) {
        return $order['order_status'] === $filter_status;
    });
}

ob_start();
?>

<div class="container py-5">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1" style="color:#0d9488;">
                <i class="fa-solid fa-box me-2"></i> My Orders
            </h2>
            <p class="text-muted mb-0">Track and manage your orders</p>
        </div>
        <div>
            <span class="badge bg-primary fs-6">
                <?= count($orders) ?> Total Orders
            </span>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="btn-group w-100 flex-wrap" role="group">
                <a href="order_history.php"
                    class="btn <?= empty($filter_status) ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="fa-solid fa-list me-2"></i> All Orders
                </a>
                <a href="order_history.php?status=Pending"
                    class="btn <?= $filter_status === 'Pending' ? 'btn-warning' : 'btn-outline-warning' ?>">
                    <i class="fa-solid fa-clock me-2"></i> Pending
                </a>
                <a href="order_history.php?status=Confirmed"
                    class="btn <?= $filter_status === 'Confirmed' ? 'btn-info' : 'btn-outline-info' ?>">
                    <i class="fa-solid fa-check me-2"></i> Confirmed
                </a>
                <a href="order_history.php?status=Shipped"
                    class="btn <?= $filter_status === 'Shipped' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="fa-solid fa-truck me-2"></i> Shipped
                </a>
                <a href="order_history.php?status=Delivered"
                    class="btn <?= $filter_status === 'Delivered' ? 'btn-success' : 'btn-outline-success' ?>">
                    <i class="fa-solid fa-check-circle me-2"></i> Delivered
                </a>
                <a href="order_history.php?status=Cancelled"
                    class="btn <?= $filter_status === 'Cancelled' ? 'btn-danger' : 'btn-outline-danger' ?>">
                    <i class="fa-solid fa-times me-2"></i> Cancelled
                </a>
            </div>
        </div>
    </div>

    <!-- Orders List -->
    <?php if (count($filtered_orders) > 0): ?>
        <?php foreach ($filtered_orders as $order): ?>
            <div class="card shadow-sm mb-4 order-card">
                <div class="card-header d-flex justify-content-between align-items-center"
                    style="background-color:#f8f9fa;">
                    <div>
                        <h6 class="mb-1 fw-bold">
                            <i class="fa-solid fa-receipt me-2" style="color:#0d9488;"></i>
                            Order #<?= htmlspecialchars($order['order_number']) ?>
                        </h6>
                        <small class="text-muted">
                            <i class="fa-solid fa-calendar me-1"></i>
                            Placed on <?= date('d M Y, h:i A', strtotime($order['order_date'])) ?>
                        </small>
                    </div>
                    <div class="text-end">
                        <h5 class="mb-1 fw-bold" style="color:#0d9488;">
                            ₹<?= number_format($order['total_amount'], 2) ?>
                        </h5>
                        <small class="text-muted">
                            <?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online Payment' ?>
                        </small>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- Order Status -->
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="small text-muted mb-1">Order Status</label>
                            <div>
                                <?php
                                $status_class = match ($order['order_status']) {
                                    'Pending' => 'warning',
                                    'Confirmed' => 'info',
                                    'Processing' => 'primary',
                                    'Shipped' => 'primary',
                                    'Delivered' => 'success',
                                    'Cancelled' => 'danger',
                                    'Returned' => 'secondary',
                                    default => 'secondary'
                                };

                                $status_icon = match ($order['order_status']) {
                                    'Pending' => 'clock',
                                    'Confirmed' => 'check',
                                    'Processing' => 'spinner',
                                    'Shipped' => 'truck',
                                    'Delivered' => 'check-circle',
                                    'Cancelled' => 'times-circle',
                                    'Returned' => 'undo',
                                    default => 'info-circle'
                                };
                                ?>
                                <span class="badge bg-<?= $status_class ?> fs-6">
                                    <i class="fa-solid fa-<?= $status_icon ?> me-1"></i>
                                    <?= $order['order_status'] ?>
                                </span>
                            </div>
                        </div>

                        <!-- Payment Status -->
                        <div class="col-md-2 mb-3 mb-md-0">
                            <label class="small text-muted mb-1">Payment Status</label>
                            <div>
                                <?php
                                $payment_class = match ($order['payment_status']) {
                                    'Paid' => 'success',
                                    'Pending' => 'warning',
                                    'Failed' => 'danger',
                                    'Refunded' => 'info',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $payment_class ?> fs-6">
                                    <i class="fa-solid fa-<?= $order['payment_status'] === 'Paid' ? 'check-circle' : 'clock' ?> me-1"></i>
                                    <?= $order['payment_status'] ?>
                                </span>
                            </div>
                        </div>

                        <!-- Delivery Address -->
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="small text-muted mb-1">Delivery Address</label>
                            <p class="mb-0 small">
                                <i class="fa-solid fa-user me-1"></i>
                                <strong><?= htmlspecialchars($order['delivery_name']) ?></strong><br>
                                <i class="fa-solid fa-location-dot me-1"></i>
                                <?= htmlspecialchars(substr($order['delivery_address'], 0, 50)) ?>...
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="col-md-3 text-end">
                            <a href="order_details.php?order=<?= urlencode($order['order_number']) ?>"
                                class="btn btn-sm text-white mb-2 w-100"
                                style="background-color:#0d9488;font-size:small;">
                                <i class="fa-solid fa-eye me-1"></i> View Details
                            </a>

                            <!-- ✅ REVIEW BUTTON FOR DELIVERED ORDERS (NEW) -->
                            <?php if ($order['order_status'] === 'Delivered'): ?>
                                <a href="order_details.php?order=<?= urlencode($order['order_number']) ?>#reviews"
                                    class="btn btn-sm btn-warning mb-2 w-100">
                                    <i class="fa-solid fa-star me-1"></i> Write Review
                                </a>
                            <?php endif; ?>

                            <?php if (in_array($order['order_status'], ['Pending', 'Confirmed'])): ?>
                                <button class="btn btn-sm btn-outline-danger w-100"
                                    data-bs-toggle="modal"
                                    data-bs-target="#cancelModal<?= $order['id'] ?>">
                                    <i class="fa-solid fa-times me-1"></i> Cancel Order
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Progress Bar for Order Status -->
                    <?php if ($order['order_status'] !== 'Cancelled'): ?>
                        <div class="mt-4">
                            <div class="order-progress">
                                <div class="progress-step <?= in_array($order['order_status'], ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered']) ? 'completed' : '' ?>">
                                    <div class="progress-icon">
                                        <i class="fa-solid fa-check"></i>
                                    </div>
                                    <div class="progress-label">Confirmed</div>
                                </div>

                                <div class="progress-step <?= in_array($order['order_status'], ['Processing', 'Shipped', 'Delivered']) ? 'completed' : '' ?>">
                                    <div class="progress-icon">
                                        <i class="fa-solid fa-box"></i>
                                    </div>
                                    <div class="progress-label">Processing</div>
                                </div>

                                <div class="progress-step <?= in_array($order['order_status'], ['Shipped', 'Delivered']) ? 'completed' : '' ?>">
                                    <div class="progress-icon">
                                        <i class="fa-solid fa-truck"></i>
                                    </div>
                                    <div class="progress-label">Shipped</div>
                                </div>

                                <div class="progress-step <?= $order['order_status'] === 'Delivered' ? 'completed' : '' ?>">
                                    <div class="progress-icon">
                                        <i class="fa-solid fa-home"></i>
                                    </div>
                                    <div class="progress-label">Delivered</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cancel Order Modal -->
            <div class="modal fade" id="cancelModal<?= $order['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 shadow">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title fw-bold">
                                <i class="fa-solid fa-exclamation-triangle me-2"></i> Cancel Order
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <p class="mb-3">Are you sure you want to cancel this order?</p>
                            <p class="text-muted small mb-3">
                                <strong>Order #<?= htmlspecialchars($order['order_number']) ?></strong><br>
                                Amount: ₹<?= number_format($order['total_amount'], 2) ?>
                            </p>

                            <form method="POST" action="cancel_order.php">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Reason for Cancellation</label>
                                    <textarea name="cancellation_reason"
                                        class="form-control"
                                        rows="3"
                                        placeholder="Please provide a reason for cancellation"
                                        required></textarea>
                                </div>

                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        No, Keep Order
                                    </button>
                                    <button type="submit" name="cancel_order" class="btn btn-danger">
                                        <i class="fa-solid fa-times me-2"></i> Yes, Cancel Order
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>

    <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-5">
            <i class="fa-solid fa-box-open" style="font-size: 5rem; color:#e5e7eb;"></i>
            <h4 class="mt-4 text-muted">
                <?= empty($filter_status) ? 'No Orders Yet' : 'No ' . $filter_status . ' Orders' ?>
            </h4>
            <p class="text-muted">
                <?= empty($filter_status) ? 'Start shopping to place your first order!' : 'Try a different filter to see other orders.' ?>
            </p>
            <?php if (empty($filter_status)): ?>
                <a href="products.php" class="btn text-white mt-3  " style="background-color:#0d9488;font-size:small;">
                    <i class="fa-solid fa-shop me-2"></i> Start Shopping
                </a>
            <?php else: ?>
                <a href="order_history.php" class="btn btn-outline-secondary mt-3">
                    <i class="fa-solid fa-list me-2"></i> View All Orders
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .order-card {
        transition: all 0.3s ease;
        border: 2px solid #f0f0f0;
    }

    .order-card:hover {
        border-color: #0d9488;
        box-shadow: 0 10px 25px rgba(13, 148, 136, 0.15) !important;
    }

    /* Order Progress Bar */
    .order-progress {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        position: relative;
        padding: 20px 0;
    }

    .order-progress::before {
        content: '';
        position: absolute;
        top: 35px;
        left: 0;
        right: 0;
        height: 2px;
        background: #e5e7eb;
        z-index: 0;
    }

    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
        flex: 1;
    }

    .progress-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #e5e7eb;
        color: #9ca3af;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin-bottom: 10px;
        border: 3px solid white;
        transition: all 0.3s ease;
    }

    .progress-step.completed .progress-icon {
        background: #0d9488;
        color: white;
    }

    .progress-label {
        font-size: 0.85rem;
        color: #6b7280;
        font-weight: 500;
        text-align: center;
    }

    .progress-step.completed .progress-label {
        color: #0d9488;
        font-weight: bold;
    }

    @media (max-width: 768px) {
        .btn-group {
            flex-direction: column;
        }

        .btn-group .btn {
            border-radius: 0.375rem !important;
            margin-bottom: 5px;
        }

        .order-progress {
            padding: 10px 0;
        }

        .progress-icon {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }

        .progress-label {
            font-size: 0.7rem;
        }
    }
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>