<?php
include_once 'db_config.php';
include_once 'user_authentication.php';

$user_email = $_SESSION['user_email'];

// Get product ID from URL
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    setcookie('error', 'Invalid product', time() + 5, '/');
?>
    <script>
        window.location.href = 'products.php';
    </script>
<?php
    exit();
}

$product_id = (int)$_GET['product_id'];

// ==========================================
// FETCH PRODUCT DETAILS
// ==========================================
$product = null;

$product_stmt = $con->prepare("CALL Products_Select(?)");
if ($product_stmt) {
    $product_stmt->bind_param("i", $product_id);
    $product_stmt->execute();
    $product_res = $product_stmt->get_result();

    if ($product_res && $product_res->num_rows > 0) {
        $product = $product_res->fetch_assoc();
        $product_res->free();
    }

    $product_stmt->close();
    flush_stored_results($con);
}

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
// CHECK IF USER CAN REVIEW
// ==========================================
$can_review = false;
$already_reviewed = false;

$check_stmt = $con->prepare("CALL Reviews_CheckCanReview(?, ?)");
if ($check_stmt) {
    $check_stmt->bind_param("si", $user_email, $product_id);
    $check_stmt->execute();

    // First result: can_review
    $can_res = $check_stmt->get_result();
    if ($can_res && $can_res->num_rows > 0) {
        $can_row = $can_res->fetch_assoc();
        $can_review = ($can_row['can_review'] > 0);
        $can_res->free();
    }

    // Move to next result
    $check_stmt->next_result();

    // Second result: already_reviewed
    $already_res = $check_stmt->get_result();
    if ($already_res && $already_res->num_rows > 0) {
        $already_row = $already_res->fetch_assoc();
        $already_reviewed = ($already_row['already_reviewed'] > 0);
        $already_res->free();
    }

    $check_stmt->close();
    flush_stored_results($con);
}

// Get user name
$user_name = '';
$user_stmt = $con->prepare("CALL Registration_Select(NULL, ?, NULL, NULL, NULL, NULL, NULL)");
if ($user_stmt) {
    $user_stmt->bind_param("s", $user_email);
    $user_stmt->execute();
    $user_res = $user_stmt->get_result();

    if ($user_res && $user_res->num_rows > 0) {
        $user_row = $user_res->fetch_assoc();
        $user_name = $user_row['fullname'];
        $user_res->free();
    }

    $user_stmt->close();
    flush_stored_results($con);
}

ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Product Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="images/products/<?= htmlspecialchars($product['image']) ?>"
                            alt="<?= htmlspecialchars($product['name']) ?>"
                            class="rounded me-3"
                            style="width: 100px; height: 100px; object-fit: contain; background:#f8f9fa; padding:10px;"
                            onerror="this.src='images/placeholder.jpg'">

                        <div>
                            <h5 class="mb-1 fw-bold"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="text-muted mb-0"><?= htmlspecialchars($product['category_name'] ?? 'Product') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($already_reviewed): ?>
                <!-- Already Reviewed -->
                <div class="alert alert-info">
                    <h5 class="alert-heading">
                        <i class="fa-solid fa-info-circle me-2"></i> Already Reviewed
                    </h5>
                    <p class="mb-0">You have already submitted a review for this product.</p>
                    <a href="my_reviews.php" class="btn btn-sm btn-primary mt-2">
                        View My Reviews
                    </a>
                </div>

            <?php elseif (!$can_review): ?>
                <!-- Cannot Review -->
                <div class="alert alert-warning">
                    <h5 class="alert-heading">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i> Cannot Submit Review
                    </h5>
                    <p class="mb-0">You can only review products that you have purchased and received.</p>
                    <a href="products.php" class="btn btn-sm btn-primary mt-2">
                        Continue Shopping
                    </a>
                </div>

            <?php else: ?>
                <!-- Review Form -->
                <div class="card shadow-sm">
                    <div class="card-header text-white" style="background-color:#0d9488;font-size:small;">
                        <h5 class="mb-0">
                            <i class="fa-solid fa-star me-2"></i> Write a Review
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="process_review.php" id="reviewForm">
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <input type="hidden" name="user_name" value="<?= htmlspecialchars($user_name) ?>">

                            <!-- Rating -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fa-solid fa-star me-2" style="color:#fbbf24;"></i>
                                    Rating <span class="text-danger">*</span>
                                </label>
                                <div class="star-rating">
                                    <input type="radio" name="rating" id="star5" value="5" required>
                                    <label for="star5" title="5 stars">
                                        <i class="fa-solid fa-star"></i>
                                    </label>

                                    <input type="radio" name="rating" id="star4" value="4">
                                    <label for="star4" title="4 stars">
                                        <i class="fa-solid fa-star"></i>
                                    </label>

                                    <input type="radio" name="rating" id="star3" value="3">
                                    <label for="star3" title="3 stars">
                                        <i class="fa-solid fa-star"></i>
                                    </label>

                                    <input type="radio" name="rating" id="star2" value="2">
                                    <label for="star2" title="2 stars">
                                        <i class="fa-solid fa-star"></i>
                                    </label>

                                    <input type="radio" name="rating" id="star1" value="1">
                                    <label for="star1" title="1 star">
                                        <i class="fa-solid fa-star"></i>
                                    </label>
                                </div>
                                <small class="text-muted">Click on the stars to rate</small>
                            </div>

                            <!-- Review Title -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    Review Title <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    name="title"
                                    class="form-control"
                                    placeholder="Summarize your experience"
                                    maxlength="200"
                                    required>
                                <small class="text-muted">Maximum 200 characters</small>
                            </div>

                            <!-- Review Text -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    Your Review <span class="text-danger">*</span>
                                </label>
                                <textarea name="review"
                                    class="form-control"
                                    rows="6"
                                    placeholder="Share your experience with this product..."
                                    required></textarea>
                                <small class="text-muted">Minimum 10 characters</small>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="product_details.php?id=<?= $product_id ?>" class="btn btn-secondary">
                                    Cancel
                                </a>
                                <button type="submit" name="submit_review" class="btn text-white" style="background-color:#0d9488;font-size:small;">
                                    <i class="fa-solid fa-paper-plane me-2"></i> Submit Review
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Review Guidelines -->
                <div class="alert alert-info mt-4">
                    <h6 class="alert-heading">
                        <i class="fa-solid fa-lightbulb me-2"></i> Review Guidelines
                    </h6>
                    <ul class="mb-0 small">
                        <li>Be honest and detailed in your review</li>
                        <li>Focus on the product quality and your experience</li>
                        <li>Avoid offensive language or personal information</li>
                        <li>Your review will be published after admin approval</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    /* Star Rating */
    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 10px;
        font-size: 2.5rem;
    }

    .star-rating input {
        display: none;
    }

    .star-rating label {
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
    }

    .star-rating label:hover,
    .star-rating label:hover~label,
    .star-rating input:checked~label {
        color: #fbbf24;
    }
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>