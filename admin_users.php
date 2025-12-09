<?php
include_once 'db_config.php';
include 'admin_authentication.php';
ob_start();
if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'admin@example.com';
}
$admin_email = $_SESSION['admin_email'];

if (isset($_POST['delete_user'])) {
    $email = $_POST['email'];

    $stmt = $con->prepare("CALL Registration_Delete(?)");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            setcookie('success', "User deleted successfully", time() + 5);
        } else {
            setcookie('error', "Error deleting user: " . $stmt->error, time() + 5);
        }
        echo "<script>window.location.href='admin_users.php';</script>";
        $stmt->close();
    }
}

if (isset($_POST['activate_user'])) {
    $email = $_POST['email'];
    $fn = NULL;
    $pwd = NULL;
    $mob = NULL;
    $gen = NULL;
    $pp = NULL;
    $addr = NULL;
    $role = NULL;
    $status = "Active";
    $stmt = $con->prepare("CALL Registration_Update(?,?,?,?,?,?,?,?,?)");
    if ($stmt) {
        $stmt->bind_param("sssisssss", $fn, $email, $pwd, $mob, $gen, $pp, $addr, $status, $role);

        if ($stmt->execute()) {
            setcookie('success', "User activated successfully", time() + 5);
        } else {
            setcookie('error', "Error activating user: " . $stmt->error, time() + 5);
        }
        echo "<script>window.location.href='admin_users.php';</script>";
        flush_stored_results($con);
        $stmt->close();
    }
}

if (isset($_POST['deactivate_user'])) {
    $email = $_POST['email'];
    $fn = NULL;
    $pwd = NULL;
    $mob = NULL;
    $gen = NULL;
    $pp = NULL;
    $addr = NULL;
    $role = NULL;
    $status = "Inactive";
    $stmt = $con->prepare("CALL Registration_Update(?,?,?,?,?,?,?,?,?)");
    if ($stmt) {
        $stmt->bind_param("sssisssss", $fn, $email, $pwd, $mob, $gen, $pp, $addr, $status, $role);

        if ($stmt->execute()) {
            setcookie('success', "User deactivated successfully", time() + 5);
        } else {
            setcookie('error', "Error deactivating user", time() + 5);
        }
        echo "<script>window.location.href='admin_users.php';</script>";
        flush_stored_results($con);
        $stmt->close();
    }
}
if (isset($_POST['update_user'])) {

    $fullname = $_POST['fullname'];
    $email    = $_POST['email'];
    $mobile   = $_POST['mobile'];
    $gender   = $_POST['gender'];
    $role     = $_POST['role'];
    $status   = $_POST['status'];
    $address  = $_POST['address'];

    // NOT updating password or profile picture here
    $password = NULL;
    $profile_picture = NULL;

    $stmt = $con->prepare("CALL Registration_Update(?,?,?,?,?,?,?,?,?)");

    if ($stmt) {
        $stmt->bind_param(
            "sssisssss",
            $fullname,
            $email,
            $password,
            $mobile,
            $gender,
            $profile_picture,
            $address,
            $status,
            $role
        );

        if ($stmt->execute()) {
            setcookie("success", "User updated successfully.", time() + 5);
        } else {
            setcookie("error", "Update failed: " . $stmt->error, time() + 5);
        }

        echo "<script>window.location.href='admin_users.php';</script>";

        flush_stored_results($con);
        $stmt->close();
    }
}


if (isset($_POST['add_user'])) {
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'User';
    $status = $_POST['status'] ?? 'Inactive';
    $gender = $_POST['gender'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $address = $_POST['address'] ?? '';
    $profile_picture = "default.png";
    $token = bin2hex(random_bytes(16));

    $stmt = $con->prepare("CALL Registration_Insert(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param(
            "ssssssssss",
            $fullname,
            $email,
            $password,
            $mobile,
            $gender,
            $profile_picture,
            $address,
            $token,
            $role,
            $status
        );
        if ($stmt->execute()) {
            setcookie('success', "User added successfully", time() + 5);
        } else {
            setcookie('error', "Error adding user: " . $stmt->error, time() + 5);
        }
?>
<?php
        echo "<script>window.location.href='admin_users.php';</script>";
        $stmt->close();
    }
}

$rows = [];
$allRows = [];
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$total = 0;
$totalPages = 1;
$offset = ($page - 1) * $perPage;

if ($search !== '') {

    flush_stored_results($con);
    $stmt = $con->prepare("CALL Registration_Search(?)");
    if ($stmt) {
        $param = $search;
        $stmt->bind_param("s", $param);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) $allRows[] = $r;
            $res->free();
        }
        $stmt->close();
        flush_stored_results($con);
    } else {

        $allRows = [];
    }
    $total = count($allRows);
    $totalPages = (int) ceil(max(1, $total) / $perPage);
    $offset = ($page - 1) * $perPage;
    $rows = array_slice($allRows, $offset, $perPage);
} else {

    $countStmt = $con->prepare("SELECT COUNT(*) AS cnt FROM registration");
    if ($countStmt) {
        $countStmt->execute();
        $cres = $countStmt->get_result();
        if ($cres) {
            $crow = $cres->fetch_assoc();
            $total = intval($crow['cnt'] ?? 0);
            $cres->free();
        }
        $countStmt->close();
    }
    $totalPages = (int) ceil(max(1, $total) / $perPage);
    $offset = ($page - 1) * $perPage;

    // fetch page rows
    $sel = $con->prepare("SELECT id, fullname, email, mobile, role, status, profile_picture, gender, address FROM registration ORDER BY id DESC LIMIT ? OFFSET ?");
    if ($sel) {
        $sel->bind_param("ii", $perPage, $offset);
        $sel->execute();
        $res = $sel->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) $rows[] = $r;
            $res->free();
        }
        $sel->close();
    }
}
$totalPages = $totalPages ?? 1;
$offset = $offset ?? 0;
?>

<div class="">
    <!-- Row 1: Heading only -->
    <div class="mb-3">
        <h4 class="mb-0" style="color:var(--teal)">Manage Users</h4>
    </div>

    <!-- Row 2: Add New User + Search in one row -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <!-- Left: Add New User -->
        <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fa fa-plus me-1"></i> Add New User
        </button>

        <!-- Right: Search -->
        <form class="d-flex" method="GET" action="admin_users.php">
            <input type="text" name="search" style="font-size:small;" class="form-control me-2" placeholder="Search name, email, role, status" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" name="search_btn" class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;">
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
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No users found.</td>
                        </tr>
                        <?php else: foreach ($rows as $i => $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($offset + $i + 1) ?></td>
                                <td>
                                    <?php if (!empty($u['profile_picture'])): ?>
                                        <img src="<?= htmlspecialchars($u['profile_picture']) ?>" class="profile-thumb" alt="">
                                    <?php endif; ?>
                                    <?= htmlspecialchars($u['fullname']) ?>
                                </td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= htmlspecialchars($u['mobile']) ?></td>
                                <td><?= htmlspecialchars($u['role']) ?></td>
                                <td>
                                    <?php if (strtolower($u['status']) === 'active'): ?>
                                        <span class="badge-active">Active</span>
                                    <?php elseif (strtolower($u['status']) === 'deleted'): ?>
                                        <span class="badge-deleted">Deleted</span>
                                    <?php else: ?>
                                        <span class="badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>

                                    <div class="d-flex flex-wrap gap-2" style="text-align: left;">

                                        <!-- View -->
                                        <!-- inside your foreach ($rows as $i => $u) loop, replace the View button with this -->
                                        <!-- View Button -->
                                        <a class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewUserModal<?= (int)$u['id'] ?>">
                                            <i class="fa fa-eye"></i> View
                                        </a>

                                        <!-- View User Modal -->
                                        <div class="modal fade" id="viewUserModal<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content">

                                                    <div class="modal-header" style="background:#0d9488; color:white;">
                                                        <h5 class="modal-title">
                                                            <i class="fa fa-user me-2"></i> User Details
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <div class="row g-4">

                                                            <!-- LEFT COLUMN - PROFILE PHOTO -->
                                                            <div class="col-md-4 text-center">
                                                                <img src="images/profile_pictures/<?= htmlspecialchars($u['profile_picture']) ?>"
                                                                    class="img-fluid rounded-circle                        border"
                                                                    style="max-height: 200px; object-fit: cover;">
                                                                <h5 class="mt-3" style="color:#0d9488;">
                                                                    <?= htmlspecialchars($u['fullname']) ?>
                                                                </h5>
                                                                <span class="badge <?= $u['status'] == 'Active' ? 'bg-success' : 'bg-danger' ?>">
                                                                    <?= htmlspecialchars($u['status']) ?>
                                                                </span>
                                                            </div>

                                                            <!-- RIGHT COLUMN - DETAILS -->
                                                            <div class="col-md-8">
                                                                <table class="table table-borderless">
                                                                    <tr>
                                                                        <th style="width:35%; color:#0d9488;">Email:</th>
                                                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Mobile:</th>
                                                                        <td><?= htmlspecialchars($u['mobile']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Gender:</th>
                                                                        <td><?= htmlspecialchars($u['gender']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Role:</th>
                                                                        <td><?= htmlspecialchars($u['role']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Status:</th>
                                                                        <td><?= htmlspecialchars($u['status']) ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="color:#0d9488;">Address:</th>
                                                                        <td><?= htmlspecialchars($u['address']) ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>

                                                        </div>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" data-bs-dismiss="modal">Close</button>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>


                                        <!-- Edit -->
                                        <!-- Edit Button -->
                                        <a class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editUserModal<?= (int)$u['id'] ?>">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>

                                        <!-- Edit User Modal -->
                                        <div class="modal fade" id="editUserModal<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <div class="modal-content">

                                                    <form method="POST" action="admin_users.php">
                                                        <input type="hidden" name="edit_user" value="1">

                                                        <div class="modal-header" style="background:#0d9488; color:#fff;">
                                                            <h5 class="modal-title"><i class="fa fa-user-edit me-2"></i> Edit User</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>

                                                        <div class="modal-body">
                                                            <div class="row g-3">

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Full Name</label>
                                                                    <input type="text" name="fullname" class="form-control"
                                                                        value="<?= htmlspecialchars($u['fullname']) ?>" data-validation="required">
                                                                    <span class="error text-danger" id="fullnameError"></span>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Email (readonly)</label>
                                                                    <input type="email" name="email" class="form-control"
                                                                        value="<?= htmlspecialchars($u['email']) ?>" readonly>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Mobile</label>
                                                                    <input type="text" name="mobile" class="form-control"
                                                                        value="<?= htmlspecialchars($u['mobile']) ?>" data-validation="required numeric min max" data-min="10" data-max="10">
                                                                    <span class="error text-danger" id="mobileError"></span>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Gender</label>
                                                                    <select name="gender" class="form-select" data-validation="required">
                                                                        <option value="">Select Gender</option>
                                                                        <option value="Male" <?= $u['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                                                        <option value="Female" <?= $u['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                                                                        <option value="Other" <?= $u['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                                                                    </select>
                                                                    <span class="error text-danger" id="genderError"></span>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Role</label>
                                                                    <select name="role" class="form-select" data-validation="required">
                                                                        <option value="">Select Role</option>
                                                                        <option value="User" <?= $u['role'] == 'User' ? 'selected' : '' ?>>User</option>
                                                                        <option value="Admin" <?= $u['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
                                                                    </select>
                                                                    <span class="error text-danger" id="roleError"></span>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Status</label>
                                                                    <select name="status" class="form-select" data-validation="required">
                                                                        <option value="">Select Status</option>
                                                                        <option value="Active" <?= $u['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                                                        <option value="Inactive" <?= $u['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                                                    </select>
                                                                    <span class="error text-danger" id="statusError"></span>
                                                                </div>

                                                                <div class="col-12">
                                                                    <label class="form-label">Address</label>
                                                                    <textarea name="address" class="form-control" data-validation="required" rows="3"><?= htmlspecialchars($u['address']) ?></textarea>
                                                                    <span class="error text-danger" id="addressError"></span>
                                                                </div>

                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="submit" name="update_user"
                                                                class="btn text-white fw-semibold" style="background:#0d9488;">
                                                                <i class="fa fa-save me-1"></i> Update
                                                            </button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        </div>

                                                    </form>

                                                </div>
                                            </div>
                                        </div>


                                        <!-- Cart -->
                                        <a class="btn btn-sm btn-outline-info"
                                            href="user_cart.php?email=<?= urlencode($u['email']) ?>">
                                            <i class="fa fa-shopping-cart"></i> Cart
                                        </a>

                                        <!-- Delete -->
                                        <form method="POST" action="admin_users.php"
                                            style="display:inline-block"
                                            onsubmit="return confirm('Delete this user?');">
                                            <input type="hidden" name="email" value="<?= htmlspecialchars($u['email']) ?>">
                                            <button type="submit" name="delete_user"
                                                class="btn btn-sm btn-outline-danger">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        </form>

                                        <!-- Activate / Deactivate -->
                                        <?php if (strtolower($u['status']) === 'active'): ?>
                                            <!-- Deactivate -->
                                            <form method="POST" action="admin_users.php"
                                                style="display:inline-block"
                                                onsubmit="return confirm('Deactivate this user?');">
                                                <input type="hidden" name="email" value="<?= htmlspecialchars($u['email']) ?>">
                                                <button type="submit" name="deactivate_user"
                                                    class="btn btn-sm btn-outline-warning">
                                                    <i class="fa fa-ban"></i> Deactivate
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <!-- Activate -->
                                            <form method="POST" action="admin_users.php"
                                                style="display:inline-block"
                                                onsubmit="return confirm('Activate this user?');">
                                                <input type="hidden" name="email" value="<?= htmlspecialchars($u['email']) ?>">
                                                <button type="submit" name="activate_user"
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

<!-- View user Modal -->

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="admin_users.php">
                <input type="hidden" name="add_user" value="1">
                <div class="modal-header" style="background:var(--teal); color:#fff;">
                    <h5 class="modal-title"><i class="fa fa-user-plus me-2"></i> Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input name="fullname" class="form-control" id="fn" data-validation="required">
                            <span class="error text-danger" id="fullnameError"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input name="email" type="email" class="form-control" id="em" data-validation="required email">
                            <span class="error text-danger" id="emailError"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input name="password" type="text" class="form-control" id="pw" data-validation="required strongPassword min max" data-min="8" data-max="25">
                            <span class="error text-danger" id="passwordError"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input name="cpassword" type="text" class="form-control" id="cpw" data-validation="required confirmPassword" data-password-id="pw">
                            <span class="error text-danger" id="cpasswordError"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" data-validation="required">
                                <option selected value="">Select Role</option>
                                <option value="User">User</option>
                                <option value="Admin">Admin</option>
                            </select>
                            <span class="error text-danger" id="roleError"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" data-validation="required">
                                <option value="" selected>Select Status</option>
                                <option value="Active">Active</option>
                                <option value="Inavtive">Inactive</option>
                            </select>
                            <span class="error text-danger" id="statusError"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile</label>
                            <input name="mobile" class="form-control" id="mob" data-validation="required numeric min max" data-min="10" data-max="10">
                            <span class="error text-danger" id="mobileError"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select" data-validation="required">
                                <option value="" selected>Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>

                            </select>
                            <span class="error text-danger" id="genderError"></span>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3" data-validation="required"></textarea>
                            <span class="error text-danger" id="addressError"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" type=" submit" name="add_user"><i class="fa fa-save me-1"></i> Save</button>
                    <button class="btn btn-danger" type="button" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
$content_admin = ob_get_clean();
include 'admin_layout.php';
