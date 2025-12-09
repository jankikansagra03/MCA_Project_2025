<?php
include_once 'db_config.php';
include_once 'user_authentication.php';

$user_email = $_SESSION['user_email'];

// Get order number from URL
if (!isset($_GET['order']) || empty($_GET['order'])) {
?>
    <script>
        window.location.href = 'my_orders.php';
    </script>
<?php
    exit();
}

$order_number = $_GET['order'];

// ==========================================
// FETCH ORDER DETAILS
// ==========================================
$order = null;

$order_stmt = $con->prepare("CALL Orders_GetByOrderNumber(?)");
if ($order_stmt) {
    $order_stmt->bind_param("s", $order_number);
    $order_stmt->execute();
    $order_res = $order_stmt->get_result();

    if ($order_res && $order_res->num_rows > 0) {
        $order = $order_res->fetch_assoc();
        $order_res->free();
    }

    $order_stmt->close();
    flush_stored_results($con);
}

// If order not found or doesn't belong to user
if (!$order || $order['user_email'] !== $user_email) {
    setcookie('error', 'Order not found', time() + 5, '/');
?>
    <script>
        window.location.href = 'my_orders.php';
    </script>
<?php
    exit();
}

// ==========================================
// FETCH ORDER ITEMS
// ==========================================
$order_items = [];

$items_stmt = $con->prepare("CALL OrderItems_Select(?)");
if ($items_stmt) {
    $items_stmt->bind_param("i", $order['id']);
    $items_stmt->execute();
    $items_res = $items_stmt->get_result();

    if ($items_res) {
        while ($row = $items_res->fetch_assoc()) {
            $order_items[] = $row;
        }
        $items_res->free();
    }

    $items_stmt->close();
    flush_stored_results($con);
}

ob_start();
?>

<div class="container py-5">
    <!-- Back Button -->
    <div class="mb-4">
        <a href="my_orders.php" class="btn btn-outline-secondary" style="font-size:small;">
            <i class="fa-solid fa-arrow-left me-2"></i> Back to Orders
        </a>
    </div>

    <!-- Order Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-header text-white" style="background-color:#0d9488;font-size:small;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">
                        <i class="fa-solid fa-receipt me-2"></i>
                        Order Details
                    </h4>
                    <p class="mb-0 small">Order #<?= htmlspecialchars($order['order_number']) ?></p>
                </div>
                <div class="text-end">
                    <?php
                    $status_badge = match ($order['order_status']) {
                        'Pending' => 'warning',
                        'Confirmed' => 'info',
                        'Processing' => 'primary',
                        'Shipped' => 'primary',
                        'Delivered' => 'success',
                        'Cancelled' => 'danger',
                        'Returned' => 'secondary',
                        default => 'secondary'
                    };
                    ?>
                    <span class="badge bg-<?= $status_badge ?> fs-6">
                        <?= $order['order_status'] ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Order Items & Progress -->
        <div class="col-lg-8">

            <!-- Order Progress Tracker -->
            <?php if ($order['order_status'] !== 'Cancelled'): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fa-solid fa-truck me-2" style="color:#0d9488;"></i>
                            Order Tracking
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="order-tracker">
                            <div class="tracker-step <?= in_array($order['order_status'], ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered']) ? 'completed' : 'active' ?>">
                                <div class="tracker-icon">
                                    <i class="fa-solid fa-receipt"></i>
                                </div>
                                <div class="tracker-content">
                                    <h6 class="mb-1">Order Placed</h6>
                                    <small class="text-muted">
                                        <?= date('d M Y, h:i A', strtotime($order['order_date'])) ?>
                                    </small>
                                </div>
                            </div>

                            <div class="tracker-step <?= in_array($order['order_status'], ['Confirmed', 'Processing', 'Shipped', 'Delivered']) ? 'completed' : ($order['order_status'] === 'Pending' ? 'active' : '') ?>">
                                <div class="tracker-icon">
                                    <i class="fa-solid fa-check-circle"></i>
                                </div>
                                <div class="tracker-content">
                                    <h6 class="mb-1">Confirmed</h6>
                                    <small class="text-muted">
                                        <?= $order['order_status'] !== 'Pending' && $order['order_date'] ? 'Order confirmed' : 'Pending confirmation' ?>
                                    </small>
                                </div>
                            </div>

                            <div class="tracker-step <?= in_array($order['order_status'], ['Processing', 'Shipped', 'Delivered']) ? 'completed' : ($order['order_status'] === 'Confirmed' ? 'active' : '') ?>">
                                <div class="tracker-icon">
                                    <i class="fa-solid fa-box"></i>
                                </div>
                                <div class="tracker-content">
                                    <h6 class="mb-1">Processing</h6>
                                    <small class="text-muted">
                                        <?= in_array($order['order_status'], ['Processing', 'Shipped', 'Delivered']) ? 'Order is being prepared' : 'Not started yet' ?>
                                    </small>
                                </div>
                            </div>

                            <div class="tracker-step <?= in_array($order['order_status'], ['Shipped', 'Delivered']) ? 'completed' : ($order['order_status'] === 'Processing' ? 'active' : '') ?>">
                                <div class="tracker-icon">
                                    <i class="fa-solid fa-truck"></i>
                                </div>
                                <div class="tracker-content">
                                    <h6 class="mb-1">Shipped</h6>
                                    <small class="text-muted">
                                        <?= $order['shipped_date'] ? date('d M Y, h:i A', strtotime($order['shipped_date'])) : 'Not shipped yet' ?>
                                    </small>
                                </div>
                            </div>

                            <div class="tracker-step <?= $order['order_status'] === 'Delivered' ? 'completed' : ($order['order_status'] === 'Shipped' ? 'active' : '') ?>">
                                <div class="tracker-icon">
                                    <i class="fa-solid fa-home"></i>
                                </div>
                                <div class="tracker-content">
                                    <h6 class="mb-1">Delivered</h6>
                                    <small class="text-muted">
                                        <?= $order['delivered_date'] ? date('d M Y, h:i A', strtotime($order['delivered_date'])) : 'Not delivered yet' ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Cancelled Order Notice -->
                <div class="alert alert-danger mb-4">
                    <h5 class="alert-heading">
                        <i class="fa-solid fa-times-circle me-2"></i> Order Cancelled
                    </h5>
                    <p class="mb-1">
                        <strong>Cancelled on:</strong>
                        <?= $order['cancelled_date'] ? date('d M Y, h:i A', strtotime($order['cancelled_date'])) : 'N/A' ?>
                    </p>
                    <?php if (!empty($order['cancellation_reason'])): ?>
                        <p class="mb-0">
                            <strong>Reason:</strong> <?= htmlspecialchars($order['cancellation_reason']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Order Items -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-box me-2" style="color:#0d9488;"></i>
                        Order Items (<?= count($order_items) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($order_items as $index => $item): ?>
                        <div class="d-flex align-items-center mb-3 pb-3 <?= $index < count($order_items) - 1 ? 'border-bottom' : '' ?>">
                            <div class="me-3">
                                <img src="images/products/<?= htmlspecialchars($item['product_image']) ?>"
                                    alt="<?= htmlspecialchars($item['product_name']) ?>"
                                    class="rounded"
                                    style="width: 100px; height: 100px; object-fit: contain; background:#f8f9fa; padding:10px;"
                                    onerror="this.src='images/placeholder.jpg'">
                            </div>

                            <div class="flex-grow-1">
                                <h6 class="mb-2 fw-bold"><?= htmlspecialchars($item['product_name']) ?></h6>

                                <div class="row small">
                                    <div class="col-md-4">
                                        <span class="text-muted">Price:</span>
                                        <strong>₹<?= number_format($item['price'], 2) ?></strong>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="text-muted">Quantity:</span>
                                        <strong><?= $item['quantity'] ?></strong>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="text-muted">Subtotal:</span>
                                        <strong class="text-success">₹<?= number_format($item['subtotal'], 2) ?></strong>
                                    </div>
                                </div>

                                <?php if ($item['discount'] > 0): ?>
                                    <div class="mt-2">
                                        <span class="badge bg-success">
                                            <?= $item['discount'] ?>% Discount Applied
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ✅ REVIEW PRODUCTS SECTION (NEW) -->
            <?php if ($order['order_status'] === 'Delivered'): ?>
                <div class="card shadow-sm mb-4 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fa-solid fa-star me-2"></i>
                            Rate Your Purchase
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            <i class="fa-solid fa-heart me-2"></i>
                            Share your experience with the products you purchased
                        </p>

                        <?php foreach ($order_items as $item): ?>
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                                <div class="d-flex align-items-center">
                                    <img src="images/products/<?= htmlspecialchars($item['product_image']) ?>"
                                        alt="<?= htmlspecialchars($item['product_name']) ?>"
                                        class="rounded me-3"
                                        style="width: 60px; height: 60px; object-fit: contain; background:#f8f9fa; padding:5px;">
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($item['product_name']) ?></h6>
                                        <small class="text-muted">Help others make informed decisions</small>
                                    </div>
                                </div>

                                <a href="submit_review.php?product_id=<?= $item['product_id'] ?>"
                                    class="btn btn-warning">
                                    <i class="fa-solid fa-star me-1"></i> Write Review
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Right Column: Summary & Address -->
        <div class="col-lg-4">

            <!-- Order Summary -->
            <div class="card shadow-sm mb-4">
                <div class="card-header text-white" style="background-color:#0d9488;font-size:small;">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-calculator me-2"></i> Order Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span class="fw-bold">₹<?= number_format($order['subtotal'], 2) ?></span>
                    </div>

                    <?php if ($order['discount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Discount:</span>
                            <span class="fw-bold">-₹<?= number_format($order['discount'], 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping Fee:</span>
                        <span class="fw-bold">
                            <?php if ($order['shipping_fee'] > 0): ?>
                                ₹<?= number_format($order['shipping_fee'], 2) ?>
                            <?php else: ?>
                                <span class="text-success">FREE</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="fw-bold">Total:</h5>
                        <h5 class="fw-bold" style="color:#0d9488;">
                            ₹<?= number_format($order['total_amount'], 2) ?>
                        </h5>
                    </div>

                    <div class="alert alert-info small mb-0">
                        <strong>Payment Method:</strong><br>
                        <i class="fa-solid fa-<?= $order['payment_method'] === 'cod' ? 'money-bill' : 'credit-card' ?> me-2"></i>
                        <?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online Payment' ?>
                    </div>
                </div>
            </div>

            <!-- Payment Status -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-credit-card me-2" style="color:#0d9488;"></i>
                        Payment Status
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $payment_class = match ($order['payment_status']) {
                        'Paid' => 'success',
                        'Pending' => 'warning',
                        'Failed' => 'danger',
                        'Refunded' => 'info',
                        default => 'secondary'
                    };
                    ?>
                    <div class="text-center">
                        <span class="badge bg-<?= $payment_class ?> fs-5 mb-3 px-4 py-2">
                            <i class="fa-solid fa-<?= $order['payment_status'] === 'Paid' ? 'check-circle' : 'clock' ?> me-2"></i>
                            <?= $order['payment_status'] ?>
                        </span>

                        <?php if ($order['payment_date']): ?>
                            <p class="text-muted small mb-0">
                                <i class="fa-solid fa-calendar me-1"></i>
                                Paid on: <?= date('d M Y, h:i A', strtotime($order['payment_date'])) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Delivery Address -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-map-marker-alt me-2" style="color:#0d9488;"></i>
                        Delivery Address
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="mb-2 fw-bold">
                        <i class="fa-solid fa-user me-2"></i>
                        <?= htmlspecialchars($order['delivery_name']) ?>
                    </h6>
                    <p class="mb-2 small">
                        <i class="fa-solid fa-envelope me-2 text-muted"></i>
                        <?= htmlspecialchars($order['delivery_email']) ?>
                    </p>
                    <p class="mb-2 small">
                        <i class="fa-solid fa-phone me-2 text-muted"></i>
                        <?= htmlspecialchars($order['delivery_mobile']) ?>
                    </p>
                    <p class="mb-0 small">
                        <i class="fa-solid fa-location-dot me-2 text-muted"></i>
                        <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <?php if (in_array($order['order_status'], ['Pending', 'Confirmed'])): ?>
                <button class="btn btn-danger w-100 mb-3"
                    data-bs-toggle="modal"
                    data-bs-target="#cancelModal">
                    <i class="fa-solid fa-times me-2"></i> Cancel Order
                </button>
            <?php endif; ?>

            <a href="my_orders.php" class="btn btn-outline-secondary w-100">
                <i class="fa-solid fa-list me-2"></i> View All Orders
            </a>
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i> Cancel Order
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <i class="fa-solid fa-triangle-exclamation fa-3x text-warning"></i>
                </div>
                <p class="mb-3 text-center">Are you sure you want to cancel this order?</p>
                <p class="text-muted small mb-3 text-center">
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

                    <div class="d-flex gap-2 justify-content-center">
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

<style>
    /* Order Tracker */
    .order-tracker {
        position: relative;
        padding: 20px 0;
    }

    .tracker-step {
        position: relative;
        padding-left: 70px;
        margin-bottom: 30px;
    }

    .tracker-step:last-child {
        margin-bottom: 0;
    }

    .tracker-step::before {
        content: '';
        position: absolute;
        left: 24px;
        top: 50px;
        width: 2px;
        height: calc(100% + 10px);
        background: #e5e7eb;
    }

    .tracker-step:last-child::before {
        display: none;
    }

    .tracker-step.completed::before {
        background: #0d9488;
    }

    .tracker-icon {
        position: absolute;
        left: 0;
        top: 0;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #e5e7eb;
        color: #9ca3af;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        border: 4px solid white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .tracker-step.completed .tracker-icon {
        background: #0d9488;
        color: white;
    }

    .tracker-step.active .tracker-icon {
        background: #fbbf24;
        color: white;
        animation: pulse 2s infinite;
    }

    .tracker-content h6 {
        margin-bottom: 5px;
        color: #374151;
    }

    .tracker-step.completed .tracker-content h6 {
        color: #0d9488;
        font-weight: bold;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        50% {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);
        }
    }
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>