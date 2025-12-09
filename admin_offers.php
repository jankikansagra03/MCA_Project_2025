<?php
include_once 'db_config.php';
include 'admin_authentication.php';
ob_start();

if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'admin@example.com';
}
$admin_email = $_SESSION['admin_email'];

// ADD OFFER
if (isset($_POST['add_offer'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount_type = $_POST['discount_type'];
    $discount_value = $_POST['discount_value'];
    $min_order_amount = !empty($_POST['min_order_amount']) ? $_POST['min_order_amount'] : NULL;
    $max_applicable_amount = !empty($_POST['max_applicable_amount']) ? $_POST['max_applicable_amount'] : NULL;
    $max_discount_amount = !empty($_POST['max_discount_amount']) ? $_POST['max_discount_amount'] : NULL;
    $valid_from = !empty($_POST['valid_from']) ? $_POST['valid_from'] : NULL;
    $valid_to = !empty($_POST['valid_to']) ? $_POST['valid_to'] : NULL;
    $usage_limit = !empty($_POST['usage_limit']) ? $_POST['usage_limit'] : NULL;
    $per_user_limit = !empty($_POST['per_user_limit']) ? $_POST['per_user_limit'] : NULL;
    $status = $_POST['status'];
    $description = $_POST['description'];

    $stmt = $con->prepare("CALL Offers_Insert(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param(
            "ssddddssiiss",
            $code,
            $discount_type,
            $discount_value,
            $min_order_amount,
            $max_applicable_amount,
            $max_discount_amount,
            $valid_from,
            $valid_to,
            $usage_limit,
            $per_user_limit,
            $status,
            $description
        );
        if ($stmt->execute()) {
            setcookie('success', "Offer added successfully", time() + 5);
        } else {
            setcookie('error', "Error adding offer: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_offers.php';</script>";
        exit();
    }
}

// UPDATE OFFER
if (isset($_POST['update_offer'])) {
    $id = $_POST['id'];
    $code = strtoupper(trim($_POST['code']));
    $discount_type = $_POST['discount_type'];
    $discount_value = $_POST['discount_value'];
    $min_order_amount = !empty($_POST['min_order_amount']) ? $_POST['min_order_amount'] : NULL;
    $max_applicable_amount = !empty($_POST['max_applicable_amount']) ? $_POST['max_applicable_amount'] : NULL;
    $max_discount_amount = !empty($_POST['max_discount_amount']) ? $_POST['max_discount_amount'] : NULL;
    $valid_from = !empty($_POST['valid_from']) ? $_POST['valid_from'] : NULL;
    $valid_to = !empty($_POST['valid_to']) ? $_POST['valid_to'] : NULL;
    $usage_limit = !empty($_POST['usage_limit']) ? $_POST['usage_limit'] : NULL;
    $per_user_limit = !empty($_POST['per_user_limit']) ? $_POST['per_user_limit'] : NULL;
    $status = $_POST['status'];
    $description = $_POST['description'];

    $stmt = $con->prepare("CALL Offers_Update(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param(
            "issddddssiiss",
            $id,
            $code,
            $discount_type,
            $discount_value,
            $min_order_amount,
            $max_applicable_amount,
            $max_discount_amount,
            $valid_from,
            $valid_to,
            $usage_limit,
            $per_user_limit,
            $status,
            $description
        );
        if ($stmt->execute()) {
            setcookie('success', "Offer updated successfully", time() + 5);
        } else {
            setcookie('error', "Error updating offer: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_offers.php';</script>";
        exit();
    }
}

// DELETE OFFER (Deactivate)
if (isset($_POST['delete_offer'])) {
    $id = $_POST['id'];

    $stmt = $con->prepare("CALL Offers_Delete(?)");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            setcookie('success', "Offer deactivated successfully", time() + 5);
        } else {
            setcookie('error', "Error deactivating offer: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_offers.php';</script>";
        exit();
    }
}

// ACTIVATE OFFER
if (isset($_POST['activate_offer'])) {
    $id = $_POST['id'];

    // Use Offers_Update with only status change
    $stmt = $con->prepare("CALL Offers_Update(?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL)");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            setcookie('success', "Offer activated successfully", time() + 5);
        } else {
            setcookie('error', "Error activating offer: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_offers.php';</script>";
        exit();
    }
}

// SEARCH & FILTER
$rows = [];
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status_filter'] ?? '');
$type_filter = trim($_GET['type_filter'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$total = 0;
$totalPages = 1;
$offset = ($page - 1) * $perPage;

// Use search stored procedure if filters applied
if ($search !== '' || $status_filter !== '' || $type_filter !== '') {
    $p_id = NULL;
    $p_code = $search !== '' ? $search : NULL;
    $p_discount_type = $type_filter !== '' ? $type_filter : NULL;
    $p_status = $status_filter !== '' ? $status_filter : NULL;

    $stmt = $con->prepare("CALL Offers_Search(?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $p_id, $p_code, $p_discount_type, $p_status);
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
        flush_stored_results($con);
    }
} else {
    // Get all offers
    $stmt = $con->prepare("CALL Offers_Select(NULL)");
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
        flush_stored_results($con);
    }
}
?>

<div class="">
    <!-- Row 1: Heading only -->
    <div class="mb-3">
        <h4 class="mb-0" style="color:var(--teal)">Manage Offers & Coupons</h4>
    </div>

    <!-- Row 2: Add New Offer + Search -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <!-- Left: Add New Offer -->
        <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" data-bs-toggle="modal" data-bs-target="#addOfferModal">
            <i class="fa fa-plus me-1"></i> Add New Offer
        </button>

        <!-- Right: Filters + Search -->
        <form class="d-flex gap-2" method="GET" action="admin_offers.php">
            <select name="type_filter" class="form-select" style="min-width: 130px; font-size:small;">
                <option value="">All Types</option>
                <option value="percent" <?= $type_filter == 'percent' ? 'selected' : '' ?>>Percent (%)</option>
                <option value="fixed" <?= $type_filter == 'fixed' ? 'selected' : '' ?>>Fixed ($)</option>
            </select>

            <select name="status_filter" class="form-select" style="min-width: 130px; font-size:small;">
                <option value="">All Status</option>
                <option value="Active" <?= $status_filter == 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= $status_filter == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>

            <input type="text" name="search" style="font-size:small;" class="form-control"
                placeholder="Search by code"
                value="<?= htmlspecialchars($search) ?>"
                style="min-width: 200px;">

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
                        <th>Code</th>
                        <th>Type</th>
                        <th>Discount</th>
                        <th>Min Order</th>
                        <th>Valid Period</th>
                        <th>Usage Limit</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No offers found.</td>
                        </tr>
                        <?php else: foreach ($rows as $i => $offer):
                            // Check if offer is expired
                            $isExpired = !empty($offer['valid_to']) && strtotime($offer['valid_to']) < time();
                            $isNotStarted = !empty($offer['valid_from']) && strtotime($offer['valid_from']) > time();
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($offset + $i + 1) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($offer['code']) ?></strong>
                                    <?php if ($isExpired): ?>
                                        <br><small class="text-danger"><i class="fa fa-clock"></i> Expired</small>
                                    <?php elseif ($isNotStarted): ?>
                                        <br><small class="text-warning"><i class="fa fa-clock"></i> Not Started</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($offer['discount_type'] == 'percent'): ?>
                                        <span class="badge bg-info">Percent %</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Fixed $</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong>
                                        <?php if ($offer['discount_type'] == 'percent'): ?>
                                            <?= number_format($offer['discount_value'], 0) ?>%
                                        <?php else: ?>
                                            $<?= number_format($offer['discount_value'], 2) ?>
                                        <?php endif; ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php if ($offer['min_order_amount']): ?>
                                        $<?= number_format($offer['min_order_amount'], 2) ?>
                                    <?php else: ?>
                                        <small class="text-muted">No min</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($offer['valid_from'] || $offer['valid_to']): ?>
                                        <small>
                                            <?= $offer['valid_from'] ? date('M d, Y', strtotime($offer['valid_from'])) : 'Always' ?>
                                            <br>to<br>
                                            <?= $offer['valid_to'] ? date('M d, Y', strtotime($offer['valid_to'])) : 'Forever' ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">Anytime</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($offer['usage_limit']): ?>
                                        <?= $offer['usage_limit'] ?> total
                                        <?php if ($offer['per_user_limit']): ?>
                                            <br><small><?= $offer['per_user_limit'] ?> per user</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <small class="text-muted">Unlimited</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($offer['status'] == 'Active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <!-- View -->
                                        <a class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewOfferModal<?= (int)$offer['id'] ?>">
                                            <i class="fa fa-eye"></i> View
                                        </a>

                                        <!-- View Offer Modal -->
                                        <div class="modal fade" id="viewOfferModal<?= (int)$offer['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background:#0d9488; color:white;">
                                                        <h5 class="modal-title">
                                                            <i class="fa fa-tag me-2"></i> Offer Details
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <table class="table table-borderless">
                                                                    <tr>
                                                                        <th style="width:50%; color:#0d9488;">Offer ID:</th>
                                                                        <td><?= htmlspecialchars($offer['id']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Code:</th>
                                                                        <td><strong><?= htmlspecialchars($offer['code']) ?></strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Type:</th>
                                                                        <td><?= ucfirst($offer['discount_type']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Discount Value:</th>
                                                                        <td>
                                                                            <strong>
                                                                                <?php if ($offer['discount_type'] == 'percent'): ?>
                                                                                    <?= number_format($offer['discount_value'], 0) ?>%
                                                                                <?php else: ?>
                                                                                    $<?= number_format($offer['discount_value'], 2) ?>
                                                                                <?php endif; ?>
                                                                            </strong>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Min Order:</th>
                                                                        <td><?= $offer['min_order_amount'] ? '$' . number_format($offer['min_order_amount'], 2) : 'No minimum' ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Max Applicable:</th>
                                                                        <td><?= $offer['max_applicable_amount'] ? '$' . number_format($offer['max_applicable_amount'], 2) : 'No limit' ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <table class="table table-borderless">
                                                                    <tr>
                                                                        <th style="width:50%; color:#0d9488;">Max Discount:</th>
                                                                        <td><?= $offer['max_discount_amount'] ? '$' . number_format($offer['max_discount_amount'], 2) : 'No limit' ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Valid From:</th>
                                                                        <td><?= $offer['valid_from'] ? date('M d, Y h:i A', strtotime($offer['valid_from'])) : 'Always' ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Valid To:</th>
                                                                        <td><?= $offer['valid_to'] ? date('M d, Y h:i A', strtotime($offer['valid_to'])) : 'Forever' ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Usage Limit:</th>
                                                                        <td><?= $offer['usage_limit'] ?? 'Unlimited' ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Per User Limit:</th>
                                                                        <td><?= $offer['per_user_limit'] ?? 'Unlimited' ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Status:</th>
                                                                        <td>
                                                                            <?php if ($offer['status'] == 'Active'): ?>
                                                                                <span class="badge bg-success">Active</span>
                                                                            <?php else: ?>
                                                                                <span class="badge bg-danger">Inactive</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        </div>

                                                        <?php if (!empty($offer['description'])): ?>
                                                            <hr>
                                                            <div>
                                                                <h6 style="color:#0d9488;">Description:</h6>
                                                                <p><?= nl2br(htmlspecialchars($offer['description'])) ?></p>
                                                            </div>
                                                        <?php endif; ?>
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
                                            data-bs-target="#editOfferModal<?= (int)$offer['id'] ?>">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>

                                        <!-- Edit Offer Modal -->
                                        <div class="modal fade" id="editOfferModal<?= (int)$offer['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                                <div class="modal-content" style="max-height: 90vh;">
                                                    <form method="POST" action="admin_offers.php">
                                                        <input type="hidden" name="id" value="<?= $offer['id'] ?>">

                                                        <div class="modal-header" style="background:#0d9488; color:#fff;">
                                                            <h5 class="modal-title"><i class="fa fa-edit me-2"></i> Edit Offer</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>

                                                        <div class="modal-body" style="max-height: 65vh; overflow-y: auto;">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Coupon Code</label>
                                                                    <input name="code" class="form-control"
                                                                        value="<?= htmlspecialchars($offer['code']) ?>"
                                                                        data-validation="required" style="text-transform: uppercase;">
                                                                    <span class="error text-danger" id="codeError"></span>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Discount Type</label>
                                                                    <select name="discount_type" class="form-select" data-validation="required">
                                                                        <option value="">Select Type</option>
                                                                        <option value="percent" <?= $offer['discount_type'] == 'percent' ? 'selected' : '' ?>>Percent (%)</option>
                                                                        <option value="fixed" <?= $offer['discount_type'] == 'fixed' ? 'selected' : '' ?>>Fixed Amount ($)</option>
                                                                    </select>
                                                                    <span class="error text-danger" id="discount_typeError"></span>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Discount Value</label>
                                                                    <input name="discount_value" class="form-control"
                                                                        value="<?= htmlspecialchars($offer['discount_value']) ?>"
                                                                        data-validation="required numeric">
                                                                    <span class="error text-danger" id="discount_valueError"></span>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Min Order Amount (Optional)</label>
                                                                    <input name="min_order_amount" class="form-control"
                                                                        value="<?= htmlspecialchars($offer['min_order_amount'] ?? '') ?>"
                                                                        data-validation="numeric" placeholder="Leave empty for no minimum">
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Max Applicable Amount (Optional)</label>
                                                                    <input name="max_applicable_amount" class="form-control"
                                                                        value="<?= htmlspecialchars($offer['max_applicable_amount'] ?? '') ?>"
                                                                        data-validation="numeric" placeholder="Leave empty for no limit">
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Max Discount Amount (Optional)</label>
                                                                    <input name="max_discount_amount" class="form-control"
                                                                        value="<?= htmlspecialchars($offer['max_discount_amount'] ?? '') ?>"
                                                                        data-validation="numeric" placeholder="Leave empty for no limit">
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Valid From (Optional)</label>
                                                                    <input type="datetime-local" name="valid_from" class="form-control"
                                                                        value="<?= $offer['valid_from'] ? date('Y-m-d\TH:i', strtotime($offer['valid_from'])) : '' ?>">
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Valid To (Optional)</label>
                                                                    <input type="datetime-local" name="valid_to" class="form-control"
                                                                        value="<?= $offer['valid_to'] ? date('Y-m-d\TH:i', strtotime($offer['valid_to'])) : '' ?>">
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Usage Limit (Optional)</label>
                                                                    <input name="usage_limit" class="form-control"
                                                                        value="<?= htmlspecialchars($offer['usage_limit'] ?? '') ?>"
                                                                        data-validation="numeric" placeholder="Leave empty for unlimited">
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Per User Limit (Optional)</label>
                                                                    <input name="per_user_limit" class="form-control"
                                                                        value="<?= htmlspecialchars($offer['per_user_limit'] ?? '') ?>"
                                                                        data-validation="numeric" placeholder="Leave empty for unlimited">
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Status</label>
                                                                    <select name="status" class="form-select" data-validation="required">
                                                                        <option value="">Select Status</option>
                                                                        <option value="Active" <?= $offer['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                                                        <option value="Inactive" <?= $offer['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                                                    </select>
                                                                    <span class="error text-danger" id="statusError"></span>
                                                                </div>

                                                                <div class="col-12">
                                                                    <label class="form-label">Description (Optional)</label>
                                                                    <textarea name="description" class="form-control" rows="3"
                                                                        placeholder="Internal notes about this offer"><?= htmlspecialchars($offer['description'] ?? '') ?></textarea>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="submit" name="update_offer"
                                                                class="btn text-white fw-semibold" style="background:#0d9488;">
                                                                <i class="fa fa-save me-1"></i> Update
                                                            </button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete / Activate -->
                                        <?php if ($offer['status'] == 'Active'): ?>
                                            <form method="POST" action="admin_offers.php"
                                                style="display:inline-block"
                                                onsubmit="return confirm('Deactivate this offer?');">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($offer['id']) ?>">
                                                <button type="submit" name="delete_offer"
                                                    class="btn btn-sm btn-outline-danger">
                                                    <i class="fa fa-ban"></i> Deactivate
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="admin_offers.php"
                                                style="display:inline-block"
                                                onsubmit="return confirm('Activate this offer?');">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($offer['id']) ?>">
                                                <button type="submit" name="activate_offer"
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
            <ul class="pagination mb-0 justify-content-center">
                <?php
                $qsBase = '';
                if ($search !== '') $qsBase .= '&search=' . urlencode($search);
                if ($status_filter !== '') $qsBase .= '&status_filter=' . urlencode($status_filter);
                if ($type_filter !== '') $qsBase .= '&type_filter=' . urlencode($type_filter);
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

<!-- Add Offer Modal -->
<div class="modal fade" id="addOfferModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="max-height: 90vh;">
            <form method="POST" action="admin_offers.php">
                <div class="modal-header" style="background:var(--teal); color:#fff;">
                    <h5 class="modal-title"><i class="fa fa-plus me-2"></i> Add New Offer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 65vh; overflow-y: auto;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Coupon Code</label>
                            <input name="code" class="form-control"
                                data-validation="required"
                                placeholder="e.g., SAVE20"
                                style="text-transform: uppercase;">
                            <span class="error text-danger" id="codeError"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Discount Type</label>
                            <select name="discount_type" class="form-select" data-validation="required">
                                <option value="">Select Type</option>
                                <option value="percent">Percent (%)</option>
                                <option value="fixed">Fixed Amount ($)</option>
                            </select>
                            <span class="error text-danger" id="discount_typeError"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Discount Value</label>
                            <input name="discount_value" class="form-control"
                                data-validation="required numeric"
                                placeholder="e.g., 20 for 20% or $20">
                            <span class="error text-danger" id="discount_valueError"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Min Order Amount (Optional)</label>
                            <input name="min_order_amount" class="form-control"
                                data-validation="numeric"
                                placeholder="Leave empty for no minimum">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Max Applicable Amount (Optional)</label>
                            <input name="max_applicable_amount" class="form-control"
                                data-validation="numeric"
                                placeholder="Leave empty for no limit">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Max Discount Amount (Optional)</label>
                            <input name="max_discount_amount" class="form-control"
                                data-validation="numeric"
                                placeholder="Leave empty for no limit">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Valid From (Optional)</label>
                            <input type="datetime-local" name="valid_from" class="form-control">
                            <small class="text-muted">Leave empty to start immediately</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Valid To (Optional)</label>
                            <input type="datetime-local" name="valid_to" class="form-control">
                            <small class="text-muted">Leave empty for no expiry</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Usage Limit (Optional)</label>
                            <input name="usage_limit" class="form-control"
                                data-validation="numeric"
                                placeholder="Total uses allowed">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Per User Limit (Optional)</label>
                            <input name="per_user_limit" class="form-control"
                                data-validation="numeric"
                                placeholder="Uses per user">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" data-validation="required">
                                <option value="">Select Status</option>
                                <option value="Active" selected>Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                            <span class="error text-danger" id="statusError"></span>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description (Optional)</label>
                            <textarea name="description" class="form-control" rows="3"
                                placeholder="Internal notes about this offer"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" type="submit" name="add_offer">
                        <i class="fa fa-save me-1"></i> Save Offer
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