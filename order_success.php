<?php
include_once 'db_config.php';
include_once 'user_authentication.php';

$user_email = $_SESSION['user_email'];

// Get order number from URL
if (!isset($_GET['order']) || empty($_GET['order'])) {
?>
    <script>
        window.location.href = 'index.php';
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
        window.location.href = 'index.php';
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
    <!-- Success Animation Section -->
    <div class="text-center mb-5">
        <div class="success-checkmark">
            <div class="check-icon">
                <span class="icon-line line-tip"></span>
                <span class="icon-line line-long"></span>
                <div class="icon-circle"></div>
                <div class="icon-fix"></div>
            </div>
        </div>

        <h1 class="fw-bold mt-4" style="color:#0d9488;">Order Placed Successfully!</h1>
        <p class="text-muted fs-5">Thank you for your order. We'll send you a confirmation email shortly.</p>
    </div>

    <!-- Order Summary Card -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header text-white" style="background-color:#0d9488;font-size:small;">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-receipt me-2"></i> Order Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Order Number:</strong><br>
                                <span class="badge bg-primary fs-6"><?= htmlspecialchars($order['order_number']) ?></span>
                            </p>
                            <p class="mb-2">
                                <strong>Order Date:</strong><br>
                                <i class="fa-solid fa-calendar me-2 text-muted"></i>
                                <?= date('d M Y, h:i A', strtotime($order['order_date'])) ?>
                            </p>
                            <p class="mb-0">
                                <strong>Payment Method:</strong><br>
                                <i class="fa-solid fa-money-bill me-2 text-muted"></i>
                                <?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online Payment' ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Order Status:</strong><br>
                                <span class="badge bg-warning text-dark">
                                    <i class="fa-solid fa-clock me-1"></i> <?= $order['order_status'] ?>
                                </span>
                            </p>
                            <p class="mb-2">
                                <strong>Payment Status:</strong><br>
                                <span class="badge bg-<?= $order['payment_status'] === 'Paid' ? 'success' : 'warning' ?>">
                                    <i class="fa-solid fa-<?= $order['payment_status'] === 'Paid' ? 'check-circle' : 'clock' ?> me-1"></i>
                                    <?= $order['payment_status'] ?>
                                </span>
                            </p>
                            <p class="mb-0">
                                <strong>Total Amount:</strong><br>
                                <span class="fs-4 fw-bold" style="color:#0d9488;">
                                    ₹<?= number_format($order['total_amount'], 2) ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <hr>

                    <!-- Delivery Address -->
                    <h6 class="fw-bold mb-3">
                        <i class="fa-solid fa-map-marker-alt me-2" style="color:#0d9488;"></i>
                        Delivery Address
                    </h6>
                    <div class="bg-light p-3 rounded">
                        <p class="mb-1"><strong><?= htmlspecialchars($order['delivery_name']) ?></strong></p>
                        <p class="mb-1">
                            <i class="fa-solid fa-envelope me-2 text-muted"></i>
                            <?= htmlspecialchars($order['delivery_email']) ?>
                        </p>
                        <p class="mb-1">
                            <i class="fa-solid fa-phone me-2 text-muted"></i>
                            <?= htmlspecialchars($order['delivery_mobile']) ?>
                        </p>
                        <p class="mb-0">
                            <i class="fa-solid fa-location-dot me-2 text-muted"></i>
                            <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card shadow-sm mb-4">
                <div class="card-header text-white" style="background-color:#0d9488;font-size:small;">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-box me-2"></i>
                        Ordered Items (<?= count($order_items) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($order_items as $index => $item): ?>
                        <div class="d-flex align-items-center mb-3 pb-3 <?= $index < count($order_items) - 1 ? 'border-bottom' : '' ?>">
                            <div class="me-3">
                                <img src="images/products/<?= htmlspecialchars($item['product_image']) ?>"
                                    alt="<?= htmlspecialchars($item['product_name']) ?>"
                                    class="rounded"
                                    style="width: 80px; height: 80px; object-fit: contain; background:#f8f9fa; padding:5px;"
                                    onerror="this.src='images/placeholder.jpg'">
                            </div>

                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($item['product_name']) ?></h6>
                                <p class="text-muted small mb-1">
                                    Price: ₹<?= number_format($item['price'], 2) ?>
                                    <?php if ($item['discount'] > 0): ?>
                                        <span class="text-success">(<?= $item['discount'] ?>% off)</span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-muted small mb-0">Quantity: <?= $item['quantity'] ?></p>
                            </div>

                            <div class="text-end">
                                <h6 class="mb-0 fw-bold" style="color:#0d9488;">
                                    ₹<?= number_format($item['subtotal'], 2) ?>
                                </h6>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Price Breakdown -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>₹<?= number_format($order['subtotal'], 2) ?></span>
                        </div>

                        <?php if ($order['discount'] > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Discount:</span>
                                <span>-₹<?= number_format($order['discount'], 2) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping Fee:</span>
                            <span>
                                <?php if ($order['shipping_fee'] > 0): ?>
                                    ₹<?= number_format($order['shipping_fee'], 2) ?>
                                <?php else: ?>
                                    <span class="text-success">FREE</span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <h5 class="fw-bold">Total:</h5>
                            <h5 class="fw-bold" style="color:#0d9488;">
                                ₹<?= number_format($order['total_amount'], 2) ?>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-center">
                <a href="my_orders.php" class="btn   text-white me-3" style="background-color:#0d9488;font-size:small;">
                    <i class="fa-solid fa-box me-2"></i> View My Orders
                </a>
                <a href="index.php" class="btn   btn-outline-secondary">
                    <i class="fa-solid fa-home me-2"></i> Continue Shopping
                </a>
            </div>

            <!-- ✅ Help Section WITH REVIEW LINK (NEW) -->
            <div class="alert alert-info mt-4">
                <h6 class="alert-heading">
                    <i class="fa-solid fa-info-circle me-2"></i> What's Next?
                </h6>
                <ul class="mb-0">
                    <li>You will receive an order confirmation email at <strong><?= htmlspecialchars($order['delivery_email']) ?></strong></li>
                    <li>Track your order status in <a href="my_orders.php" class="alert-link">My Orders</a></li>
                    <li>Expected delivery: 3-5 business days</li>
                    <?php if ($order['payment_method'] === 'cod'): ?>
                        <li>Please keep exact change ready for Cash on Delivery</li>
                    <?php endif; ?>
                    <li class="fw-bold">
                        <i class="fa-solid fa-star text-warning me-1"></i>
                        After delivery, share your experience by
                        <a href="my_orders.php" class="alert-link">writing a review</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    /* Success Checkmark Animation */
    .success-checkmark {
        width: 80px;
        height: 115px;
        margin: 0 auto;
    }

    .check-icon {
        width: 80px;
        height: 80px;
        position: relative;
        border-radius: 50%;
        box-sizing: content-box;
        border: 4px solid #0d9488;
    }

    .check-icon::before {
        top: 3px;
        left: -2px;
        width: 30px;
        transform-origin: 100% 50%;
        border-radius: 100px 0 0 100px;
    }

    .check-icon::after {
        top: 0;
        left: 30px;
        width: 60px;
        transform-origin: 0 50%;
        border-radius: 0 100px 100px 0;
        animation: rotate-circle 4.25s ease-in;
    }

    .check-icon::before,
    .check-icon::after {
        content: '';
        height: 100px;
        position: absolute;
        background: #FFFFFF;
        transform: rotate(-45deg);
    }

    .icon-line {
        height: 5px;
        background-color: #0d9488;
        display: block;
        border-radius: 2px;
        position: absolute;
        z-index: 10;
    }

    .icon-line.line-tip {
        top: 46px;
        left: 14px;
        width: 25px;
        transform: rotate(45deg);
        animation: icon-line-tip 0.75s;
    }

    .icon-line.line-long {
        top: 38px;
        right: 8px;
        width: 47px;
        transform: rotate(-45deg);
        animation: icon-line-long 0.75s;
    }

    .icon-circle {
        top: -4px;
        left: -4px;
        z-index: 10;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        position: absolute;
        box-sizing: content-box;
        border: 4px solid rgba(13, 148, 136, .5);
    }

    .icon-fix {
        top: 8px;
        width: 5px;
        left: 26px;
        z-index: 1;
        height: 85px;
        position: absolute;
        transform: rotate(-45deg);
        background-color: #FFFFFF;
    }

    @keyframes rotate-circle {
        0% {
            transform: rotate(-45deg);
        }

        5% {
            transform: rotate(-45deg);
        }

        12% {
            transform: rotate(-405deg);
        }

        100% {
            transform: rotate(-405deg);
        }
    }

    @keyframes icon-line-tip {
        0% {
            width: 0;
            left: 1px;
            top: 19px;
        }

        54% {
            width: 0;
            left: 1px;
            top: 19px;
        }

        70% {
            width: 50px;
            left: -8px;
            top: 37px;
        }

        84% {
            width: 17px;
            left: 21px;
            top: 48px;
        }

        100% {
            width: 25px;
            left: 14px;
            top: 45px;
        }
    }

    @keyframes icon-line-long {
        0% {
            width: 0;
            right: 46px;
            top: 54px;
        }

        65% {
            width: 0;
            right: 46px;
            top: 54px;
        }

        84% {
            width: 55px;
            right: 0px;
            top: 35px;
        }

        100% {
            width: 47px;
            right: 8px;
            top: 38px;
        }
    }
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>