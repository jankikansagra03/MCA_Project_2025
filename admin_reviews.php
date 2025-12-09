<?php
include_once 'db_config.php';
include_once 'admin_authentication.php';

if (!isset($_SESSION['admin_email'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_email = $_SESSION['admin_email'];

// =======================
// DELETE REVIEW
// =======================
if (isset($_POST['delete_review'])) {
    $review_id = (int)$_POST['review_id'];

    $stmt = $con->prepare("DELETE FROM reviews WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $review_id);
        if ($stmt->execute()) {
            setcookie('success', "Review deleted successfully", time() + 5);
        } else {
            setcookie('error', "Error deleting review: " . $stmt->error, time() + 5);
        }
        echo "<script>window.location.href='admin_reviews.php';</script>";
        $stmt->close();
    }
}

// =======================
// UPDATE REVIEW STATUS
// =======================
if (isset($_POST['update_status'])) {
    $review_id = (int)$_POST['review_id'];
    $status = trim($_POST['status']);

    $stmt = $con->prepare("UPDATE reviews SET status = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $status, $review_id);

        if ($stmt->execute()) {
            setcookie('success', "Review status updated successfully", time() + 5);
        } else {
            setcookie('error', "Error updating review: " . $stmt->error, time() + 5);
        }
        echo "<script>window.location.href='admin_reviews.php';</script>";
        $stmt->close();
    }
}

// =======================
// FETCH REVIEWS
// =======================
$rows = [];
$allRows = [];
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$total = 0;
$totalPages = 1;
$offset = ($page - 1) * $perPage;

// Build query
$where_conditions = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    $where_conditions[] = "(r.title LIKE ? OR r.review LIKE ? OR r.user_name LIKE ? OR p.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

if ($status_filter !== '') {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_conditions);

// Count total
$count_query = "SELECT COUNT(*) as total 
                FROM reviews r 
                LEFT JOIN products p ON r.product_id = p.id 
                WHERE $where_sql";
$count_stmt = $con->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total / $perPage);
$count_result->free();
$count_stmt->close();

// Fetch reviews
$query = "SELECT r.*, p.name as product_name, p.image as product_image
          FROM reviews r
          LEFT JOIN products p ON r.product_id = p.id
          WHERE $where_sql
          ORDER BY r.created_at DESC
          LIMIT ? OFFSET ?";

$stmt = $con->prepare($query);
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
}
$stmt->close();

$totalPages = $total_pages ?? 1;

ob_start();
?>

<div class="">
    <!-- Heading -->
    <div class="mb-3">
        <h4 class="mb-0" style="color:var(--teal)">Manage Reviews</h4>
    </div>

    <!-- Search & Filter -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="admin_dashboard.php" class="btn btn-outline-secondary" style="font-size:small;">
            <i class="fa fa-arrow-left me-1"></i> Back
        </a>
        <form class="d-flex gap-2" method="GET" action="admin_reviews.php">
            <input type="text" name="search" style="font-size:small;" class="form-control"
                placeholder="Search by product, user, review..."
                value="<?= htmlspecialchars($search) ?>" style="min-width: 300px; font-size:small">
            <select name="status" class="form-select" style="max-width: 150px; font-size:small;">
                <option value="">All Status</option>
                <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Approved" <?= $status_filter == 'Approved' ? 'selected' : '' ?>>Approved</option>
                <option value="Rejected" <?= $status_filter == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
            <button type="submit" class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;">
                Search
            </button>
        </form>
    </div>

    <!-- Reviews Table -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:5%;">S.No</th>
                        <th>Product</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Review Title</th>
                        <th>Status</th>
                        <th style="width:8%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No reviews found.</td>
                        </tr>
                        <?php else: foreach ($rows as $i => $review): ?>
                            <!-- Table Row -->
                            <tr class="review-row" style="cursor: pointer;"
                                data-bs-toggle="collapse"
                                data-bs-target="#reviewDetails<?= $review['id'] ?>">
                                <td><?= htmlspecialchars($offset + $i + 1) ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="images/products/<?= htmlspecialchars($review['product_image']) ?>"
                                            alt="Product"
                                            style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;"
                                            onerror="this.src='images/products/default.jpg'">
                                        <span><?= htmlspecialchars($review['product_name']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($review['user_name']) ?></td>
                                <td>
                                    <div class="text-warning">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <i class="fa<?= $s <= $review['rating'] ? 's' : 'r' ?> fa-star"></i>
                                        <?php endfor; ?>
                                        <span class="text-muted small">(<?= $review['rating'] ?>/5)</span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($review['title']) ?></td>
                                <td>
                                    <span class="badge bg-<?=
                                                            $review['status'] == 'Approved' ? 'success' : ($review['status'] == 'Rejected' ? 'danger' : 'warning')
                                                            ?>">
                                        <?= htmlspecialchars($review['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <i class="fa fa-chevron-down text-muted"></i>
                                </td>
                            </tr>

                            <!-- Accordion Details Row -->
                            <tr class="collapse-row">
                                <td colspan="7" class="p-0 border-0">
                                    <div class="collapse" id="reviewDetails<?= $review['id'] ?>">
                                        <div class="card card-body border-0" style="background-color:#f9fafb;">

                                            <div class="row mb-3">
                                                <!-- Review Info -->
                                                <div class="col-md-8">
                                                    <div class="card border-0 h-100" style="background-color:#ffffff;">
                                                        <div class="card-body">
                                                            <h6 class="fw-bold mb-3" style="color:#0d9488;">
                                                                <i class="fa fa-comment me-2"></i>Review Details
                                                            </h6>

                                                            <div class="mb-3">
                                                                <strong>Title:</strong>
                                                                <p class="mb-2"><?= htmlspecialchars($review['title']) ?></p>
                                                            </div>

                                                            <div class="mb-3">
                                                                <strong>Review:</strong>
                                                                <p class="mb-2"><?= nl2br(htmlspecialchars($review['review'])) ?></p>
                                                            </div>

                                                            <div class="mb-2">
                                                                <strong>Rating:</strong>
                                                                <div class="text-warning">
                                                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                                                        <i class="fa<?= $s <= $review['rating'] ? 's' : 'r' ?> fa-star"></i>
                                                                    <?php endfor; ?>
                                                                    <span class="text-muted">(<?= $review['rating'] ?>/5)</span>
                                                                </div>
                                                            </div>

                                                            <div class="mb-0">
                                                                <strong>Submitted:</strong>
                                                                <?= date('d M Y, h:i A', strtotime($review['created_at'])) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- User & Product Info -->
                                                <div class="col-md-4">
                                                    <div class="card border-0 mb-3" style="background-color:#fef3c7;">
                                                        <div class="card-body">
                                                            <h6 class="fw-bold mb-2" style="color:#d4af37;">
                                                                <i class="fa fa-user me-2"></i>User Info
                                                            </h6>
                                                            <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($review['user_name']) ?></p>
                                                            <p class="mb-0"><strong>Email:</strong> <?= htmlspecialchars($review['user_email']) ?></p>
                                                        </div>
                                                    </div>

                                                    <div class="card border-0" style="background-color:#e0f2fe;">
                                                        <div class="card-body">
                                                            <h6 class="fw-bold mb-2" style="color:#0369a1;">
                                                                <i class="fa fa-box me-2"></i>Product
                                                            </h6>
                                                            <img src="images/products/<?= htmlspecialchars($review['product_image']) ?>"
                                                                class="img-fluid rounded mb-2"
                                                                alt="Product"
                                                                onerror="this.src='images/products/default.jpg'">
                                                            <p class="mb-0"><?= htmlspecialchars($review['product_name']) ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="d-flex gap-2 justify-content-end">
                                                <?php if ($review['status'] != 'Approved'): ?>
                                                    <form method="POST" style="display:inline-block;">
                                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                        <input type="hidden" name="status" value="Approved">
                                                        <button type="submit" name="update_status" class="btn btn-sm btn-success">
                                                            <i class="fa fa-check me-1"></i> Approve
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ($review['status'] != 'Rejected'): ?>
                                                    <form method="POST" style="display:inline-block;">
                                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                        <input type="hidden" name="status" value="Rejected">
                                                        <button type="submit" name="update_status" class="btn btn-sm btn-warning">
                                                            <i class="fa fa-times me-1"></i> Reject
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <form method="POST" style="display:inline-block"
                                                    onsubmit="return confirm('Delete this review permanently?');">
                                                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                                    <button type="submit" name="delete_review" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-trash me-1"></i> Delete
                                                    </button>
                                                </form>
                                            </div>

                                        </div>
                                    </div>
                                </td>
                            </tr>

                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-3" aria-label="Pagination">
                <ul class="pagination mb-0">
                    <?php
                    $qsBase = '';
                    if ($search !== '') $qsBase .= '&search=' . urlencode($search);
                    if ($status_filter !== '') $qsBase .= '&status=' . urlencode($status_filter);

                    $prevDisabled = $page <= 1 ? ' disabled' : '';
                    $nextDisabled = $page >= $totalPages ? ' disabled' : '';
                    $prevPage = $page - 1;
                    $nextPage = $page + 1;
                    ?>
                    <li class="page-item<?= $prevDisabled ?>">
                        <a class="page-link btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;"
                            href="?page=<?= $prevPage . $qsBase ?>">« Prev</a>
                    </li>
                    <?php
                    $range = 3;
                    $startp = max(1, $page - $range);
                    $endp = min($totalPages, $page + $range);
                    for ($p = $startp; $p <= $endp; $p++):
                        $active = $p === $page ? ' active' : '';
                    ?>
                        <li class="page-item<?= $active ?>">
                            <a class="page-link" href="?page=<?= $p . $qsBase ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item<?= $nextDisabled ?>">
                        <a class="page-link btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;"
                            href="?page=<?= $nextPage . $qsBase ?>">Next »</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<style>
    .review-row:hover {
        background-color: #f0fdfa;
    }

    .collapse-row td {
        transition: all 0.3s ease;
    }

    .review-row[aria-expanded="true"] .fa-chevron-down {
        transform: rotate(180deg);
        transition: transform 0.3s ease;
    }

    .fa-chevron-down {
        transition: transform 0.3s ease;
    }
</style>

<?php
$content_admin = ob_get_clean();
include 'admin_layout.php';
?>