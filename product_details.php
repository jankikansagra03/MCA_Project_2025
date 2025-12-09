<?php
include_once 'db_config.php';
// include_once 'user_authentication.php';

// Get product ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
?>
    <script>
        window.location.href = 'products.php';
    </script>
<?php
    exit();
}

$product_id = (int)$_GET['id'];

// ==========================================
// FETCH PRODUCT DETAILS
// ==========================================
$product = null;

$stmt = $con->prepare("CALL Products_Select(?)");
if ($stmt) {
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $product = $res->fetch_assoc();
        $res->free();
    }

    $stmt->close();
    flush_stored_results($con);
}

// If product not found
if (!$product) {
    setcookie('error', 'Product not found', time() + 5, '/');
?>
    <script>
        window.location.href = 'products.php';
    </script>
<?php
    exit();
}

// ==========================================
// FETCH PRODUCT REVIEWS
// ==========================================
$reviews = [];
$review_stats = [
    'total_reviews' => 0,
    'average_rating' => 0,
    'rating_5' => 0,
    'rating_4' => 0,
    'rating_3' => 0,
    'rating_2' => 0,
    'rating_1' => 0
];

// Get review statistics
$stats_stmt = $con->prepare("CALL Reviews_GetProductRating(?)");
if ($stats_stmt) {
    $stats_stmt->bind_param("i", $product_id);
    $stats_stmt->execute();
    $stats_res = $stats_stmt->get_result();

    if ($stats_res && $stats_res->num_rows > 0) {
        $review_stats = $stats_res->fetch_assoc();
        $stats_res->free();
    }

    $stats_stmt->close();
    flush_stored_results($con);
}

// Get approved reviews
$reviews_stmt = $con->prepare("CALL Reviews_SelectByProduct(?, 'Approved')");
if ($reviews_stmt) {
    $reviews_stmt->bind_param("i", $product_id);
    $reviews_stmt->execute();
    $reviews_res = $reviews_stmt->get_result();

    if ($reviews_res) {
        while ($row = $reviews_res->fetch_assoc()) {
            $reviews[] = $row;
        }
        $reviews_res->free();
    }

    $reviews_stmt->close();
    flush_stored_results($con);
}

// Calculate final price
$final_price = $product['price'] - $product['discount'];

ob_start();
?>

<div class="container py-5">
    <div class="row">
        <!-- Product Images Section -->
        <div class="col-lg-5">
            <div class="product-image-section">
                <div class="main-image mb-3">
                    <img src="images/products/<?= htmlspecialchars($product['image']) ?>"
                        id="mainImage"
                        alt="<?= htmlspecialchars($product['name']) ?>"
                        class="img-fluid rounded shadow-sm"
                        style="width: 100%; height: 450px; object-fit: contain; background:#f8f9fa; padding:20px;"
                        onerror="this.src='images/placeholder.jpg'">
                </div>

                <?php if (!empty($product['gallery_images'])): ?>
                    <?php
                    $gallery = json_decode($product['gallery_images'], true);
                    if (is_array($gallery) && count($gallery) > 0):
                    ?>
                        <div class="gallery-thumbnails d-flex gap-2 overflow-auto">
                            <?php foreach ($gallery as $img): ?>
                                <img src="<?= htmlspecialchars($img) ?>"
                                    alt="Product Image"
                                    class="thumbnail-img rounded"
                                    style="width: 80px; height: 80px; object-fit: contain; background:#f8f9fa; padding:5px; cursor: pointer; border: 2px solid #e5e7eb;"
                                    onclick="changeImage(this.src)"
                                    onerror="this.src='images/placeholder.jpg'">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Details Section -->
        <div class="col-lg-7">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
                </ol>
            </nav>

            <h1 class="fw-bold mb-3" style="color:#0d9488;">
                <?= htmlspecialchars($product['name']) ?>
            </h1>

            <?php if (!empty($product['brand'])): ?>
                <p class="text-muted mb-3">
                    <i class="fa-solid fa-tag me-2"></i>
                    Brand: <strong><?= htmlspecialchars($product['brand']) ?></strong>
                </p>
            <?php endif; ?>

            <!-- Rating Display -->
            <?php if ($review_stats['total_reviews'] > 0): ?>
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="stars">
                            <?php
                            $avg_rating = round($review_stats['average_rating'], 1);
                            for ($i = 1; $i <= 5; $i++):
                            ?>
                                <i class="fa-solid fa-star" style="color: <?= $i <= $avg_rating ? '#fbbf24' : '#e5e7eb' ?>;"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="fw-bold"><?= number_format($avg_rating, 1) ?></span>
                        <span class="text-muted">(<?= $review_stats['total_reviews'] ?> reviews)</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Price Section -->
            <div class="mb-4">
                <h2 class="fw-bold mb-2" style="color:#0d9488;">
                    ₹<?= number_format($final_price, 2) ?>
                </h2>

                <?php if ($product['discount'] > 0): ?>
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-muted text-decoration-line-through fs-5">
                            ₹<?= number_format($product['price'], 2) ?>
                        </span>
                        <span class="badge bg-success fs-6">
                            Save ₹<?= number_format($product['discount'], 2) ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Stock Status -->
            <div class="mb-4">
                <?php if ($product['stock'] > 0): ?>
                    <span class="badge bg-success fs-6">
                        <i class="fa-solid fa-check-circle me-1"></i> In Stock (<?= $product['stock'] ?> available)
                    </span>
                <?php else: ?>
                    <span class="badge bg-danger fs-6">
                        <i class="fa-solid fa-times-circle me-1"></i> Out of Stock
                    </span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <?php if (!empty($product['description'])): ?>
                <div class="mb-4">
                    <h5 class="fw-bold mb-2">Description</h5>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                </div>
            <?php endif; ?>

            <!-- Add to Cart/Wishlist Buttons -->
            <?php if (isset($_SESSION['user_email'])): ?>
                <div class="d-flex gap-3 mb-4">
                    <?php if ($product['stock'] > 0): ?>
                        <form method="POST" action="cart_action.php" class="flex-grow-1">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" name="add_to_cart" class="btn   text-white w-100" style="background-color:#0d9488;font-size:small;">
                                <i class="fa-solid fa-shopping-cart me-2"></i> Add to Cart
                            </button>
                        </form>
                    <?php else: ?>
                        <button class="btn   btn-secondary w-100" disabled>
                            <i class="fa-solid fa-ban me-2"></i> Out of Stock
                        </button>
                    <?php endif; ?>

                    <form method="POST" action="wishlist_action.php">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <button type="submit" name="add_to_wishlist" class="btn   btn-outline-danger">
                            <i class="fa-solid fa-heart"></i>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    Please <a href="login.php" class="alert-link">login</a> to add items to cart or wishlist
                </div>
            <?php endif; ?>

            <!-- Product Category -->
            <?php if (!empty($product['category_name'])): ?>
                <div class="mb-3">
                    <strong>Category:</strong>
                    <span class="badge" style="background-color:#0d9488;font-size:small;">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Long Description -->
    <?php if (!empty($product['long_description'])): ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header" style="background-color:#0d9488; color:white;">
                        <h5 class="mb-0">
                            <i class="fa-solid fa-info-circle me-2"></i> Product Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <?= nl2br(htmlspecialchars($product['long_description'])) ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ✅ CUSTOMER REVIEWS SECTION (NEW) -->
    <div class="row mt-5" id="reviews">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color:#0d9488; color:white;">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-star me-2"></i> Customer Reviews
                        <?php if ($review_stats['total_reviews'] > 0): ?>
                            <span class="badge bg-warning text-dark ms-2">
                                <?= $review_stats['total_reviews'] ?>
                            </span>
                        <?php endif; ?>
                    </h5>

                    <!-- Write Review Button -->
                    <?php if (isset($_SESSION['user_email'])): ?>
                        <a href="submit_review.php?product_id=<?= $product['id'] ?>"
                            class="btn btn-light btn-sm">
                            <i class="fa-solid fa-pen me-1"></i> Write a Review
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-light btn-sm">
                            <i class="fa-solid fa-sign-in me-1"></i> Login to Review
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">

                    <?php if ($review_stats['total_reviews'] > 0): ?>
                        <!-- Rating Summary -->
                        <div class="row mb-4">
                            <div class="col-md-4 text-center border-end">
                                <h1 class="display-3 fw-bold mb-2" style="color:#0d9488;">
                                    <?= number_format($review_stats['average_rating'], 1) ?>
                                </h1>
                                <div class="mb-2">
                                    <?php
                                    $avg_rating = round($review_stats['average_rating'], 1);
                                    for ($i = 1; $i <= 5; $i++):
                                    ?>
                                        <i class="fa-solid fa-star fa-lg" style="color: <?= $i <= $avg_rating ? '#fbbf24' : '#e5e7eb' ?>;"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-muted mb-0">Based on <?= $review_stats['total_reviews'] ?> reviews</p>
                            </div>

                            <div class="col-md-8">
                                <h6 class="mb-3">Rating Distribution</h6>
                                <?php
                                $ratings = [5, 4, 3, 2, 1];
                                foreach ($ratings as $rating):
                                    $count = $review_stats["rating_$rating"];
                                    $percentage = $review_stats['total_reviews'] > 0
                                        ? ($count / $review_stats['total_reviews']) * 100
                                        : 0;
                                ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="me-2" style="width: 80px;">
                                            <?= $rating ?>
                                            <i class="fa-solid fa-star fa-sm" style="color:#fbbf24;"></i>
                                        </span>
                                        <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                            <div class="progress-bar"
                                                style="width: <?= $percentage ?>%; background-color:#0d9488;">
                                            </div>
                                        </div>
                                        <span class="text-muted" style="width: 50px;"><?= $count ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <hr>

                        <!-- Reviews List -->
                        <h6 class="mb-3">Customer Reviews</h6>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item mb-4 pb-4 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?= htmlspecialchars($review['user_name']) ?></h6>
                                        <div class="mb-1">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fa-solid fa-star fa-sm"
                                                    style="color: <?= $i <= $review['rating'] ? '#fbbf24' : '#e5e7eb' ?>;"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fa-solid fa-calendar me-1"></i>
                                        <?= date('d M Y', strtotime($review['created_at'])) ?>
                                    </small>
                                </div>

                                <h6 class="fw-bold mb-2"><?= htmlspecialchars($review['title']) ?></h6>
                                <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($review['review'])) ?></p>
                            </div>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <!-- No Reviews Yet -->
                        <div class="text-center py-5">
                            <i class="fa-solid fa-comments fa-3x mb-3 d-block" style="color:#e5e7eb;"></i>
                            <h5 class="text-muted mb-3">No reviews yet</h5>
                            <p class="text-muted mb-3">Be the first to review this product!</p>

                            <?php if (isset($_SESSION['user_email'])): ?>
                                <a href="submit_review.php?product_id=<?= $product['id'] ?>"
                                    class="btn text-white"
                                    style="background-color:#0d9488;font-size:small;">
                                    <i class="fa-solid fa-pen me-2"></i> Write First Review
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-secondary" style="font-size:small;">
                                    <i class="fa-solid fa-sign-in me-2"></i> Login to Review
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

</div>

<script>
    function changeImage(src) {
        document.getElementById('mainImage').src = src;
    }
</script>

<style>
    .thumbnail-img:hover {
        border-color: #0d9488 !important;
        transform: scale(1.05);
        transition: all 0.3s ease;
    }

    .review-item:last-child {
        border-bottom: none !important;
        padding-bottom: 0 !important;
    }

    .stars i {
        font-size: 1.2rem;
    }
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>