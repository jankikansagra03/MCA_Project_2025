<?php
include_once 'db_config.php';
// shop.php

// Shop content
$p_id = NULL;
$products = $con->prepare("CALL Products_Select(?)");
$products->bind_param("i", $p_id);
$products->execute();
$product_results = $products->get_result();
ob_start();
?>

<div class="container py-5">


    <!-- Search + Sort Bar -->
    <div class="row mb-4">
        <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12 col-xs-12 mb-2">
            <input type="text" class="form-control" placeholder=" Search mobiles..." style="border:2px solid #0d9488;">
        </div>
        <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12 col-xs-12 mb-2">
            <select class="form-select" style="border:2px solid #0d9488; color:#0d9488;">
                <option selected disabled>Sort by Price</option>
                <option value="low-high">Low to High</option>
                <option value="high-low">High to Low</option>
            </select>
        </div>
        <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12 col-xs-12 mb-2">
            <select class=" form-select" style="border:2px solid #0d9488; color:#0d9488;">
                <option selected disabled>Sort by Company</option>
                <option value="apple">Apple</option>
                <option value="samsung">Samsung</option>
                <option value="oneplus">OnePlus</option>
                <option value="google">Google</option>
            </select>
        </div>
        <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12 col-xs-12 mb-2">
            <input type="button" value="Apply Filter" class="btn text-white w-100" style="background-color:#0d9488;">
        </div>
    </div>
    <h2 class="text-center fw-bold mb-4" style="color:#0d9488;">
        <i class="fa-solid fa-mobile-screen-button me-2" style="color:#0d9488 !important;"></i> Our Collection
    </h2>
    <!-- Products Grid -->
    <div class="row g-4">
        <!-- Product Card 1 -->
        <?php while ($product = $product_results->fetch_assoc()) { ?>
            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12 col-xs-12 mb-2">
                <?php

                $price = $product['price'];
                $discount = $product['discount']; // Assuming you have this column

                // Calculate Final Price
                if ($discount > 0) {
                    $final_price = $price - ($price * ($discount / 100));
                    $has_discount = true;
                } else {
                    $final_price = $price;
                    $has_discount = false;
                }
                ?>

                <div class="col-md-3 col-sm-6">
                    <div class="card product-card h-100">

                        <div class="product-image-wrapper">
                            <?php if ($has_discount): ?>
                                <span class="discount-badge">
                                    -<?= $discount ?>%
                                </span>
                            <?php endif; ?>

                            <img src="images/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="img-fluid">

                            <div class="action-buttons">
                                <a href="product_details.php?id=<?= $product['id'] ?>" class="btn-action" title="View Details">
                                    <i class="fa-solid fa-eye"></i>
                                </a>

                                <form method="POST" action="cart_action.php" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" name="add_to_cart" class="btn-action" title="Add to Cart">
                                        <i class="fa-solid fa-shopping-cart"></i>
                                    </button>
                                </form>

                                <form method="POST" action="wishlist_action.php" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" name="add_to_wishlist" class="btn-action" title="Add to Wishlist">
                                        <i class="fa-regular fa-heart"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="product-info">
                            <div class="category-tag"><?= htmlspecialchars($product['category_name'] ?? 'Category') ?></div>

                            <h5 class="product-title"><?= htmlspecialchars($product['name']) ?></h5>

                            <div class="price-box">
                                <span class="current-price">$<?= number_format($final_price, 2) ?></span>

                                <?php if ($has_discount): ?>
                                    <span class="original-price">$<?= number_format($price, 2) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>

            <?php } ?>
            </div>

            <!-- Product Card 2 -->

    </div>

    <!-- Pagination -->
    <div class="mt-5 d-flex justify-content-center">
        <nav>
            <ul class="pagination">
                <li class="page-item disabled"><a class="page-link">Previous</a></li>
                <li class="page-item active"><a class="page-link" style="background-color:#0d9488; border-color:#0d9488;">1</a></li>
                <li class="page-item"><a class="page-link" style="color:#0d9488;">2</a></li>
                <li class="page-item"><a class="page-link" style="color:#0d9488;">3</a></li>
                <li class="page-item"><a class="page-link" style="color:#0d9488;">Next</a></li>
            </ul>
        </nav>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
