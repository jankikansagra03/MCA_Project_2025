<?php
include_once 'db_config.php';
include 'admin_authentication.php';
ob_start();

if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'admin@example.com';
}
$admin_email = $_SESSION['admin_email'];

// ADD ITEM TO WISHLIST
if (isset($_POST['add_wishlist_item'])) {
    $user_email = $_POST['user_email'];
    $product_id = $_POST['product_id'];

    $stmt = $con->prepare("CALL Wishlist_Insert(?, ?)");
    if ($stmt) {
        $stmt->bind_param("si", $user_email, $product_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row['rows_affected'] > 0) {
                setcookie('success', "Item added to wishlist successfully", time() + 5);
            } else {
                setcookie('error', "Item already exists in wishlist", time() + 5);
            }
        } else {
            setcookie('error', "Error adding item to wishlist: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_wishlist.php';</script>";
        exit();
    }
}

// DELETE SINGLE WISHLIST ITEM
if (isset($_POST['delete_wishlist_item'])) {
    $wishlist_id = $_POST['wishlist_id'];

    $stmt = $con->prepare("CALL Wishlist_Delete(?)");
    if ($stmt) {
        $stmt->bind_param("i", $wishlist_id);
        if ($stmt->execute()) {
            setcookie('success', "Wishlist item deleted successfully", time() + 5);
        } else {
            setcookie('error', "Error deleting wishlist item: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_wishlist.php';</script>";
        exit();
    }
}

// EMPTY USER'S ENTIRE WISHLIST
if (isset($_POST['empty_wishlist'])) {
    $user_email = $_POST['user_email'];

    $stmt = $con->prepare("CALL Wishlist_Empty(?)");
    if ($stmt) {
        $stmt->bind_param("s", $user_email);
        if ($stmt->execute()) {
            setcookie('success', "Wishlist emptied successfully for user: " . htmlspecialchars($user_email), time() + 5);
        } else {
            setcookie('error', "Error emptying wishlist: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_wishlist.php';</script>";
        exit();
    }
}

// FETCH ALL USERS FOR DROPDOWN
$users = [];
$userStmt = $con->prepare("SELECT email, fullname FROM registration WHERE status='Active' ORDER BY fullname");
if ($userStmt) {
    $userStmt->execute();
    $userRes = $userStmt->get_result();
    while ($user = $userRes->fetch_assoc()) {
        $users[] = $user;
    }
    $userRes->free();
    $userStmt->close();
}

// FETCH ALL ACTIVE PRODUCTS FOR DROPDOWN
$products = [];
$prodStmt = $con->prepare("CALL Products_Select(NULL)");
if ($prodStmt) {
    $prodStmt->execute();
    $prodRes = $prodStmt->get_result();
    while ($prod = $prodRes->fetch_assoc()) {
        if ($prod['status'] == 'Active') {
            $products[] = $prod;
        }
    }
    $prodRes->free();
    $prodStmt->close();
    flush_stored_results($con);
}

// SEARCH & PAGINATION
$rows = [];
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$total = 0;
$totalPages = 1;
$offset = ($page - 1) * $perPage;

// Get all wishlist items with user and product details
if ($search !== '') {
    // Search by user email, name or product name
    $stmt = $con->prepare("
        SELECT 
            w.id as wishlist_id,
            w.user_email,
            w.product_id,
            w.added_at,
            p.name as product_name,
            p.price,
            p.image,
            p.final_price,
            p.status as product_status,
            r.fullname as user_fullname
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        LEFT JOIN registration r ON w.user_email = r.email
        WHERE w.user_email LIKE CONCAT('%', ?, '%')
            OR p.name LIKE CONCAT('%', ?, '%')
            OR r.fullname LIKE CONCAT('%', ?, '%')
        ORDER BY w.added_at DESC
    ");

    if ($stmt) {
        $stmt->bind_param("sss", $search, $search, $search);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            $allRows = [];
            while ($r = $res->fetch_assoc()) {
                $allRows[] = $r;
            }
            $res->free();

            $total = count($allRows);
            $totalPages = (int) ceil(max(1, $total) / $perPage);
            $rows = array_slice($allRows, $offset, $perPage);
        }
        $stmt->close();
    }
} else {
    // Get all wishlist items from all users
    $stmt = $con->prepare("
        SELECT 
            w.id as wishlist_id,
            w.user_email,
            w.product_id,
            w.added_at,
            p.name as product_name,
            p.price,
            p.image,
            p.final_price,
            p.status as product_status,
            r.fullname as user_fullname
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        LEFT JOIN registration r ON w.user_email = r.email
        ORDER BY w.added_at DESC
    ");

    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            $allRows = [];
            while ($r = $res->fetch_assoc()) {
                $allRows[] = $r;
            }
            $res->free();

            $total = count($allRows);
            $totalPages = (int) ceil(max(1, $total) / $perPage);
            $rows = array_slice($allRows, $offset, $perPage);
        }
        $stmt->close();
    }
}
?>

<div class="">
    <!-- Row 1: Heading only -->
    <div class="mb-3">
        <h4 class="mb-0" style="color:var(--teal)">Manage Wishlist Items</h4>
    </div>

    <!-- Row 2: Add New Item + Search -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <!-- Left: Add Item to Wishlist -->
        <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" data-bs-toggle="modal" data-bs-target="#addWishlistModal">
            <i class="fa fa-plus me-1"></i> Add Item to Wishlist
        </button>

        <!-- Right: Search -->
        <form class="d-flex gap-2" method="GET" action="admin_wishlist.php">
            <input type="text" name="search" style="font-size:small;" class="form-control"
                placeholder="Search by user name, email or product"
                value="<?= htmlspecialchars($search) ?>"
                style="min-width: 300px; font-size:small">

            <button type="submit" class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;">
                Search
            </button>
        </form>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:5%;">S.No</th>
                        <th style="width:10%;">Image</th>
                        <th>User</th>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Added At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No wishlist items found.</td>
                        </tr>
                        <?php else:
                        $currentUser = '';
                        foreach ($rows as $i => $w):
                        ?>
                            <?php if ($currentUser !== $w['user_email']):
                                $currentUser = $w['user_email'];
                            ?>
                                <tr style="background-color: #f0f9ff;">
                                    <td colspan="8">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong style="color:#0d9488;">
                                                <i class="fa fa-user"></i>
                                                <?php if (!empty($w['user_fullname'])): ?>
                                                    <?= htmlspecialchars($w['user_fullname']) ?>
                                                    <span style="font-weight: normal; color: #666;">
                                                        (<?= htmlspecialchars($w['user_email']) ?>)
                                                    </span>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($w['user_email']) ?>
                                                <?php endif; ?>
                                            </strong>
                                            <form method="POST" action="admin_wishlist.php"
                                                style="display:inline-block"
                                                onsubmit="return confirm('Empty entire wishlist for this user?');">
                                                <input type="hidden" name="user_email" value="<?= htmlspecialchars($w['user_email']) ?>">
                                                <button type="submit" name="empty_wishlist"
                                                    class="btn btn-sm btn-outline-danger">
                                                    <i class="fa fa-trash"></i> Empty Wishlist
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <tr>
                                <td><?= htmlspecialchars($offset + $i + 1) ?></td>
                                <td>
                                    <img src="<?= htmlspecialchars($w['image'] ?? 'images/products/default_product.jpg') ?>"
                                        class="img-thumbnail" alt="" style="width:60px; height:60px; object-fit:cover;">
                                </td>
                                <td>
                                    <?php if (!empty($w['user_fullname'])): ?>
                                        <strong><?= htmlspecialchars($w['user_fullname']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($w['user_email']) ?></small>
                                    <?php else: ?>
                                        <?= htmlspecialchars($w['user_email']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($w['product_name']) ?></td>
                                <td>
                                    <strong>$<?= number_format($w['final_price'], 2) ?></strong>
                                    <?php if ($w['price'] != $w['final_price']): ?>
                                        <br><small class="text-muted"><s>$<?= number_format($w['price'], 2) ?></s></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($w['product_status'] == 'Active'): ?>
                                        <span class="badge bg-success">Available</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Unavailable</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y h:i A', strtotime($w['added_at'])) ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <!-- View -->
                                        <a class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewWishlistModal<?= (int)$w['wishlist_id'] ?>">
                                            <i class="fa fa-eye"></i> View
                                        </a>

                                        <!-- View Wishlist Item Modal -->
                                        <div class="modal fade" id="viewWishlistModal<?= (int)$w['wishlist_id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background:#0d9488; color:white;">
                                                        <h5 class="modal-title">
                                                            <i class="fa fa-heart me-2"></i> Wishlist Item Details
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <div class="text-center mb-3">
                                                            <img src="<?= htmlspecialchars($w['image'] ?? 'images/products/default_product.jpg') ?>"
                                                                class="img-fluid rounded border"
                                                                style="max-height: 200px; object-fit: cover;">
                                                        </div>

                                                        <table class="table table-borderless">
                                                            <tr>
                                                                <th style="width:40%; color:#0d9488;">Wishlist ID:</th>
                                                                <td><?= htmlspecialchars($w['wishlist_id']) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">User:</th>
                                                                <td>
                                                                    <?php if (!empty($w['user_fullname'])): ?>
                                                                        <strong><?= htmlspecialchars($w['user_fullname']) ?></strong><br>
                                                                        <small><?= htmlspecialchars($w['user_email']) ?></small>
                                                                    <?php else: ?>
                                                                        <?= htmlspecialchars($w['user_email']) ?>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Product:</th>
                                                                <td><?= htmlspecialchars($w['product_name']) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Product ID:</th>
                                                                <td><?= htmlspecialchars($w['product_id']) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Original Price:</th>
                                                                <td>$<?= number_format($w['price'], 2) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Final Price:</th>
                                                                <td><strong class="text-success">$<?= number_format($w['final_price'], 2) ?></strong></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Product Status:</th>
                                                                <td>
                                                                    <?php if ($w['product_status'] == 'Active'): ?>
                                                                        <span class="badge bg-success">Available</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-danger">Unavailable</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Added At:</th>
                                                                <td><?= date('M d, Y h:i A', strtotime($w['added_at'])) ?></td>
                                                            </tr>
                                                        </table>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete Item -->
                                        <form method="POST" action="admin_wishlist.php"
                                            style="display:inline-block"
                                            onsubmit="return confirm('Delete this wishlist item?');">
                                            <input type="hidden" name="wishlist_id" value="<?= htmlspecialchars($w['wishlist_id']) ?>">
                                            <button type="submit" name="delete_wishlist_item"
                                                class="btn btn-sm btn-outline-danger">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav class="mt-3" aria-label="Pagination">
            <ul class="pagination mb-0 justify-content-center">
                <?php
                $qsBase = '';
                if ($search !== '') $qsBase .= '&search=' . urlencode($search);

                $prevDisabled = $page <= 1 ? 'disabled' : '';
                $nextDisabled = $page >= $totalPages ? 'disabled' : '';
                $prevPage = $page - 1;
                $nextPage = $page + 1;
                ?>

                <li class="page-item prev-next <?= $prevDisabled ?>">
                    <a class="page-link" href="?page=<?= $prevPage ?>">
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
                        <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item prev-next <?= $nextDisabled ?>">
                    <a class="page-link" href="?page=<?= $nextPage ?>">
                        Next <i class="fa fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<!-- Add Wishlist Item Modal -->
<div class="modal fade" id="addWishlistModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="admin_wishlist.php">
                <div class="modal-header" style="background:var(--teal); color:#fff;">
                    <h5 class="modal-title"><i class="fa fa-plus me-2"></i> Add Item to Wishlist</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select User</label>
                        <select name="user_email" class="form-select" data-validation="required">
                            <option value="">Choose User</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= htmlspecialchars($user['email']) ?>">
                                    <?= htmlspecialchars($user['fullname']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="error text-danger" id="user_emailError"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Product</label>
                        <select name="product_id" class="form-select" data-validation="required">
                            <option value="">Choose Product</option>
                            <?php foreach ($products as $prod): ?>
                                <option value="<?= $prod['id'] ?>">
                                    <?= htmlspecialchars($prod['name']) ?> - $<?= number_format($prod['final_price'], 2) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="error text-danger" id="product_idError"></span>
                    </div>

                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> <strong>Note:</strong> If the product is already in the user's wishlist, it won't be added again.
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" type="submit" name="add_wishlist_item">
                        <i class="fa fa-heart me-1"></i> Add to Wishlist
                    </button>
                    <button class="btn btn-danger" type="button" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content_admin = ob_get_clean();
include 'admin_layout.php';
?>