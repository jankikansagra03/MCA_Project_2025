<?php
include_once 'db_config.php';
include 'admin_authentication.php';
ob_start();

if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'admin@example.com';
}
$admin_email = $_SESSION['admin_email'];

// ADD ADDRESS
if (isset($_POST['add_address'])) {
    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $address = $_POST['address'];

    $stmt = $con->prepare("CALL Addresses_Insert(?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssss", $user_id, $name, $email, $mobile, $address);
        if ($stmt->execute()) {
            setcookie('success', "Address added successfully", time() + 5);
        } else {
            setcookie('error', "Error adding address: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_addresses.php';</script>";
        exit();
    }
}

// UPDATE ADDRESS
if (isset($_POST['update_address'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $address = $_POST['address'];

    $stmt = $con->prepare("CALL Addresses_Update(?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issss", $id, $name, $email, $mobile, $address);
        if ($stmt->execute()) {
            setcookie('success', "Address updated successfully", time() + 5);
        } else {
            setcookie('error', "Error updating address: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_addresses.php';</script>";
        exit();
    }
}

// DELETE ADDRESS
if (isset($_POST['delete_address'])) {
    $id = $_POST['id'];

    $stmt = $con->prepare("CALL Addresses_Delete(?)");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            setcookie('success', "Address deleted successfully", time() + 5);
        } else {
            setcookie('error', "Error deleting address: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_addresses.php';</script>";
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

// SEARCH & PAGINATION
$rows = [];
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$total = 0;
$totalPages = 1;
$offset = ($page - 1) * $perPage;

// Get all addresses with user details
if ($search !== '') {
    // Search by user email, name, mobile, or address
    $stmt = $con->prepare("
        SELECT 
            a.id,
            a.user_id,
            a.name,
            a.email,
            a.mobile,
            a.address,
            r.fullname as user_fullname
        FROM addresses a
        LEFT JOIN registration r ON a.user_id = r.email
        WHERE a.user_id LIKE CONCAT('%', ?, '%')
            OR a.name LIKE CONCAT('%', ?, '%')
            OR a.email LIKE CONCAT('%', ?, '%')
            OR a.mobile LIKE CONCAT('%', ?, '%')
            OR a.address LIKE CONCAT('%', ?, '%')
            OR r.fullname LIKE CONCAT('%', ?, '%')
        ORDER BY a.id DESC
    ");

    if ($stmt) {
        $stmt->bind_param("ssssss", $search, $search, $search, $search, $search, $search);
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
    // Get all addresses
    $stmt = $con->prepare("
        SELECT 
            a.id,
            a.user_id,
            a.name,
            a.email,
            a.mobile,
            a.address,
            r.fullname as user_fullname
        FROM addresses a
        LEFT JOIN registration r ON a.user_id = r.email
        ORDER BY a.id DESC
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
        <h4 class="mb-0" style="color:var(--teal)">Manage Addresses</h4>
    </div>

    <!-- Row 2: Add New Address + Search -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <!-- Left: Add New Address -->
        <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" data-bs-toggle="modal" data-bs-target="#addAddressModal">
            <i class="fa fa-plus me-1"></i> Add New Address
        </button>

        <!-- Right: Search -->
        <form class="d-flex gap-2" method="GET" action="admin_addresses.php">
            <input type="text" name="search" style="font-size:small;" class="form-control"
                placeholder="Search by user, name, email, mobile or address"
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
                        <th>User</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No addresses found.</td>
                        </tr>
                        <?php else:
                        $currentUser = '';
                        foreach ($rows as $i => $addr):
                        ?>
                            <?php if ($currentUser !== $addr['user_id']):
                                $currentUser = $addr['user_id'];
                            ?>
                                <tr style="background-color: #f0f9ff;">
                                    <td colspan="7">
                                        <strong style="color:#0d9488;">
                                            <i class="fa fa-user"></i>
                                            <?php if (!empty($addr['user_fullname'])): ?>
                                                <?= htmlspecialchars($addr['user_fullname']) ?>
                                                <span style="font-weight: normal; color: #666;">
                                                    (<?= htmlspecialchars($addr['user_id']) ?>)
                                                </span>
                                            <?php else: ?>
                                                <?= htmlspecialchars($addr['user_id']) ?>
                                            <?php endif; ?>
                                        </strong>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <tr>
                                <td><?= htmlspecialchars($offset + $i + 1) ?></td>
                                <td>
                                    <?php if (!empty($addr['user_fullname'])): ?>
                                        <strong><?= htmlspecialchars($addr['user_fullname']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($addr['user_id']) ?></small>
                                    <?php else: ?>
                                        <?= htmlspecialchars($addr['user_id']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($addr['name']) ?></td>
                                <td><?= htmlspecialchars($addr['email']) ?></td>
                                <td><?= htmlspecialchars($addr['mobile']) ?></td>
                                <td>
                                    <small><?= htmlspecialchars(substr($addr['address'], 0, 50)) ?><?= strlen($addr['address']) > 50 ? '...' : '' ?></small>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <!-- View -->
                                        <a class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewAddressModal<?= (int)$addr['id'] ?>">
                                            <i class="fa fa-eye"></i> View
                                        </a>

                                        <!-- View Address Modal -->
                                        <div class="modal fade" id="viewAddressModal<?= (int)$addr['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background:#0d9488; color:white;">
                                                        <h5 class="modal-title">
                                                            <i class="fa fa-map-marker me-2"></i> Address Details
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <table class="table table-borderless">
                                                            <tr>
                                                                <th style="width:35%; color:#0d9488;">Address ID:</th>
                                                                <td><?= htmlspecialchars($addr['id']) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">User:</th>
                                                                <td>
                                                                    <?php if (!empty($addr['user_fullname'])): ?>
                                                                        <strong><?= htmlspecialchars($addr['user_fullname']) ?></strong><br>
                                                                        <small><?= htmlspecialchars($addr['user_id']) ?></small>
                                                                    <?php else: ?>
                                                                        <?= htmlspecialchars($addr['user_id']) ?>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Name:</th>
                                                                <td><?= htmlspecialchars($addr['name']) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Email:</th>
                                                                <td><?= htmlspecialchars($addr['email']) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Mobile:</th>
                                                                <td><?= htmlspecialchars($addr['mobile']) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Address:</th>
                                                                <td><?= nl2br(htmlspecialchars($addr['address'])) ?></td>
                                                            </tr>
                                                        </table>
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
                                            data-bs-target="#editAddressModal<?= (int)$addr['id'] ?>">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>

                                        <!-- Edit Address Modal -->
                                        <div class="modal fade" id="editAddressModal<?= (int)$addr['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="admin_addresses.php">
                                                        <input type="hidden" name="id" value="<?= $addr['id'] ?>">

                                                        <div class="modal-header" style="background:#0d9488; color:#fff;">
                                                            <h5 class="modal-title"><i class="fa fa-edit me-2"></i> Edit Address</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>

                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">User Email</label>
                                                                <input type="text" class="form-control"
                                                                    value="<?= !empty($addr['user_fullname']) ? htmlspecialchars($addr['user_fullname']) . ' (' . htmlspecialchars($addr['user_id']) . ')' : htmlspecialchars($addr['user_id']) ?>"
                                                                    disabled>
                                                                <small class="text-muted">User cannot be changed for existing address</small>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Name</label>
                                                                <input name="name" class="form-control"
                                                                    value="<?= htmlspecialchars($addr['name']) ?>"
                                                                    data-validation="required">
                                                                <span class="error text-danger" id="nameError"></span>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Email</label>
                                                                <input name="email" class="form-control"
                                                                    value="<?= htmlspecialchars($addr['email']) ?>"
                                                                    data-validation="required email">
                                                                <span class="error text-danger" id="emailError"></span>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Mobile</label>
                                                                <input name="mobile" class="form-control"
                                                                    value="<?= htmlspecialchars($addr['mobile']) ?>"
                                                                    data-validation="required numeric" data-min="10" data-max="15">
                                                                <span class="error text-danger" id="mobileError"></span>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Address</label>
                                                                <textarea name="address" class="form-control" rows="3"
                                                                    data-validation="required"><?= htmlspecialchars($addr['address']) ?></textarea>
                                                                <span class="error text-danger" id="addressError"></span>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="submit" name="update_address"
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
                                        <form method="POST" action="admin_addresses.php"
                                            style="display:inline-block"
                                            onsubmit="return confirm('Delete this address?');">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($addr['id']) ?>">
                                            <button type="submit" name="delete_address"
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

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="admin_addresses.php">
                <div class="modal-header" style="background:var(--teal); color:#fff;">
                    <h5 class="modal-title"><i class="fa fa-plus me-2"></i> Add New Address</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select User</label>
                        <select name="user_id" class="form-select" data-validation="required">
                            <option value="">Choose User</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= htmlspecialchars($user['email']) ?>">
                                    <?= htmlspecialchars($user['fullname']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="error text-danger" id="user_idError"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input name="name" class="form-control" data-validation="required">
                        <span class="error text-danger" id="nameError"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input name="email" class="form-control" data-validation="required email">
                        <span class="error text-danger" id="emailError"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mobile</label>
                        <input name="mobile" class="form-control"
                            data-validation="required numeric" data-min="10" data-max="15">
                        <span class="error text-danger" id="mobileError"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"
                            data-validation="required"></textarea>
                        <span class="error text-danger" id="addressError"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" type="submit" name="add_address">
                        <i class="fa fa-save me-1"></i> Save Address
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