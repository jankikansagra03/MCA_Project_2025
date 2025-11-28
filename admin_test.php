<?php
// admin_categories.php
include 'db_config.php';
include 'admin_authentication.php';
ob_start();


if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'admin@example.com';
}
$admin_email = $_SESSION['admin_email'];

// -------------------- HANDLE ACTIONS --------------------

// Deactivate category (soft delete)
if (isset($_POST['delete_category'])) {
    $id = intval($_POST['id']);

    $stmt = $con->prepare("CALL Category_Delete(?)");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // clear any results from the proc
        flush_stored_results($con);

        if ($stmt->affected_rows >= 0) {
            setcookie('success', "Category deactivated successfully", time() + 5);
        } else {
            setcookie('error', "Error deactivating category: " . $stmt->error, time() + 5);
        }
        $stmt->close();
    } else {
        setcookie('error', "Prepare failed: " . $con->error, time() + 5);
    }

    // fast redirect, stop executing rest of the page
    header("Location: admin_categories.php");
    exit;
}

// Add category
if (isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name'] ?? '');
    $status        = $_POST['status'] ?? 'Active';

    $stmt = $con->prepare("CALL Category_Insert(?, ?)");
    if ($stmt) {
        $stmt->bind_param("ss", $category_name, $status);
        $stmt->execute();

        flush_stored_results($con);

        if ($stmt->affected_rows >= 0) {
            setcookie('success', "Category added successfully", time() + 5);
        } else {
            setcookie('error', "Error adding category: " . $stmt->error, time() + 5);
        }
        $stmt->close();
    } else {
        setcookie('error', "Prepare failed: " . $con->error, time() + 5);
    }

    header("Location: admin_categories.php");
    exit;
}

// Update category (also used when clicking "Activate")
if (isset($_POST['update_category'])) {
    $id            = intval($_POST['id']);
    $category_name = trim($_POST['category_name'] ?? '');
    $status        = $_POST['status'] ?? 'Active';

    $stmt = $con->prepare("CALL Category_Update(?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $id, $category_name, $status);
        $stmt->execute();

        flush_stored_results($con);

        if ($stmt->affected_rows >= 0) {
            setcookie('success', "Category updated successfully", time() + 5);
        } else {
            setcookie('error', "Error updating category: " . $stmt->error, time() + 5);
        }
        $stmt->close();
    } else {
        setcookie('error', "Prepare failed: " . $con->error, time() + 5);
    }

    header("Location: admin_categories.php");
    exit;
}

// -------------------- FETCH & LISTING --------------------

$rows       = [];
$allRows    = [];
$search     = trim($_GET['search'] ?? '');
$page       = max(1, intval($_GET['page'] ?? 1));
$perPage    = 10;
$total      = 0;
$totalPages = 1;
$offset     = ($page - 1) * $perPage;

if ($search !== '') {
    // ensure no pending result sets from earlier calls
    flush_stored_results($con);

    $stmt = $con->prepare("CALL Category_Search(?)");
    if ($stmt) {
        $param = $search;
        $stmt->bind_param("s", $param);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $allRows[] = $r;
            }
            $res->free();
        }
        $stmt->close();
        flush_stored_results($con);
    } else {
        $allRows = [];
    }

    $total      = count($allRows);
    $totalPages = (int)ceil(max(1, $total) / $perPage);
    $offset     = ($page - 1) * $perPage;
    $rows       = array_slice($allRows, $offset, $perPage);
} else {
    // count total
    $countStmt = $con->prepare("SELECT COUNT(*) AS cnt FROM categories");
    if ($countStmt) {
        $countStmt->execute();
        $cres = $countStmt->get_result();
        if ($cres) {
            $crow  = $cres->fetch_assoc();
            $total = intval($crow['cnt'] ?? 0);
            $cres->free();
        }
        $countStmt->close();
    }

    $totalPages = (int)ceil(max(1, $total) / $perPage);
    $offset     = ($page - 1) * $perPage;

    $sel = $con->prepare("SELECT id, category_name, status FROM categories ORDER BY id DESC LIMIT ? OFFSET ?");
    if ($sel) {
        $sel->bind_param("ii", $perPage, $offset);
        $sel->execute();
        $res = $sel->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
            $res->free();
        }
        $sel->close();
    }
}

$totalPages = $totalPages ?? 1;
$offset     = $offset ?? 0;
?>

<div class="">
    <div class="mb-3">
        <h3 class="mb-0" style="color:var(--teal)">Manage Categories</h3>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <button class="btn text-white fw-semibold" style="background-color:#0d9488;"
            data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fa fa-plus me-1"></i> Add New Category
        </button>

        <form class="d-flex" method="GET" action="admin_categories.php">
            <input type="text" name="search" class="form-control me-2"
                placeholder="Search category or status"
                value="<?= htmlspecialchars($search) ?>">
            <button type="submit" name="search_btn"
                class="btn text-white fw-semibold"
                style="background-color:#0d9488;">
                Search
            </button>
        </form>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:6%;">S.No</th>
                        <th>Category Name</th>
                        <th>Status</th>
                        <th style="width:22%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No categories found.</td>
                        </tr>
                        <?php else: foreach ($rows as $i => $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($offset + $i + 1) ?></td>
                                <td><?= htmlspecialchars($c['category_name']) ?></td>
                                <td>
                                    <?php if (strtolower($c['status']) === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">

                                        <!-- View Button -->
                                        <a class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewCategoryModal<?= (int)$c['id'] ?>">
                                            <i class="fa fa-eye"></i> View
                                        </a>

                                        <!-- View Modal -->
                                        <div class="modal fade"
                                            id="viewCategoryModal<?= (int)$c['id'] ?>"
                                            tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background:#0d9488; color:white;">
                                                        <h5 class="modal-title">
                                                            <i class="fa fa-tags me-2"></i> Category Details
                                                        </h5>
                                                        <button type="button"
                                                            class="btn-close btn-close-white"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <table class="table table-borderless mb-0">
                                                            <tr>
                                                                <th style="width:35%; color:#0d9488;">ID:</th>
                                                                <td><?= (int)$c['id'] ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Name:</th>
                                                                <td><?= htmlspecialchars($c['category_name']) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Status:</th>
                                                                <td><?= htmlspecialchars($c['status']) ?></td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button class="btn text-white fw-semibold"
                                                            style="background-color:#0d9488;"
                                                            data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Edit Button -->
                                        <a class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editCategoryModal<?= (int)$c['id'] ?>">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>

                                        <!-- Edit Modal -->
                                        <div class="modal fade"
                                            id="editCategoryModal<?= (int)$c['id'] ?>"
                                            tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="admin_categories.php">
                                                        <div class="modal-header" style="background:#0d9488; color:white;">
                                                            <h5 class="modal-title">
                                                                <i class="fa fa-edit me-2"></i> Edit Category
                                                            </h5>
                                                            <button type="button"
                                                                class="btn-close btn-close-white"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id"
                                                                value="<?= (int)$c['id'] ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Category Name</label>
                                                                <input name="category_name" class="form-control"
                                                                    value="<?= htmlspecialchars($c['category_name']) ?>"
                                                                    data-validation="required">
                                                                <span class="error text-danger"
                                                                    id="categoryNameError<?= (int)$c['id'] ?>"></span>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Status</label>
                                                                <select name="status" class="form-select"
                                                                    data-validation="required">
                                                                    <option value="">-- Select Status --</option>
                                                                    <option value="Active" <?= $c['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                                                    <option value="Inactive" <?= $c['status'] !== 'Active' ? 'selected' : '' ?>>Inactive</option>
                                                                </select>
                                                                <span class="error text-danger"
                                                                    id="statusError<?= (int)$c['id'] ?>"></span>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" name="update_category"
                                                                class="btn text-white fw-semibold"
                                                                style="background:#0d9488;">
                                                                <i class="fa fa-save me-1"></i> Update
                                                            </button>
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Deactivate / Activate buttons -->
                                        <?php if (strtolower($c['status']) === 'active'): ?>
                                            <!-- Deactivate (soft delete) -->
                                            <form method="POST" action="admin_categories.php"
                                                style="display:inline-block"
                                                onsubmit="return confirm('Deactivate this category?');">
                                                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                                <button type="submit" name="delete_category"
                                                    class="btn btn-sm btn-outline-danger">
                                                    <i class="fa fa-ban"></i> Deactivate
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <!-- Activate using update_category -->
                                            <form method="POST" action="admin_categories.php"
                                                style="display:inline-block"
                                                onsubmit="return confirm('Activate this category?');">
                                                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                                <input type="hidden" name="category_name"
                                                    value="<?= htmlspecialchars($c['category_name'], ENT_QUOTES) ?>">
                                                <input type="hidden" name="status" value="Active">
                                                <button type="submit" name="update_category"
                                                    class="btn btn-sm btn-outline-success">
                                                    <i class="fa fa-check"></i> Activate
                                                </button>
                                            </form>
                                        <?php endif; ?>

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
            <ul class="pagination mb-0">
                <?php
                $qsBase = '';
                if ($search !== '') {
                    $qsBase .= '&search=' . urlencode($search);
                }

                $prevDisabled = $page <= 1 ? ' disabled' : '';
                $nextDisabled = $page >= $totalPages ? ' disabled' : '';
                $prevPage     = $page - 1;
                $nextPage     = $page + 1;
                ?>
                <li class="page-item<?= $prevDisabled ?>">
                    <a class="page-link btn text-white fw-semibold"
                        style="background-color:#0d9488;"
                        href="?page=<?= $prevPage . $qsBase ?>">« Prev</a>
                </li>

                <?php
                $range  = 3;
                $startp = max(1, $page - $range);
                $endp   = min($totalPages, $page + $range);
                for ($p = $startp; $p <= $endp; $p++):
                    $active = $p === $page ? ' active' : '';
                ?>
                    <li class="page-item<?= $active ?>">
                        <a class="page-link" href="?page=<?= $p . $qsBase ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item<?= $nextDisabled ?>">
                    <a class="page-link btn text-white fw-semibold"
                        style="background-color:#0d9488;"
                        href="?page=<?= $nextPage . $qsBase ?>">Next »</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="admin_categories.php">
                <div class="modal-header" style="background:var(--teal); color:#fff;">
                    <h5 class="modal-title"><i class="fa fa-plus me-2"></i> Add Category</h5>
                    <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input name="category_name" class="form-control"
                            data-validation="required">
                        <span class="error text-danger" id="category_nameError"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" data-validation="required">
                            <option value="Active" selected>Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                        <span class="error text-danger" id="statusError"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn text-white fw-semibold"
                        style="background-color:#0d9488;"
                        type="submit" name="add_category">
                        <i class="fa fa-save me-1"></i> Save
                    </button>
                    <button class="btn btn-danger" type="button"
                        data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content_admin = ob_get_clean();
include 'admin_layout.php';
