<?php
include_once 'db_config.php';
include_once 'user_authentication.php';

$user_email = $_SESSION['user_email'];

// FETCH WISHLIST ITEMS
$wishlist_items = [];

$stmt = $con->prepare("CALL Wishlist_Select(?, NULL)");
if ($stmt) {
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            // Calculate final price
            $price = $row['price'];
            $discount = $row['discount'];

            if ($discount > 0) {
                $row['final_price'] = $price - ($price * ($discount / 100));
                $row['has_discount'] = true;
            } else {
                $row['final_price'] = $price;
                $row['has_discount'] = false;
            }

            $wishlist_items[] = $row;
        }
        $res->free();
    }

    $stmt->close();
    flush_stored_results($con);
}

ob_start();
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0" style="color:#0d9488;">
            <i class="fa-solid fa-heart me-2"></i> My Wishlist
        </h2>
        <?php if (count($wishlist_items) > 0): ?>
            <form method="POST" action="wishlist_action.php" style="display:inline;">
                <button type="submit" name="clear_wishlist"
                    class="btn btn-outline-danger"
                    onclick="return confirm('Clear entire wishlist?')">
                    <i class="fa-solid fa-trash me-2"></i> Clear All
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (count($wishlist_items) == 0): ?>
        <div class="text-center py-5">
            <i class="fa-solid fa-heart-crack" style="font-size: 5rem; color:#e5e7eb;"></i>
            <h4 class="mt-4 text-muted">Your wishlist is empty</h4>
            <p class="text-muted">Save your favorite products here!</p>
            <a href="products.php" class="btn text-white mt-3" style="background-color:#0d9488;font-size:small;">
                <i class="fa-solid fa-shop me-2"></i> Browse Products
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <p class="text-muted mb-4">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    You have <?= count($wishlist_items) ?> item<?= count($wishlist_items) > 1 ? 's' : '' ?> in your wishlist
                </p>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12">
                    <div class="card wishlist-card h-100 shadow-sm">
                        <div class="position-relative">
                            <?php if ($item['has_discount']): ?>
                                <span class="discount-badge">-<?= $item['discount'] ?>%</span>
                            <?php endif; ?>

                            <?php if ($item['stock'] <= 0): ?>
                                <span class="out-of-stock-badge">Out of Stock</span>
                            <?php elseif ($item['stock'] <= 5): ?>
                                <span class="low-stock-badge">Only <?= $item['stock'] ?> left!</span>
                            <?php endif; ?>

                            <img src="images/products/<?= htmlspecialchars($item['image']) ?>"
                                alt="<?= htmlspecialchars($item['name']) ?>"
                                class="card-img-top wishlist-image"
                                onerror="this.src='images/placeholder.jpg'">

                            <!-- Remove from Wishlist -->
                            <form method="POST" action="wishlist_action.php" class="position-absolute top-0 end-0 m-2">
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <button type="submit" name="remove_from_wishlist"
                                    class="btn btn-sm btn-danger rounded-circle"
                                    title="Remove from Wishlist"
                                    style="width: 35px; height: 35px;">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </form>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <span class="badge mb-2" style="background-color:#0d9488; width: fit-content;">
                                <?= htmlspecialchars($item['category_name'] ?? 'Category') ?>
                            </span>

                            <h5 class="card-title fw-bold mb-2" style="color:#0d9488;">
                                <?= htmlspecialchars($item['name']) ?>
                            </h5>

                            <?php if (!empty($item['brand'])): ?>
                                <p class="text-muted small mb-2">
                                    <i class="fa-solid fa-tag me-1"></i>
                                    Brand: <?= htmlspecialchars($item['brand']) ?>
                                </p>
                            <?php endif; ?>

                            <div class="price-section mb-3">
                                <h4 class="mb-0 fw-bold" style="color:#0d9488;">
                                    ₹<?= number_format($item['final_price'], 2) ?>
                                </h4>

                                <?php if ($item['has_discount']): ?>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="text-muted text-decoration-line-through">
                                            ₹<?= number_format($item['price'], 2) ?>
                                        </span>
                                        <span class="badge bg-success">
                                            Save ₹<?= number_format($item['price'] - $item['final_price'], 2) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-auto">
                                <div class="d-grid gap-2">
                                    <?php if ($item['stock'] > 0): ?>
                                        <form method="POST" action="wishlist_action.php">
                                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                            <button type="submit" name="move_to_cart"
                                                class="btn text-white w-100"
                                                style="background-color:#0d9488;font-size:small;">
                                                <i class="fa-solid fa-shopping-cart me-2"></i> Add to Cart
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary w-100" disabled>
                                            <i class="fa-solid fa-ban me-2"></i> Out of Stock
                                        </button>
                                    <?php endif; ?>

                                    <a href="product_details.php?id=<?= $item['product_id'] ?>"
                                        class="btn btn-outline-secondary w-100">
                                        <i class="fa-solid fa-eye me-2"></i> View Details
                                    </a>
                                </div>
                            </div>

                            <?php if (!empty($item['description'])): ?>
                                <p class="text-muted small mt-3 mb-0">
                                    <?= htmlspecialchars(substr($item['description'], 0, 80)) ?>
                                    <?= strlen($item['description']) > 80 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Continue Shopping Button -->
        <div class="text-center mt-5">
            <a href="products.php" class="btn btn-outline-secondary  ">
                <i class="fa-solid fa-arrow-left me-2"></i> Continue Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
    .wishlist-card {
        transition: all 0.3s ease;
        border: 2px solid #f0f0f0;
    }

    .wishlist-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(13, 148, 136, 0.15) !important;
        border-color: #0d9488;
    }

    .wishlist-image {
        height: 250px;
        object-fit: contain;
        padding: 15px;
        background: #f8f9fa;
    }

    .discount-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        background: #dc2626;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: bold;
        z-index: 2;
    }

    .low-stock-badge {
        position: absolute;
        top: 50px;
        left: 10px;
        background: #f59e0b;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: bold;
        z-index: 2;
    }

    .out-of-stock-badge {
        position: absolute;
        top: 50px;
        left: 10px;
        background: #dc2626;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: bold;
        z-index: 2;
    }

    .price-section {
        border-top: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;
        padding: 10px 0;
    }
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>