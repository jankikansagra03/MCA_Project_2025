<?php
include_once("db_config.php");
include_once("admin_authentication.php"); // Ensure admin is logged in
$title = "Manage Users";

ob_start();

// Simple admin guard (adjust to your auth)
if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'admin@example.com';
}
$admin_email = $_SESSION['admin_email'];

// Upload dir
$uploadDir = "images/profile_pictures/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Helper to flush extra stored results
function flush_stored_results($con)
{
    while ($con->more_results() && $con->next_result()) {
        $extra = $con->use_result();
        if ($extra instanceof mysqli_result) $extra->free();
    }
}

// ---------- AJAX endpoints (return plain text or HTML) ----------
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // ---- Return table HTML fragment (used to refresh table via AJAX) ----
    if ($action === 'table') {
        // server-side search/pagination logic similar to main render
        $search = trim($_GET['search'] ?? '');
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Fetch all rows via stored procedure
        $pFullname = null;
        $pEmail = null;
        $pRole = null;
        $pStatus = null;
        $pPassword = null;
        $pToken = null;
        $pmobile = null;
        $stmt = $con->prepare("CALL Registration_Select(?,?,?,?,?,?,?)");
        if (!$stmt) {
            echo "<div class='alert alert-danger'>DB prepare failed: " . htmlspecialchars($con->error) . "</div>";
            exit;
        }
        $stmt->bind_param("sssssssi", $pFullname, $pEmail, $pRole, $pStatus, $pPassword, $pToken, $pmobile);
        $stmt->execute();
        $res = $stmt->get_result();
        $allRows = [];
        if ($res) {
            $allRows = $res->fetch_all(MYSQLI_ASSOC);
            $res->free();
            flush_stored_results($con);
        } else {
            flush_stored_results($con);
        }

        // filter partial
        if ($search !== '') {
            $q = mb_strtolower($search, 'UTF-8');
            $filtered = [];
            foreach ($allRows as $r) {
                $hay = mb_strtolower(($r['fullname'] ?? '') . ' ' . ($r['email'] ?? '') . ' ' . ($r['role'] ?? '') . ' ' . ($r['status'] ?? ''), 'UTF-8');
                if (mb_strpos($hay, $q) !== false) $filtered[] = $r;
            }
        } else {
            $filtered = $allRows;
        }

        $total = count($filtered);
        $totalPages = (int) ceil(max(1, $total) / $perPage);
        $pageRows = array_slice($filtered, $offset, $perPage);

        // Render plain HTML for table body + pagination controls
        ob_start();
?>
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="usersTable">
                <thead>
                    <tr>
                        <th style="width:8%;">User ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th style="width:10%;">Status</th>
                        <th style="width:28%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pageRows) === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No users found.</td>
                        </tr>
                        <?php else: foreach ($pageRows as $row): ?>
                            <tr data-email="<?= htmlspecialchars($row['email']) ?>">
                                <td><?= htmlspecialchars($row['id'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($row['profile_picture'])): ?>
                                        <img src="<?= htmlspecialchars($row['profile_picture']) ?>" class="profile-thumb me-2" style="width:48px;height:48px;object-fit:cover;border-radius:8px;">
                                    <?php endif; ?>
                                    <?= htmlspecialchars($row['fullname']) ?>
                                </td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['role']) ?></td>
                                <td>
                                    <?php if (strtolower($row['status']) === 'active'): ?>
                                        <span class="badge-status-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge-status-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-outline-primary small-action btn-view" data-email="<?= htmlspecialchars($row['email']) ?>">View</button>
                                        <button class="btn btn-sm btn-outline-secondary small-action btn-edit" data-email="<?= htmlspecialchars($row['email']) ?>">Edit</button>
                                        <a href="view_cart.php?user_email=<?= urlencode($row['email']) ?>" class="btn btn-sm btn-outline-info small-action">Cart</a>

                                        <?php if (strtolower($row['status']) === 'active'): ?>
                                            <button class="btn btn-sm btn-outline-warning small-action btn-deactivate" data-email="<?= htmlspecialchars($row['email']) ?>">Deactivate</button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-success small-action btn-activate" data-email="<?= htmlspecialchars($row['email']) ?>">Activate</button>
                                        <?php endif; ?>

                                        <button class="btn btn-sm btn-outline-danger small-action btn-delete" data-email="<?= htmlspecialchars($row['email']) ?>">Delete</button>
                                    </div>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav class="mt-3" aria-label="Page navigation">
            <ul class="pagination">
                <?php
                $qsBase = '';
                if ($search !== '') $qsBase .= '&search=' . urlencode($search);
                $range = 3;
                $start = max(1, $page - $range);
                $end = min($totalPages, $page + $range);
                if ($page > 1) echo '<li class="page-item"><a class="page-link table-page" href="#" data-page="' . ($page - 1) . $qsBase . '">&laquo; Prev</a></li>';
                else echo '<li class="page-item disabled"><span class="page-link">&laquo; Prev</span></li>';

                for ($p = $start; $p <= $end; $p++) {
                    $active = $p === $page ? ' active' : '';
                    echo '<li class="page-item' . $active . '"><a class="page-link table-page" href="#" data-page="' . $p . $qsBase . '">' . $p . '</a></li>';
                }

                if ($page < $totalPages) echo '<li class="page-item"><a class="page-link table-page" href="#" data-page="' . ($page + 1) . $qsBase . '">Next &raquo;</a></li>';
                else echo '<li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>';
                ?>
            </ul>
        </nav>
    <?php
        $html = ob_get_clean();
        echo $html;
        exit;
    }

    // ---- Return VIEW modal body HTML snippet (plain HTML) ----
    if ($action === 'view-body' && !empty($_GET['email'])) {
        $email = trim($_GET['email']);
        $stmt = $con->prepare("CALL Registration_Select(?,?,?,?,?,?,?)");
        $pFullname = null;
        $pEmail = $email;
        $pRole = null;
        $pStatus = null;
        $pPassword = null;
        $pToken = null;
        $pmobile = null;
        $stmt->bind_param("sssssssi", $pFullname, $pEmail, $pRole, $pStatus, $pPassword, $pToken, $pmobile);
        $stmt->execute();
        $res = $stmt->get_result();
        $u = $res ? $res->fetch_assoc() : null;
        if ($res) {
            $res->free();
            flush_stored_results($con);
        } else flush_stored_results($con);
        if (!$u) {
            echo "<div class='p-3'>User not found.</div>";
            exit;
        }

        // output html snippet
    ?>
        <div class="row">
            <div class="col-md-4 text-center">
                <?php if (!empty($u['profile_picture'])): ?>
                    <img src="<?= htmlspecialchars($u['profile_picture']) ?>" class="img-fluid mb-3" style="max-width:180px;border-radius:8px;">
                <?php else: ?>
                    <div style="width:180px;height:180px;border-radius:8px;background:#f1f1f1;display:flex;align-items:center;justify-content:center;color:#999;">No Image</div>
                <?php endif; ?>
            </div>
            <div class="col-md-8">
                <h5><?= htmlspecialchars($u['fullname']) ?></h5>
                <p><strong>Email:</strong> <?= htmlspecialchars($u['email']) ?></p>
                <p><strong>Role:</strong> <?= htmlspecialchars($u['role']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($u['status']) ?></p>
                <p><strong>Mobile:</strong> <?= htmlspecialchars($u['mobile']) ?></p>
                <p><strong>Gender:</strong> <?= htmlspecialchars($u['gender']) ?></p>
                <p><strong>Address:</strong><br><?= nl2br(htmlspecialchars($u['address'])) ?></p>
            </div>
        </div>
    <?php
        exit;
    }

    // ---- Return EDIT modal body HTML snippet (form fragment) ----
    if ($action === 'edit-body' && !empty($_GET['email'])) {
        $email = trim($_GET['email']);
        $stmt = $con->prepare("CALL Registration_Select(?,?,?,?,?,?,?)");
        $pFullname = null;
        $pEmail = $email;
        $pRole = null;
        $pStatus = null;
        $pPassword = null;
        $pToken = null;
        $pmobile = null;
        $stmt->bind_param("sssssssi", $pFullname, $pEmail, $pRole, $pStatus, $pPassword, $pToken, $pmobile);
        $stmt->execute();
        $res = $stmt->get_result();
        $u = $res ? $res->fetch_assoc() : null;
        if ($res) {
            $res->free();
            flush_stored_results($con);
        } else flush_stored_results($con);
        if (!$u) {
            echo "<div class='p-3'>User not found.</div>";
            exit;
        }

        // output the form fragment
    ?>
        <form id="editForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="original_email" value="<?= htmlspecialchars($u['email']) ?>">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Full Name</label><input name="fullname" class="form-control" value="<?= htmlspecialchars($u['fullname']) ?>"></div>
                <div class="col-md-6"><label class="form-label">Email (identifier)</label><input class="form-control" value="<?= htmlspecialchars($u['email']) ?>" disabled></div>

                <div class="col-md-4"><label class="form-label">Password <small class="text-muted">(leave blank to keep)</small></label>
                    <input name="password" type="password" class="form-control">
                </div>

                <div class="col-md-4"><label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option <?= $u['role'] === 'User' ? 'selected' : '' ?>>User</option>
                        <option <?= $u['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                        <option <?= $u['role'] === 'Manager' ? 'selected' : '' ?>>Manager</option>
                    </select>
                </div>

                <div class="col-md-4"><label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option <?= strtolower($u['status']) === 'active' ? 'selected' : '' ?>>Active</option>
                        <option <?= strtolower($u['status']) !== 'active' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-md-4"><label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="" <?= $u['gender'] === '' ? 'selected' : '' ?>>Prefer not to say</option>
                        <option value="Male" <?= $u['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $u['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>

                <div class="col-md-4"><label class="form-label">Mobile</label><input name="mobile" class="form-control" value="<?= htmlspecialchars($u['mobile']) ?>"></div>

                <div class="col-md-4"><label class="form-label">Profile Picture (replace)</label><input name="profile_picture" type="file" class="form-control"></div>

                <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($u['address']) ?></textarea></div>
            </div>

            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-teal">Save Changes</button>
                <button type="button" class="btn btn-outline-secondary" id="editCancelBtn">Close</button>
            </div>
        </form>
<?php
        exit;
    }

    // ---- ADD (form POST via AJAX, returns plain text) ----
    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // validate
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($fullname === '' || $email === '' || $password === '') {
            echo "ERROR: Full name, email and password required.";
            exit;
        }

        $role = $_POST['role'] ?? 'User';
        $status = $_POST['status'] ?? 'Inactive';
        $gender = $_POST['gender'] ?? null;
        $mobile = $_POST['mobile'] ?? null;
        $address = $_POST['address'] ?? null;

        // profile picture
        $profile_picture = null;
        if (!empty($_FILES['profile_picture']['name'])) {
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $safe = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = $uploadDir . $safe;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest)) $profile_picture = 'uploads/' . $safe;
        }



        $sql = "CALL Registration_Insert(?,?,?,?,?,?,?,?)";
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            echo "ERROR: DB prepare failed: " . $con->error;
            exit;
        }
        $stmt->bind_param("ssssssss", $fullname, $email, $password, $mobile, $gender, $profile_picture, $address, $token);
        if ($stmt->execute()) {
            flush_stored_results($con);
            echo "OK: User added.";
            exit;
        } else {
            echo "ERROR: Insert failed: " . $stmt->error;
            exit;
        }
    }

    // ---- EDIT (form POST via AJAX, plain text) ----
    if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $orig_email = trim($_POST['original_email'] ?? '');
        if ($orig_email === '') {
            echo "ERROR: Identifier missing.";
            exit;
        }

        $fullname = trim($_POST['fullname'] ?? '') !== '' ? trim($_POST['fullname']) : null;
        $password = $_POST['password'] ?? '';
        $password_hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
        $role = $_POST['role'] ?? null;
        $status = $_POST['status'] ?? null;
        $gender = $_POST['gender'] ?? null;
        $mobile = $_POST['mobile'] ?? null;
        $address = $_POST['address'] ?? null;

        $profile_picture = null;
        if (!empty($_FILES['profile_picture']['name'])) {
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $safe = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = $uploadDir . $safe;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest)) $profile_picture = 'uploads/' . $safe;
        }

        $sql = "CALL Registration_Update(?,?,?,?,?,?,?,?,?)";
        $stmt = $con->prepare($sql);
        if (!$stmt) {
            echo "ERROR: DB prepare failed: " . $con->error;
            exit;
        }
        // bind variables (strings or NULL)
        $b_fullname = $fullname;
        $b_password = $password_hash;
        $b_role = $role;
        $b_status = $status;
        $b_mobile = $mobile;
        $b_profile = $profile_picture;
        $b_gender = $gender;
        $b_address = $address;
        $b_email = $orig_email;
        $stmt->bind_param("sssssssss", $b_fullname, $b_password, $b_role, $b_status, $b_mobile, $b_profile, $b_gender, $b_address, $b_email);
        if ($stmt->execute()) {
            flush_stored_results($con);
            echo "OK: User updated.";
            exit;
        } else {
            echo "ERROR: Update failed: " . $stmt->error;
            exit;
        }
    }

    // ---- DELETE (POST via AJAX) ----
    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            echo "ERROR: Email required.";
            exit;
        }
        $stmt = $con->prepare("CALL Registration_Delete(?)");
        if (!$stmt) {
            echo "ERROR: DB prepare failed: " . $con->error;
            exit;
        }
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            flush_stored_results($con);
            echo "OK: User deleted.";
            exit;
        } else {
            echo "ERROR: Delete failed: " . $stmt->error;
            exit;
        }
    }

    // ---- ACTIVATE / DEACTIVATE (POST via AJAX) ----
    if (($action === 'activate' || $action === 'deactivate') && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            echo "ERROR: Email required.";
            exit;
        }
        $status = $action === 'activate' ? 'Active' : 'Inactive';
        $stmt = $con->prepare("CALL Registration_Update(?,?,?,?,?,?,?,?,?)");
        if (!$stmt) {
            echo "ERROR: DB prepare failed: " . $con->error;
            exit;
        }
        $null = null;
        $stmt->bind_param("sssssssss", $null, $null, $null, $status, $null, $null, $null, $null, $email);
        if ($stmt->execute()) {
            flush_stored_results($con);
            echo "OK: User $status.";
            exit;
        } else {
            echo "ERROR: $status failed: " . $stmt->error;
            exit;
        }
    }

    echo "ERROR: Unknown action.";
    exit;
}

// ---------- PAGE RENDER (initial page load) ----------
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap">
    <!-- Left: Add button -->
    <div class="me-2">
        <button id="btnAdd" class=" btn text-white fw-semibold" style="background-color:#0d9488;">
            <i class=" fa fa-plus me-1"></i> Add New User
        </button>
    </div>

    <!-- Right: Search (keeps compact on small screens) -->
    <form id="searchForm" class="d-flex ms-auto" role="search" onsubmit="return false;">
        <div class="input-group" style="min-width:220px; max-width:520px;">
            <input id="searchInput" class="form-control" placeholder="Search name, email, role, status" value="<?= htmlspecialchars($search) ?>">
            <button id="searchBtn" class="btn btn-outline-secondary" type="button"><i class="fa fa-search"></i></button>
        </div>
    </form>
</div>

<!-- Table + pagination will be loaded into this container via AJAX -->
<div id="tableContainer" class="table-fragment"></div>
</div>

<!-- Modals (empty bodies; filled by AJAX ) -->
<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-large">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--teal);color:#fff;">
                <h5 class="modal-title"><i class="fa fa-eye me-2"></i> View User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewBody">Loading...</div>
            <div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-large">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--teal);color:#fff;">
                <h5 class="modal-title"><i class="fa fa-edit me-2"></i> Edit User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editBody">Loading...</div>
        </div>
    </div>
</div>

<!-- Add Modal (contains form) -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-large">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--teal);color:#fff;">
                <h5 class="modal-title"><i class="fa fa-user-plus me-2"></i> Add User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addForm" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Full Name</label><input name="fullname" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Role</label><select name="role" class="form-select">
                                <option>User</option>
                                <option>Admin</option>
                                <option>Manager</option>
                            </select></div>
                        <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select">
                                <option>Active</option>
                                <option selected>Inactive</option>
                            </select></div>
                        <div class="col-md-4"><label class="form-label">Gender</label><select name="gender" class="form-select">
                                <option value="">Prefer not to say</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select></div>
                        <div class="col-md-4"><label class="form-label">Mobile</label><input name="mobile" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Profile Picture</label><input name="profile_picture" type="file" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="3"></textarea></div>
                    </div>
                    <div class="mt-3 text-end">
                        <button type="submit" class="btn text-white fw-semibold" style="background-color:#0d9488;">Save</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Helper: show alert from plain-text server reply (prefix OK: or ERROR:)
    function handleServerText(text) {
        text = text.trim();
        if (text.startsWith('OK:')) {
            return {
                ok: true,
                message: text.substring(3).trim()
            };
        } else if (text.startsWith('ERROR:')) {
            return {
                ok: false,
                message: text.substring(6).trim()
            };
        } else {
            // Fallback: treat non-prefixed as OK
            return {
                ok: true,
                message: text
            };
        }
    }

    // Load table fragment via AJAX
    async function loadTable(page = 1) {
        const q = encodeURIComponent(document.getElementById('searchInput').value.trim());
        const resp = await fetch('?action=table&search=' + q + '&page=' + page);
        const html = await resp.text();
        document.getElementById('tableContainer').innerHTML = html;
        attachTableEventHandlers(); // reattach handlers after replacing HTML
    }

    // Attach event handlers to buttons inside table
    function attachTableEventHandlers() {
        // View buttons
        document.querySelectorAll('.btn-view').forEach(btn => {
            btn.onclick = async function() {
                const email = this.dataset.email;
                const resp = await fetch('?action=view-body&email=' + encodeURIComponent(email));
                const html = await resp.text();
                document.getElementById('viewBody').innerHTML = html;
                const vm = new bootstrap.Modal(document.getElementById('viewModal'));
                vm.show();
            };
        });

        // Edit buttons
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.onclick = async function() {
                const email = this.dataset.email;
                const resp = await fetch('?action=edit-body&email=' + encodeURIComponent(email));
                const html = await resp.text();
                document.getElementById('editBody').innerHTML = html;
                // attach submit handler for the loaded edit form
                const editForm = document.getElementById('editForm');
                if (editForm) {
                    editForm.onsubmit = async function(e) {
                        e.preventDefault();
                        const fd = new FormData(editForm);
                        // send to action=edit (server expects POST multipart)
                        const res = await fetch('?action=edit', {
                            method: 'POST',
                            body: fd
                        });
                        const txt = await res.text();
                        const result = handleServerText(txt);
                        alert(result.message);
                        if (result.ok) {
                            const em = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                            em.hide();
                            await loadTable(1);
                        }
                    };
                    // Cancel button inside fragment
                    const cancel = document.getElementById('editCancelBtn');
                    if (cancel) cancel.onclick = () => bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                }
                const em = new bootstrap.Modal(document.getElementById('editModal'));
                em.show();
            };
        });

        // Activate
        document.querySelectorAll('.btn-activate').forEach(btn => {
            btn.onclick = async function() {
                if (!confirm('Activate this user?')) return;
                const email = this.dataset.email;
                const fd = new FormData();
                fd.append('email', email);
                const res = await fetch('?action=activate', {
                    method: 'POST',
                    body: fd
                });
                const txt = await res.text();
                const r = handleServerText(txt);
                alert(r.message);
                if (r.ok) loadTable(1);
            };
        });

        // Deactivate
        document.querySelectorAll('.btn-deactivate').forEach(btn => {
            btn.onclick = async function() {
                if (!confirm('Deactivate this user?')) return;
                const email = this.dataset.email;
                const fd = new FormData();
                fd.append('email', email);
                const res = await fetch('?action=deactivate', {
                    method: 'POST',
                    body: fd
                });
                const txt = await res.text();
                const r = handleServerText(txt);
                alert(r.message);
                if (r.ok) loadTable(1);
            };
        });

        // Delete
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.onclick = async function() {
                if (!confirm('Delete this user? This cannot be undone.')) return;
                const email = this.dataset.email;
                const fd = new FormData();
                fd.append('email', email);
                const res = await fetch('?action=delete', {
                    method: 'POST',
                    body: fd
                });
                const txt = await res.text();
                const r = handleServerText(txt);
                alert(r.message);
                if (r.ok) loadTable(1);
            };
        });

        // Pagination links inside table fragment (they have class .table-page and data-page)
        document.querySelectorAll('.table-page').forEach(a => {
            a.onclick = async function(e) {
                e.preventDefault();
                // data-page might include &search=...
                const raw = this.dataset.page;
                // parse page param
                const parts = raw.split('&');
                const page = parseInt(parts[0]) || 1;
                // if there is a search param in data, set searchInput accordingly
                const searchPart = parts.find(p => p.startsWith('search='));
                if (searchPart) {
                    document.getElementById('searchInput').value = decodeURIComponent(searchPart.split('=')[1]);
                }
                await loadTable(page);
            };
        });
    }

    // Initial wiring
    document.getElementById('btnAdd').onclick = function() {
        document.getElementById('addForm').reset();
        const am = new bootstrap.Modal(document.getElementById('addModal'));
        am.show();
    };

    // Add form submit via AJAX (plain text response)
    document.getElementById('addForm').onsubmit = async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const res = await fetch('?action=add', {
            method: 'POST',
            body: fd
        });
        const txt = await res.text();
        const r = handleServerText(txt);
        alert(r.message);
        if (r.ok) {
            bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
            await loadTable(1);
        }
    };

    // Search button
    document.getElementById('searchBtn').onclick = function() {
        loadTable(1);
    };

    // On page load, load table
    loadTable(<?= json_encode(intval($page)) ?>);
</script>



<?php
$content_admin = ob_get_clean();
include_once("admin_layout.php");
