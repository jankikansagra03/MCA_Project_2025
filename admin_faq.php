<?php
include_once 'db_config.php';
include 'admin_authentication.php';
ob_start();

if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'admin@example.com';
}
$admin_email = $_SESSION['admin_email'];

// ADD FAQ
if (isset($_POST['add_faq'])) {
    $question = $_POST['question'];
    $answer = $_POST['answer'];
    $category = $_POST['category'];
    $display_order = $_POST['display_order'];
    $status = $_POST['status'];

    $stmt = $con->prepare("CALL FAQ_Insert(?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssis", $question, $answer, $category, $display_order, $status);
        if ($stmt->execute()) {
            setcookie('success', "FAQ added successfully", time() + 5);
        } else {
            setcookie('error', "Error adding FAQ: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_faq.php';</script>";
        exit();
    }
}

// UPDATE FAQ
if (isset($_POST['update_faq'])) {
    $id = $_POST['id'];
    $question = $_POST['question'];
    $answer = $_POST['answer'];
    $category = $_POST['category'];
    $display_order = $_POST['display_order'];
    $status = $_POST['status'];

    $stmt = $con->prepare("CALL FAQ_Update(?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isssss", $id, $question, $answer, $category, $display_order, $status);
        if ($stmt->execute()) {
            setcookie('success', "FAQ updated successfully", time() + 5);
        } else {
            setcookie('error', "Error updating FAQ: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_faq.php';</script>";
        exit();
    }
}

// DELETE FAQ
if (isset($_POST['delete_faq'])) {
    $id = $_POST['id'];

    $stmt = $con->prepare("CALL FAQ_Delete(?)");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            setcookie('success', "FAQ deleted successfully", time() + 5);
        } else {
            setcookie('error', "Error deleting FAQ: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_faq.php';</script>";
        exit();
    }
}

// SEARCH & FILTER
$rows = [];
$search = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category_filter'] ?? '');
$status_filter = trim($_GET['status_filter'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$total = 0;
$totalPages = 1;
$offset = ($page - 1) * $perPage;

// Get all FAQs with filters
if ($category_filter !== '' || $status_filter !== '') {
    $p_category = $category_filter !== '' ? $category_filter : NULL;
    $p_status = $status_filter !== '' ? $status_filter : NULL;

    $stmt = $con->prepare("CALL FAQ_Search(?, ?)");
    if ($stmt) {
        $stmt->bind_param("ss", $p_category, $p_status);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            $allRows = [];
            while ($r = $res->fetch_assoc()) {
                // Apply search filter
                if ($search !== '') {
                    if (stripos($r['question'], $search) !== false || stripos($r['answer'], $search) !== false) {
                        $allRows[] = $r;
                    }
                } else {
                    $allRows[] = $r;
                }
            }
            $res->free();

            $total = count($allRows);
            $totalPages = (int) ceil(max(1, $total) / $perPage);
            $rows = array_slice($allRows, $offset, $perPage);
        }
        $stmt->close();
        flush_stored_results($con);
    }
} else {
    $stmt = $con->prepare("CALL FAQ_Select(NULL)");
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            $allRows = [];
            while ($r = $res->fetch_assoc()) {
                // Apply search filter
                if ($search !== '') {
                    if (stripos($r['question'], $search) !== false || stripos($r['answer'], $search) !== false) {
                        $allRows[] = $r;
                    }
                } else {
                    $allRows[] = $r;
                }
            }
            $res->free();

            $total = count($allRows);
            $totalPages = (int) ceil(max(1, $total) / $perPage);
            $rows = array_slice($allRows, $offset, $perPage);
        }
        $stmt->close();
        flush_stored_results($con);
    }
}

// Get unique categories for filter
$categories = [];
$catStmt = $con->prepare("SELECT DISTINCT category FROM faq ORDER BY category");
if ($catStmt) {
    $catStmt->execute();
    $catRes = $catStmt->get_result();
    while ($cat = $catRes->fetch_assoc()) {
        $categories[] = $cat['category'];
    }
    $catRes->free();
    $catStmt->close();
}
?>

<div class="">
    <!-- Row 1: Heading -->
    <div class="mb-3">
        <h4 class="mb-0" style="color:var(--teal)">Manage FAQ</h4>
    </div>

    <!-- Row 2: Add Button + Filters + Search -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <!-- Left: Add Button -->
        <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" data-bs-toggle="modal" data-bs-target="#addFAQModal">
            <i class="fa fa-plus me-1"></i> Add FAQ
        </button>

        <!-- Right: Filters + Search -->
        <form class="d-flex gap-2" method="GET" action="admin_faq.php">
            <select name="category_filter" class="form-select" style="min-width: 150px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $category_filter == $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status_filter" class="form-select" style="min-width: 130px;">
                <option value="">All Status</option>
                <option value="Active" <?= $status_filter == 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= $status_filter == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>

            <input type="text" name="search" style="font-size:small;" class="form-control"
                placeholder="Search question or answer"
                value="<?= htmlspecialchars($search) ?>"
                style="min-width: 250px;">

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
                        <th>Question</th>
                        <th>Category</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No FAQs found.</td>
                        </tr>
                        <?php else: foreach ($rows as $i => $faq): ?>
                            <tr>
                                <td><?= htmlspecialchars($offset + $i + 1) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars(substr($faq['question'], 0, 80)) ?><?= strlen($faq['question']) > 80 ? '...' : '' ?></strong>
                                </td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($faq['category']) ?></span></td>
                                <td><span class="badge bg-secondary"><?= $faq['display_order'] ?></span></td>
                                <td>
                                    <?php if ($faq['status'] == 'Active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($faq['created_at'])) ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <!-- View -->
                                        <a class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewFAQModal<?= (int)$faq['id'] ?>">
                                            <i class="fa fa-eye"></i> View
                                        </a>

                                        <!-- View Modal -->
                                        <div class="modal fade" id="viewFAQModal<?= (int)$faq['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background:#0d9488; color:white;">
                                                        <h5 class="modal-title"><i class="fa fa-question-circle me-2"></i>FAQ Details</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <h6 style="color:#0d9488;">Question:</h6>
                                                            <p class="fw-bold"><?= htmlspecialchars($faq['question']) ?></p>
                                                        </div>

                                                        <div class="mb-3">
                                                            <h6 style="color:#0d9488;">Answer:</h6>
                                                            <p><?= nl2br(htmlspecialchars($faq['answer'])) ?></p>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <h6 style="color:#0d9488;">Category:</h6>
                                                                <p><span class="badge bg-info"><?= htmlspecialchars($faq['category']) ?></span></p>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <h6 style="color:#0d9488;">Display Order:</h6>
                                                                <p><?= $faq['display_order'] ?></p>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <h6 style="color:#0d9488;">Status:</h6>
                                                                <p>
                                                                    <?php if ($faq['status'] == 'Active'): ?>
                                                                        <span class="badge bg-success">Active</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-danger">Inactive</span>
                                                                    <?php endif; ?>
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <p class="text-muted mb-0"><small>Created: <?= date('M d, Y h:i A', strtotime($faq['created_at'])) ?></small></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Edit -->
                                        <a class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editFAQModal<?= (int)$faq['id'] ?>">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editFAQModal<?= (int)$faq['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="admin_faq.php">
                                                        <input type="hidden" name="id" value="<?= $faq['id'] ?>">

                                                        <div class="modal-header" style="background:#0d9488; color:#fff;">
                                                            <h5 class="modal-title"><i class="fa fa-edit me-2"></i>Edit FAQ</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>

                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Question</label>
                                                                <input name="question" class="form-control"
                                                                    value="<?= htmlspecialchars($faq['question']) ?>"
                                                                    data-validation="required">
                                                                <span class="error text-danger" id="questionError"></span>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Answer</label>
                                                                <textarea name="answer" class="form-control" rows="5"
                                                                    data-validation="required"><?= htmlspecialchars($faq['answer']) ?></textarea>
                                                                <span class="error text-danger" id="answerError"></span>
                                                            </div>

                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Category</label>
                                                                    <input name="category" class="form-control"
                                                                        value="<?= htmlspecialchars($faq['category']) ?>"
                                                                        data-validation="required"
                                                                        list="categoryList">
                                                                    <datalist id="categoryList">
                                                                        <?php foreach ($categories as $cat): ?>
                                                                            <option value="<?= htmlspecialchars($cat) ?>">
                                                                            <?php endforeach; ?>
                                                                    </datalist>
                                                                    <span class="error text-danger" id="categoryError"></span>
                                                                </div>

                                                                <div class="col-md-3">
                                                                    <label class="form-label">Display Order</label>
                                                                    <input name="display_order" class="form-control"
                                                                        value="<?= htmlspecialchars($faq['display_order']) ?>"
                                                                        data-validation="numeric">
                                                                </div>

                                                                <div class="col-md-3">
                                                                    <label class="form-label">Status</label>
                                                                    <select name="status" class="form-select" data-validation="required">
                                                                        <option value="Active" <?= $faq['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                                                        <option value="Inactive" <?= $faq['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="submit" name="update_faq"
                                                                class="btn text-white fw-semibold" style="background:#0d9488;">
                                                                <i class="fa fa-save me-1"></i> Update
                                                            </button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete -->
                                        <form method="POST" action="admin_faq.php"
                                            style="display:inline-block"
                                            onsubmit="return confirm('Delete this FAQ?');">
                                            <input type="hidden" name="id" value="<?= $faq['id'] ?>">
                                            <button type="submit" name="delete_faq"
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
                if ($category_filter !== '') $qsBase .= '&category_filter=' . urlencode($category_filter);
                if ($status_filter !== '') $qsBase .= '&status_filter=' . urlencode($status_filter);

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
    </div>
</div>

<!-- Add FAQ Modal -->
<div class="modal fade" id="addFAQModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="admin_faq.php">
                <div class="modal-header" style="background:var(--teal); color:#fff;">
                    <h5 class="modal-title"><i class="fa fa-plus me-2"></i>Add FAQ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Question</label>
                        <input name="question" class="form-control"
                            data-validation="required"
                            placeholder="Enter the question">
                        <span class="error text-danger" id="questionError"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Answer</label>
                        <textarea name="answer" class="form-control" rows="5"
                            data-validation="required"
                            placeholder="Enter the answer"></textarea>
                        <span class="error text-danger" id="answerError"></span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <input name="category" class="form-control"
                                value="General"
                                data-validation="required"
                                list="categoryListAdd">
                            <datalist id="categoryListAdd">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>">
                                    <?php endforeach; ?>
                                    <option value="Payment">
                                    <option value="Shipping">
                                    <option value="Returns">
                                    <option value="Warranty">
                                    <option value="General">
                            </datalist>
                            <span class="error text-danger" id="categoryError"></span>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Display Order</label>
                            <input name="display_order" class="form-control" value="0" data-validation="numeric">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" data-validation="required">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" type="submit" name="add_faq">
                        <i class="fa fa-save me-1"></i> Add FAQ
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