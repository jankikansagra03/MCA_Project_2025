<?php
include_once 'db_config.php';
include_once 'user_authentication.php';

$user_email = $_SESSION['user_email'];

// FETCH CART ITEMS (now includes all product details)
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
            // Calculate prices
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

// Shipping calculation
$shipping_fee = 0;
$free_shipping_threshold = 500;
if ($total_amount > 0 && $total_amount < $free_shipping_threshold) {
    $shipping_fee = 50;
}

$grand_total = $total_amount + $shipping_fee;

ob_start();
?>
<div class="container py-5">
    <h2 class="fw-bold mb-4" style="color:#0d9488;">
        <i class="fa-solid fa-shopping-cart me-2"></i> Shopping Cart
    </h2>

    <?php if (count($cart_items) == 0): ?>
        <div class="text-center py-5">
            <i class="fa-solid fa-shopping-cart" style="font-size: 5rem; color:#e5e7eb;"></i>
            <h4 class="mt-4 text-muted">Your cart is empty</h4>
            <p class="text-muted">Add some products to get started!</p>
            <a href="products.php" class="btn text-white mt-3" style="background-color:#0d9488;font-size:small;">
                <i class="fa-solid fa-shop me-2"></i> Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <!-- Product Image -->
                                    <div class="col-md-2 col-sm-3 text-center">
                                        <img src="images/products/<?= htmlspecialchars($item['image']) ?>"
                                            alt="<?= htmlspecialchars($item['name']) ?>"
                                            class="img-fluid rounded"
                                            style="max-height: 100px; object-fit: contain;"
                                            onerror="this.src='images/placeholder.jpg'">
                                    </div>

                                    <!-- Product Info -->
                                    <div class="col-md-4 col-sm-9">
                                        <h5 class="fw-bold mb-1" style="color:#0d9488;">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </h5>
                                        <p class="text-muted small mb-1">
                                            Category: <?= htmlspecialchars($item['category_name'] ?? 'N/A') ?>
                                        </p>
                                        <p class="mb-0">
                                            <span class="fw-bold">₹<?= number_format($item['price'] - ($item['price'] * ($item['discount'] / 100)), 2) ?></span>
                                            <?php if ($item['discount'] > 0): ?>
                                                <span class="text-muted text-decoration-line-through small ms-2">
                                                    ₹<?= number_format($item['price'], 2) ?>
                                                </span>
                                                <span class="badge bg-success ms-2"><?= $item['discount'] ?>% OFF</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <!-- Quantity Controls -->
                                    <div class="col-md-3 col-sm-6 mt-3 mt-md-0">
                                        <label class="small text-muted mb-1">Quantity:</label>
                                        <div class="input-group" style="max-width: 140px;">
                                            <form method="POST" action="cart_action.php" style="display:inline;">
                                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                                <button type="submit" name="decrease_quantity" class="btn btn-outline-secondary btn-sm">
                                                    <i class="fa-solid fa-minus"></i>
                                                </button>
                                            </form>

                                            <input type="text"
                                                class="form-control form-control-sm text-center"
                                                value="<?= $item['quantity'] ?>"
                                                readonly
                                                style="max-width: 50px;">

                                            <form method="POST" action="cart_action.php" style="display:inline;">
                                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                                <button type="submit" name="increase_quantity" class="btn btn-outline-secondary btn-sm"
                                                    <?= $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>>
                                                    <i class="fa-solid fa-plus"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <?php if ($item['stock'] <= 5): ?>
                                            <small class="text-warning d-block mt-1">Only <?= $item['stock'] ?> left!</small>
                                        <?php endif; ?>
                                        <?php if ($item['stock'] <= 0): ?>
                                            <small class="text-danger d-block mt-1">Out of Stock!</small>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Price & Remove -->
                                    <div class="col-md-3 col-sm-6 text-md-end mt-3 mt-md-0">
                                        <h5 class="fw-bold mb-2" style="color:#0d9488;">
                                            ₹<?= number_format($item['final_total'], 2) ?>
                                        </h5>
                                        <?php if ($item['discount_amount'] > 0): ?>
                                            <p class="text-success small mb-2">
                                                Saved: ₹<?= number_format($item['discount_amount'], 2) ?>
                                            </p>
                                        <?php endif; ?>

                                        <form method="POST" action="cart_action.php" style="display:inline;">
                                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                            <button type="submit" name="remove_from_cart"
                                                class="btn btn-outline-danger btn-sm"
                                                onclick="return confirm('Remove this item from cart?')">
                                                <i class="fa-solid fa-trash me-1"></i> Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Clear Cart Button -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="products.php" class="btn btn-outline-secondary" style="font-size:small;">
                            <i class="fa-solid fa-arrow-left me-2"></i> Continue Shopping
                        </a>

                        <form method="POST" action="cart_action.php" style="display:inline;">
                            <button type="submit" name="clear_cart"
                                class="btn btn-outline-danger"
                                onclick="return confirm('Clear entire cart?')">
                                <i class="fa-solid fa-trash me-2"></i> Clear Cart
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header text-white" style="background-color:#0d9488;font-size:small;">
                        <h5 class="mb-0"><i class="fa-solid fa-receipt me-2"></i> Order Summary</h5>
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

                        <?php if ($total_amount < $free_shipping_threshold && $total_amount > 0): ?>
                            <div class="alert alert-info small mt-3 mb-3">
                                <i class="fa-solid fa-truck me-1"></i>
                                Add ₹<?= number_format($free_shipping_threshold - $total_amount, 2) ?> more for FREE shipping!
                            </div>
                        <?php endif; ?>

                        <hr>

                        <div class="d-flex justify-content-between mb-3">
                            <h5 class="fw-bold">Total:</h5>
                            <h5 class="fw-bold" style="color:#0d9488;">₹<?= number_format($grand_total, 2) ?></h5>
                        </div>

                        <a href="checkout.php" class="btn   text-white w-100" style="background-color:#0d9488;font-size:small;">
                            <i class="fa-solid fa-lock me-2"></i> Proceed to Checkout
                        </a>

                        <p class="text-muted small text-center mt-3 mb-0">
                            <i class="fa-solid fa-shield-halved me-1"></i>
                            Secure checkout powered by SSL
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .cart-item {
        transition: all 0.3s ease;
    }

    .cart-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(13, 148, 136, 0.1) !important;
    }

    .input-group .btn {
        border-color: #0d9488;
        color: #0d9488;
    }

    .input-group .btn:hover:not(:disabled) {
        background-color: #0d9488;
        color: white;
    }

    .input-group .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>