<?php
include_once 'db_config.php';
include 'admin_authentication.php';
ob_start();

if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'admin@example.com';
}
$admin_email = $_SESSION['admin_email'];

// ADD TEAM MEMBER
if (isset($_POST['add_team_member'])) {
    $name = $_POST['name'];
    $designation = $_POST['designation'];
    $bio = $_POST['bio'];
    $facebook_url = $_POST['facebook_url'];
    $twitter_url = $_POST['twitter_url'];
    $linkedin_url = $_POST['linkedin_url'];
    $display_order = $_POST['display_order'];
    $status = $_POST['status'];

    // Handle photo upload
    $photo = 'images/team/default.jpg';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "images/team/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            $photo = $target_file;
        }
    }

    $stmt = $con->prepare("CALL TeamMembers_Insert(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param(
            "sssssssis",
            $name,
            $designation,
            $photo,
            $bio,
            $facebook_url,
            $twitter_url,
            $linkedin_url,
            $display_order,
            $status
        );
        if ($stmt->execute()) {
            setcookie('success', "Team member added successfully", time() + 5, '/');
        } else {
            setcookie('error', "Error adding team member: " . $stmt->error, time() + 5, '/');
        }
        $stmt->close();
        flush_stored_results($con);
        header("Location: admin_team.php");
        exit();
    }
}

// UPDATE TEAM MEMBER
if (isset($_POST['update_team_member'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $designation = $_POST['designation'];
    $bio = $_POST['bio'];
    $facebook_url = $_POST['facebook_url'];
    $twitter_url = $_POST['twitter_url'];
    $linkedin_url = $_POST['linkedin_url'];
    $display_order = $_POST['display_order'];
    $status = $_POST['status'];
    $photo = $_POST['current_photo'];

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "images/team/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            // Delete old photo if not default
            if ($photo != 'images/team/default.jpg' && file_exists($photo)) {
                unlink($photo);
            }
            $photo = $target_file;
        }
    }

    $stmt = $con->prepare("CALL TeamMembers_Update(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param(
            "isssssssis",
            $id,
            $name,
            $designation,
            $photo,
            $bio,
            $facebook_url,
            $twitter_url,
            $linkedin_url,
            $display_order,
            $status
        );
        if ($stmt->execute()) {
            setcookie('success', "Team member updated successfully", time() + 5, '/');
        } else {
            setcookie('error', "Error updating team member: " . $stmt->error, time() + 5, '/');
        }
        $stmt->close();
        flush_stored_results($con);
        header("Location: admin_team.php");
        exit();
    }
}

// DELETE TEAM MEMBER
if (isset($_POST['delete_team_member'])) {
    $id = $_POST['id'];
    $photo = $_POST['photo'];

    $stmt = $con->prepare("CALL TeamMembers_Delete(?)");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // Delete photo if not default
            if ($photo != 'images/team/default.jpg' && file_exists($photo)) {
                unlink($photo);
            }
            setcookie('success', "Team member deleted successfully", time() + 5, '/');
        } else {
            setcookie('error', "Error deleting team member: " . $stmt->error, time() + 5, '/');
        }
        $stmt->close();
        flush_stored_results($con);
        header("Location: admin_team.php");
        exit();
    }
}

// FETCH ALL TEAM MEMBERS
$rows = [];
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$total = 0;
$totalPages = 1;
$offset = ($page - 1) * $perPage;

$stmt = $con->prepare("CALL TeamMembers_Select(NULL)");
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
?>

<div class="">
    <!-- Row 1: Heading -->
    <div class="mb-3">
        <h4 class="mb-0" style="color:var(--teal)">Manage Team Members</h4>
    </div>

    <!-- Row 2: Add Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" data-bs-toggle="modal" data-bs-target="#addTeamModal">
            <i class="fa fa-plus me-1"></i> Add Team Member
        </button>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:5%;">S.No</th>
                        <th style="width:10%;">Photo</th>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No team members found.</td>
                        </tr>
                        <?php else: foreach ($rows as $i => $member): ?>
                            <tr>
                                <td><?= htmlspecialchars($offset + $i + 1) ?></td>
                                <td>
                                    <img src="<?= htmlspecialchars($member['photo']) ?>"
                                        class="img-thumbnail rounded-circle"
                                        style="width:60px; height:60px; object-fit:cover;"
                                        onerror="this.src='images/team/default.jpg'">
                                </td>
                                <td><?= htmlspecialchars($member['name']) ?></td>
                                <td><?= htmlspecialchars($member['designation']) ?></td>
                                <td><span class="badge bg-secondary"><?= $member['display_order'] ?></span></td>
                                <td>
                                    <?php if ($member['status'] == 'Active'): ?>
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
                                            data-bs-target="#viewTeamModal<?= (int)$member['id'] ?>">
                                            <i class="fa fa-eye"></i> View
                                        </a>

                                        <!-- View Modal -->
                                        <div class="modal fade" id="viewTeamModal<?= (int)$member['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background:#0d9488; color:white;">
                                                        <h5 class="modal-title"><i class="fa fa-user me-2"></i>Team Member Details</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="text-center mb-3">
                                                            <img src="<?= htmlspecialchars($member['photo']) ?>"
                                                                class="img-fluid rounded-circle border"
                                                                style="width:150px; height:150px; object-fit:cover;"
                                                                onerror="this.src='images/team/default.jpg'">
                                                        </div>
                                                        <table class="table table-borderless">
                                                            <tr>
                                                                <th style="width:40%; color:#0d9488;">Name:</th>
                                                                <td><?= htmlspecialchars($member['name']) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Designation:</th>
                                                                <td><?= htmlspecialchars($member['designation']) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Bio:</th>
                                                                <td><?= nl2br(htmlspecialchars($member['bio'])) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Display Order:</th>
                                                                <td><?= $member['display_order'] ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th style="color:#0d9488;">Status:</th>
                                                                <td>
                                                                    <?php if ($member['status'] == 'Active'): ?>
                                                                        <span class="badge bg-success">Active</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-danger">Inactive</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                            <?php if (!empty($member['facebook_url']) || !empty($member['twitter_url']) || !empty($member['linkedin_url'])): ?>
                                                                <tr>
                                                                    <th style="color:#0d9488;">Social Media:</th>
                                                                    <td>
                                                                        <?php if (!empty($member['facebook_url'])): ?>
                                                                            <a href="<?= htmlspecialchars($member['facebook_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1">
                                                                                <i class="fab fa-facebook"></i>
                                                                            </a>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($member['twitter_url'])): ?>
                                                                            <a href="<?= htmlspecialchars($member['twitter_url']) ?>" target="_blank" class="btn btn-sm btn-outline-info me-1">
                                                                                <i class="fab fa-twitter"></i>
                                                                            </a>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($member['linkedin_url'])): ?>
                                                                            <a href="<?= htmlspecialchars($member['linkedin_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                                <i class="fab fa-linkedin"></i>
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endif; ?>
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
                                            data-bs-target="#editTeamModal<?= (int)$member['id'] ?>">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editTeamModal<?= (int)$member['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="admin_team.php" enctype="multipart/form-data">
                                                        <input type="hidden" name="id" value="<?= $member['id'] ?>">
                                                        <input type="hidden" name="current_photo" value="<?= htmlspecialchars($member['photo']) ?>">

                                                        <div class="modal-header" style="background:#0d9488; color:#fff;">
                                                            <h5 class="modal-title"><i class="fa fa-edit me-2"></i>Edit Team Member</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>

                                                        <div class="modal-body">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Name</label>
                                                                    <input name="name" class="form-control"
                                                                        value="<?= htmlspecialchars($member['name']) ?>"
                                                                        data-validation="required">
                                                                    <span class="error text-danger" id="nameError"></span>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Designation</label>
                                                                    <input name="designation" class="form-control"
                                                                        value="<?= htmlspecialchars($member['designation']) ?>"
                                                                        data-validation="required">
                                                                    <span class="error text-danger" id="designationError"></span>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Photo</label>
                                                                    <input type="file" name="photo" class="form-control" accept="image/*">
                                                                    <small class="text-muted">Leave empty to keep current photo</small>
                                                                    <div class="mt-2">
                                                                        <img src="<?= htmlspecialchars($member['photo']) ?>"
                                                                            style="width:80px; height:80px; object-fit:cover;"
                                                                            class="rounded-circle border"
                                                                            onerror="this.src='images/team/default.jpg'">
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-3">
                                                                    <label class="form-label">Display Order</label>
                                                                    <input name="display_order" class="form-control"
                                                                        value="<?= htmlspecialchars($member['display_order']) ?>"
                                                                        data-validation="numeric">
                                                                </div>

                                                                <div class="col-md-3">
                                                                    <label class="form-label">Status</label>
                                                                    <select name="status" class="form-select" data-validation="required">
                                                                        <option value="Active" <?= $member['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                                                        <option value="Inactive" <?= $member['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                                                    </select>
                                                                </div>

                                                                <div class="col-12">
                                                                    <label class="form-label">Bio</label>
                                                                    <textarea name="bio" class="form-control" rows="3"><?= htmlspecialchars($member['bio']) ?></textarea>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label"><i class="fab fa-facebook text-primary me-1"></i>Facebook URL</label>
                                                                    <input name="facebook_url" class="form-control"
                                                                        value="<?= htmlspecialchars($member['facebook_url']) ?>">
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label"><i class="fab fa-twitter text-info me-1"></i>Twitter URL</label>
                                                                    <input name="twitter_url" class="form-control"
                                                                        value="<?= htmlspecialchars($member['twitter_url']) ?>">
                                                                </div>

                                                                <div class="col-md-12">
                                                                    <label class="form-label"><i class="fab fa-linkedin text-primary me-1"></i>LinkedIn URL</label>
                                                                    <input name="linkedin_url" class="form-control"
                                                                        value="<?= htmlspecialchars($member['linkedin_url']) ?>">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="submit" name="update_team_member"
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
                                        <form method="POST" action="admin_team.php"
                                            style="display:inline-block"
                                            onsubmit="return confirm('Delete this team member?');">
                                            <input type="hidden" name="id" value="<?= $member['id'] ?>">
                                            <input type="hidden" name="photo" value="<?= htmlspecialchars($member['photo']) ?>">
                                            <button type="submit" name="delete_team_member"
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

<!-- Add Team Member Modal -->
<div class="modal fade" id="addTeamModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="admin_team.php" enctype="multipart/form-data">
                <div class="modal-header" style="background:var(--teal); color:#fff;">
                    <h5 class="modal-title"><i class="fa fa-plus me-2"></i>Add Team Member</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input name="name" class="form-control" data-validation="required">
                            <span class="error text-danger" id="nameError"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Designation</label>
                            <input name="designation" class="form-control" data-validation="required">
                            <span class="error text-danger" id="designationError"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty for default avatar</small>
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

                        <div class="col-12">
                            <label class="form-label">Bio</label>
                            <textarea name="bio" class="form-control" rows="3"
                                placeholder="Short bio about the team member"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="fab fa-facebook text-primary me-1"></i>Facebook URL</label>
                            <input name="facebook_url" class="form-control" placeholder="https://facebook.com/profile">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="fab fa-twitter text-info me-1"></i>Twitter URL</label>
                            <input name="twitter_url" class="form-control" placeholder="https://twitter.com/handle">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label"><i class="fab fa-linkedin text-primary me-1"></i>LinkedIn URL</label>
                            <input name="linkedin_url" class="form-control" placeholder="https://linkedin.com/in/profile">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" type="submit" name="add_team_member">
                        <i class="fa fa-save me-1"></i> Add Team Member
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