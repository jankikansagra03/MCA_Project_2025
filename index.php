<?php
include_once 'db_config.php';

// ==========================================
// FETCH FEATURED PRODUCTS
// ==========================================
$featured_products = [];

// Fetch products marked as featured or top 6 products
$query = "SELECT p.*, c.category_name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.status = 'Active' 
          AND p.stock > 0
          ORDER BY p.id DESC 
          LIMIT 6";

$result = $con->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $featured_products[] = $row;
    }
    $result->free();
}

ob_start();
?>

<div class="container py-5">
    <h2 class="text-center fw-bold mb-4" style="color:#0d9488;">
        <i class="fa-solid fa-star me-2" style="color:#0d9488"></i>
        Featured Products
        <i class="fa-solid fa-star ms-2" style="color:#0d9488;"></i>
    </h2>

    <?php if (count($featured_products) > 0): ?>
        <div class="row g-4">
            <?php foreach ($featured_products as $product):
                // Calculate final price after discount
                $final_price = $product['price'] - $product['discount'];
                $discount_percent = $product['price'] > 0
                    ? round(($product['discount'] / $product['price']) * 100)
                    : 0;
            ?>
                <div class="col-md-6 col-sm-12 col-xs-12 col-lg-4 col-xxl-4 col-xl-4">
                    <div class="card shadow-sm border-0 h-100 product-card">
                        <!-- Product Image -->
                        <div class="position-relative">
                            <img src="images/products/<?= htmlspecialchars($product['image']) ?>"
                                class="card-img-top"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                style="height: 300px; object-fit: contain; background: #f8f9fa; padding: 20px;"
                                onerror="this.src='images/placeholder.jpg'">

                            <!-- Discount Badge -->
                            <?php if ($discount_percent > 0): ?>
                                <span class="badge bg-danger position-absolute top-0 end-0 m-3">
                                    <?= $discount_percent ?>% OFF
                                </span>
                            <?php endif; ?>

                            <!-- Stock Badge -->
                            <?php if ($product['stock'] <= 10): ?>
                                <span class="badge bg-warning position-absolute top-0 start-0 m-3 text-dark">
                                    Only <?= $product['stock'] ?> left!
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body text-center d-flex flex-column">
                            <!-- Category -->
                            <?php if (!empty($product['category_name'])): ?>
                                <p class="text-muted small mb-2">
                                    <i class="fa-solid fa-tag me-1"></i>
                                    <?= htmlspecialchars($product['category_name']) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Product Name -->
                            <h5 class="card-title fw-bold" style="color:#0d9488;">
                                <?= htmlspecialchars($product['name']) ?>
                            </h5>

                            <!-- Description -->
                            <p class="card-text text-muted small mb-3">
                                <?= htmlspecialchars(substr($product['description'], 0, 80)) ?>...
                            </p>

                            <!-- Price -->
                            <div class="mb-3 mt-auto">
                                <p class="fw-bold mb-1" style="color:#0d9488; font-size: 1.5rem;">
                                    ₹<?= number_format($final_price, 2) ?>
                                </p>
                                <?php if ($discount_percent > 0): ?>
                                    <p class="text-muted small mb-0">
                                        <span class="text-decoration-line-through">
                                            ₹<?= number_format($product['price'], 2) ?>
                                        </span>
                                        <span class="text-success ms-2">
                                            Save ₹<?= number_format($product['discount'], 2) ?>
                                        </span>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <!-- Buttons -->
                            <div class="d-flex gap-2">
                                <a href="product_details.php?id=<?= $product['id'] ?>"
                                    class="btn btn-outline-secondary flex-grow-1">
                                    <i class="fa-solid fa-eye me-1"></i> View
                                </a>

                                <?php if (isset($_SESSION['user_email'])): ?>
                                    <form method="POST" action="cart_action.php" class="flex-grow-1">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" name="add_to_cart"
                                            class="btn text-white w-100"
                                            style="background-color:#0d9488;font-size:small;">
                                            <i class="fa-solid fa-cart-plus me-1"></i> Add to Cart
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="login.php"
                                        class="btn text-white flex-grow-1"
                                        style="background-color:#0d9488;font-size:small;">
                                        <i class="fa-solid fa-sign-in me-1"></i> Login to Buy
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- View All Button -->
        <div class="text-center mt-5">
            <a href="products.php" class="btn   text-white px-5" style="background-color:#0d9488;font-size:small;">
                <i class="fa-solid fa-grid me-2"></i> View All Products
            </a>
        </div>

    <?php else: ?>
        <!-- No Products -->
        <div class="alert alert-info text-center">
            <i class="fa-solid fa-box-open fa-3x mb-3 d-block" style="color:#0d9488;"></i>
            <h4>No Featured Products Available</h4>
            <p class="mb-0">Check back soon for amazing deals!</p>
        </div>
    <?php endif; ?>
</div>

<style>
    .product-card {
        transition: all 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    }

    .card-img-top {
        transition: transform 0.3s ease;
    }

    .product-card:hover .card-img-top {
        transform: scale(1.05);
    }
</style>

<?php
$content = ob_get_clean();
include_once("layout.php");
?>