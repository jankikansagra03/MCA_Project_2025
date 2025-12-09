<?php
include_once 'db_config.php';
include_once 'user_authentication.php';

$user_email = $_SESSION['user_email'];

// FETCH CART ITEMS
$cart_items = [];
$total_amount = 0;
$total_discount = 0;
$subtotal = 0;

$stmt = $con->prepare("CALL Cart_Select(?, NULL)");
if ($stmt) {
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $price = $row['price'];
            $discount = $row['discount'];
            $quantity = $row['quantity'];

            $original_price = $price * $quantity;
            $discount_amount = ($price * ($discount / 100)) * $quantity;
            $final_price = $original_price - $discount_amount;

            $row['original_total'] = $original_price;
            $row['discount_amount'] = $discount_amount;
            $row['final_total'] = $final_price;

            $cart_items[] = $row;

            $subtotal += $original_price;
            $total_discount += $discount_amount;
            $total_amount += $final_price;
        }
        $res->free();
    }

    $stmt->close();
    flush_stored_results($con);
}

// If cart is empty, redirect to cart
if (count($cart_items) == 0) {
    setcookie('error', 'Your cart is empty', time() + 5, '/');
?>
    <script>
        window.location.href = 'cart.php';
    </script>
<?php
    exit();
}

// Shipping calculation
$shipping_fee = 0;
$free_shipping_threshold = 500;
if ($total_amount > 0 && $total_amount < $free_shipping_threshold) {
    $shipping_fee = 50;
}

$grand_total = $total_amount + $shipping_fee;

// FETCH USER ADDRESSES
$addresses = [];
$addr_stmt = $con->prepare("CALL Addresses_Select(?, NULL)");
if ($addr_stmt) {
    $addr_stmt->bind_param("s", $user_email);
    $addr_stmt->execute();
    $addr_res = $addr_stmt->get_result();

    if ($addr_res) {
        while ($addr = $addr_res->fetch_assoc()) {
            $addresses[] = $addr;
        }
        $addr_res->free();
    }

    $addr_stmt->close();
    flush_stored_results($con);
}

ob_start();
?>

<div class="container py-5">
    <h2 class="fw-bold mb-4 text-center" style="color:#0d9488;">
        <i class="fa-solid fa-lock me-2"></i> Secure Checkout
    </h2>

    <!-- Progress Steps -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="checkout-steps d-flex justify-content-between">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div class="step-label">Address</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-label">Review</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-label">Payment</div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="place_order.php" id="checkoutForm">
        <div class="row g-4">
            <!-- Left Section: Address Selection -->
            <div class="col-lg-8">
                <!-- Delivery Address -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header" style="background-color:#0d9488; color:white;">
                        <h5 class="mb-0">
                            <i class="fa-solid fa-map-marker-alt me-2"></i>
                            Select Delivery Address
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($addresses) == 0): ?>
                            <div class="alert alert-warning">
                                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                                No saved addresses found. Please add a new address.
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($addresses as $index => $address): ?>
                                    <div class="col-md-6">
                                        <div class="address-card">
                                            <input type="radio"
                                                name="address_id"
                                                id="addr_<?= $address['id'] ?>"
                                                value="<?= $address['id'] ?>"
                                                <?= $index === 0 ? 'checked' : '' ?>
                                                required>
                                            <label for="addr_<?= $address['id'] ?>" class="w-100">
                                                <div class="address-content">
                                                    <h6 class="fw-bold mb-2">
                                                        <i class="fa-solid fa-user me-2"></i>
                                                        <?= htmlspecialchars($address['name']) ?>
                                                    </h6>
                                                    <p class="mb-1 small">
                                                        <i class="fa-solid fa-phone me-2"></i>
                                                        <?= htmlspecialchars($address['mobile']) ?>
                                                    </p>
                                                    <p class="mb-1 small">
                                                        <i class="fa-solid fa-envelope me-2"></i>
                                                        <?= htmlspecialchars($address['email']) ?>
                                                    </p>
                                                    <p class="mb-0 small text-muted">
                                                        <i class="fa-solid fa-location-dot me-2"></i>
                                                        <?= nl2br(htmlspecialchars($address['address'])) ?>
                                                    </p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Add New Address Button -->
                        <div class="mt-3">
                            <a href="saved_addresses.php" class="btn btn-outline-secondary" style="font-size:small;">
                                <i class="fa-solid fa-plus me-2"></i> Add New Address
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header" style="background-color:#0d9488; color:white;">
                        <h5 class="mb-0">
                            <i class="fa-solid fa-box me-2"></i>
                            Order Items (<?= count($cart_items) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <img src="images/products/<?= htmlspecialchars($item['image']) ?>"
                                    alt="<?= htmlspecialchars($item['name']) ?>"
                                    class="rounded"
                                    style="width: 80px; height: 80px; object-fit: contain;"
                                    onerror="this.src='images/placeholder.jpg'">

                                <div class="ms-3 flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                                    <p class="text-muted small mb-1">Quantity: <?= $item['quantity'] ?></p>
                                    <p class="mb-0">
                                        <span class="fw-bold" style="color:#0d9488;">
                                            ₹<?= number_format($item['final_total'], 2) ?>
                                        </span>
                                        <?php if ($item['discount'] > 0): ?>
                                            <span class="text-muted text-decoration-line-through small ms-2">
                                                ₹<?= number_format($item['original_total'], 2) ?>
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card shadow-sm">
                    <div class="card-header" style="background-color:#0d9488; color:white;">
                        <h5 class="mb-0">
                            <i class="fa-solid fa-credit-card me-2"></i>
                            Select Payment Method
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="payment-options">
                            <div class="payment-option mb-3">
                                <input type="radio"
                                    name="payment_method"
                                    id="razorpay"
                                    value="razorpay"
                                    checked
                                    required>
                                <label for="razorpay" class="w-100">
                                    <div class="payment-content">
                                        <i class="fa-solid fa-wallet me-2" style="color:#0d9488;"></i>
                                        <span class="fw-bold">Pay Online (Razorpay)</span>
                                        <p class="text-muted small mb-0 mt-1">
                                            Credit Card, Debit Card, Net Banking, UPI
                                        </p>
                                    </div>
                                </label>
                            </div>

                            <div class="payment-option">
                                <input type="radio"
                                    name="payment_method"
                                    id="cod"
                                    value="cod">
                                <label for="cod" class="w-100">
                                    <div class="payment-content">
                                        <i class="fa-solid fa-money-bill me-2" style="color:#0d9488;"></i>
                                        <span class="fw-bold">Cash on Delivery (COD)</span>
                                        <p class="text-muted small mb-0 mt-1">
                                            Pay when you receive the product
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Section: Order Summary -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header text-white" style="background-color:#0d9488;font-size:small;">
                        <h5 class="mb-0">
                            <i class="fa-solid fa-receipt me-2"></i> Order Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal (<?= count($cart_items) ?> items):</span>
                            <span class="fw-bold">₹<?= number_format($subtotal, 2) ?></span>
                        </div>

                        <?php if ($total_discount > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Discount:</span>
                                <span class="fw-bold">-₹<?= number_format($total_discount, 2) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span class="fw-bold">
                                <?php if ($shipping_fee > 0): ?>
                                    ₹<?= number_format($shipping_fee, 2) ?>
                                <?php else: ?>
                                    <span class="text-success">FREE</span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-3">
                            <h5 class="fw-bold">Total:</h5>
                            <h5 class="fw-bold" style="color:#0d9488;">
                                ₹<?= number_format($grand_total, 2) ?>
                            </h5>
                        </div>

                        <input type="hidden" name="total_amount" value="<?= $grand_total ?>">
                        <input type="hidden" name="subtotal" value="<?= $subtotal ?>">
                        <input type="hidden" name="discount" value="<?= $total_discount ?>">
                        <input type="hidden" name="shipping" value="<?= $shipping_fee ?>">

                        <button type="submit"
                            class="btn   text-white w-100 mb-3"
                            style="background-color:#0d9488;font-size:small;"
                            <?= count($addresses) == 0 ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-lock me-2"></i> Place Order
                        </button>

                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fa-solid fa-shield-halved me-1"></i>
                                100% Secure Payment
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    /* Progress Steps */
    .checkout-steps {
        position: relative;
        max-width: 600px;
        margin: 0 auto;
    }

    .checkout-steps::before {
        content: '';
        position: absolute;
        top: 30px;
        left: 0;
        right: 0;
        height: 2px;
        background: #e5e7eb;
        z-index: 0;
    }

    .step {
        text-align: center;
        position: relative;
        z-index: 1;
    }

    .step-number {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #e5e7eb;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.2rem;
        margin: 0 auto 10px;
        border: 3px solid white;
    }

    .step.active .step-number {
        background: #0d9488;
        color: white;
    }

    .step-label {
        font-size: 0.9rem;
        color: #6b7280;
    }

    .step.active .step-label {
        color: #0d9488;
        font-weight: bold;
    }

    /* Address Cards */
    .address-card {
        position: relative;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .address-card input[type="radio"] {
        position: absolute;
        opacity: 0;
    }

    .address-card input[type="radio"]:checked+label .address-content {
        border-left: 4px solid #0d9488;
        padding-left: 15px;
    }

    .address-card:has(input:checked) {
        border-color: #0d9488;
        background-color: #f0fdf4;
    }

    .address-card:hover {
        border-color: #0d9488;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(13, 148, 136, 0.1);
    }

    /* Payment Options */
    .payment-option {
        position: relative;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .payment-option input[type="radio"] {
        position: absolute;
        opacity: 0;
    }

    .payment-option:has(input:checked) {
        border-color: #0d9488;
        background-color: #f0fdf4;
    }

    .payment-option:hover {
        border-color: #0d9488;
        transform: translateY(-2px);
    }
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>