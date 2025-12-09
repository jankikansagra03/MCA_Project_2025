<?php
include_once 'db_config.php';

// GET FILTER PARAMETERS
$search = trim($_GET['search'] ?? '');
$sort_price = trim($_GET['sort_price'] ?? '');
$category_filter = trim($_GET['category'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;

// FETCH ALL CATEGORIES FOR FILTER DROPDOWN
$categories = [];
$cat_stmt = $con->prepare("CALL Category_GetActive()");
if ($cat_stmt) {
    $cat_stmt->execute();
    $cat_res = $cat_stmt->get_result();
    while ($cat = $cat_res->fetch_assoc()) {
        if ($cat['status'] == 'Active') {
            $categories[] = $cat;
        }
    }
    $cat_res->free();
    $cat_stmt->close();
    flush_stored_results($con);
}

// FETCH PRODUCTS WITH FILTERS
$all_products = [];

// Use search if provided
if (!empty($search) || !empty($category_filter)) {
    $p_id = NULL;
    $p_name = !empty($search) ? $search : NULL;
    $p_category_id = !empty($category_filter) ? (int)$category_filter : NULL;
    $p_status = 'Active';

    $stmt = $con->prepare("CALL Products_Search(?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isis", $p_id, $p_name, $p_category_id, $p_status);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $all_products[] = $row;
        }
        $res->free();
        $stmt->close();
        flush_stored_results($con);
    }
} else {
    // Get all active products
    $p_id = NULL;
    $stmt = $con->prepare("CALL Products_Select(?)");
    if ($stmt) {
        $stmt->bind_param("i", $p_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if ($row['status'] == 'Active') {
                $all_products[] = $row;
            }
        }
        $res->free();
        $stmt->close();
        flush_stored_results($con);
    }
}

// APPLY PRICE SORTING
if ($sort_price == 'low-high') {
    usort($all_products, function ($a, $b) {
        $price_a = $a['price'] - ($a['price'] * ($a['discount'] / 100));
        $price_b = $b['price'] - ($b['price'] * ($b['discount'] / 100));
        return $price_a <=> $price_b;
    });
} elseif ($sort_price == 'high-low') {
    usort($all_products, function ($a, $b) {
        $price_a = $a['price'] - ($a['price'] * ($a['discount'] / 100));
        $price_b = $b['price'] - ($b['price'] * ($b['discount'] / 100));
        return $price_b <=> $price_a;
    });
}

// PAGINATION
$total = count($all_products);
$totalPages = (int) ceil(max(1, $total) / $perPage);
$offset = ($page - 1) * $perPage;
$products = array_slice($all_products, $offset, $perPage);

ob_start();
?>

<div class="container py-5">
    <!-- Search + Filter Bar -->
    <form method="GET" action="shop.php" id="filterForm">
        <div class="row mb-4">
            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12 col-xs-12 mb-2">
                <input type="text"
                    name="search"
                    class="form-control"
                    placeholder="🔍 Search mobiles..."
                    value="<?= htmlspecialchars($search) ?>"
                    style="border:2px solid #0d9488;">
            </div>

            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12 col-xs-12 mb-2">
                <select name="sort_price" class="form-select" style="border:2px solid #0d9488; color:#0d9488;">
                    <option value="" <?= $sort_price == '' ? 'selected' : '' ?>>Sort by Price</option>
                    <option value="low-high" <?= $sort_price == 'low-high' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="high-low" <?= $sort_price == 'high-low' ? 'selected' : '' ?>>Price: High to Low</option>
                </select>
            </div>

            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12 col-xs-12 mb-2">
                <select name="category" class="form-select" style="border:2px solid #0d9488; color:#0d9488;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12 col-xs-12 mb-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn text-white flex-grow-1" style="background-color:#0d9488;font-size:small;">
                        <i class="fa-solid fa-filter me-1"></i> Apply Filter
                    </button>
                    <a href="shop.php" class="btn btn-outline-secondary" style="font-size:small;">
                        <i class="fa-solid fa-rotate-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </form>

    <!-- Results Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0" style="color:#0d9488;">
            <i class="fa-solid fa-mobile-screen-button me-2"></i> Our Collection
        </h2>
        <p class="text-muted mb-0">
            Showing <?= count($products) ?> of <?= $total ?> products
        </p>
    </div>

    <?php if (count($products) == 0): ?>
        <div class="alert alert-info text-center" role="alert">
            <i class="fa-solid fa-info-circle me-2"></i>
            No products found matching your criteria. Please try different filters.
        </div>
    <?php else: ?>
        <!-- Products Grid -->
        <div class="row g-4">
            <?php foreach ($products as $product):
                $price = $product['price'];
                $discount = $product['discount'];

                // Calculate Final Price
                if ($discount > 0) {
                    $final_price = $price - ($price * ($discount / 100));
                    $has_discount = true;
                } else {
                    $final_price = $price;
                    $has_discount = false;
                }
            ?>
                <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12 col-xs-12 mb-2">
                    <div class="card product-card h-100">
                        <div class="product-image-wrapper">
                            <?php if ($has_discount): ?>
                                <span class="discount-badge">
                                    -<?= $discount ?>%
                                </span>
                            <?php endif; ?>

                            <?php if ($product['stock'] <= 0): ?>
                                <span class="out-of-stock-badge">
                                    Out of Stock
                                </span>
                            <?php elseif ($product['stock'] <= 5): ?>
                                <span class="low-stock-badge">
                                    Only <?= $product['stock'] ?> left!
                                </span>
                            <?php endif; ?>

                            <img src="images/products/<?php echo $product['image']; ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                class="product-image"
                                onerror="this.src='images/placeholder.jpg'">

                            <div class="action-buttons">
                                <a href="product_details.php?id=<?= $product['id'] ?>"
                                    class="btn-action"
                                    title="View Details">
                                    <i class="fa-solid fa-eye"></i>
                                </a>

                                <?php if ($product['stock'] > 0): ?>
                                    <form method="POST" action="cart_action.php" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" name="add_to_cart" class="btn-action" title="Add to Cart">
                                            <i class="fa-solid fa-shopping-cart"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-action" disabled title="Out of Stock" style="opacity:0.5; cursor:not-allowed;">
                                        <i class="fa-solid fa-shopping-cart"></i>
                                    </button>
                                <?php endif; ?>

                                <form method="POST" action="wishlist_action.php" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" name="add_to_wishlist" class="btn-action" title="Add to Wishlist">
                                        <i class="fa-regular fa-heart"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="product-info">
                            <div class="category-tag">
                                <?= htmlspecialchars($product['category_name'] ?? 'Category') ?>
                            </div>

                            <h5 class="product-title">
                                <?= htmlspecialchars($product['name']) ?>
                            </h5>

                            <div class="price-box">
                                <span class="current-price">₹<?= number_format($final_price, 2) ?></span>

                                <?php if ($has_discount): ?>
                                    <span class="original-price">₹<?= number_format($price, 2) ?></span>
                                    <span class="save-amount">Save ₹<?= number_format($price - $final_price, 2) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-5" aria-label="Product Pagination">
                <ul class="pagination justify-content-center">
                    <?php
                    $qsBase = '';
                    if ($search !== '') $qsBase .= '&search=' . urlencode($search);
                    if ($sort_price !== '') $qsBase .= '&sort_price=' . urlencode($sort_price);
                    if ($category_filter !== '') $qsBase .= '&category=' . urlencode($category_filter);

                    $prevDisabled = $page <= 1 ? 'disabled' : '';
                    $nextDisabled = $page >= $totalPages ? 'disabled' : '';
                    $prevPage = $page - 1;
                    $nextPage = $page + 1;
                    ?>

                    <li class="page-item prev-next <?= $prevDisabled ?>">
                        <a class="page-link" href="?page=<?= $prevPage . $qsBase ?>">
                            <i class="fa fa-chevron-left"></i> Previous
                        </a>
                    </li>

                    <?php
                    $range = 3;
                    $startp = max(1, $page - $range);
                    $endp = min($totalPages, $page + $range);
                    for ($p = $startp; $p <= $endp; $p++):
                    ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $p . $qsBase ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item prev-next <?= $nextDisabled ?>">
                        <a class="page-link" href="?page=<?= $nextPage . $qsBase ?>">
                            Next <i class="fa fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Additional Styles -->
<style>
    .low-stock-badge {
        position: absolute;
        top: 50px;
        right: 10px;
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
        right: 10px;
        background: #dc2626;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: bold;
        z-index: 2;
    }

    .save-amount {
        display: block;
        color: #16a34a;
        font-size: 0.85rem;
        font-weight: 600;
        margin-top: 2px;
    }

    .product-card {
        transition: all 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(13, 148, 136, 0.2);
    }
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>