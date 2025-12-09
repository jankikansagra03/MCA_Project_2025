<?php
include_once 'db_config.php';
include 'admin_authentication.php';
ob_start();

if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'admin@example.com';
}
$admin_email = $_SESSION['admin_email'];

// UPDATE PRIVACY POLICY CONTENT
if (isset($_POST['update_privacy'])) {
    $id = $_POST['id'];
    $page_title = $_POST['page_title'];
    $page_content = $_POST['page_content'];
    $status = $_POST['status'];

    $stmt = $con->prepare("CALL SitePages_Update(?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issss", $id, $page_title, $page_content, $status, $admin_email);
        if ($stmt->execute()) {
            setcookie('success', "Privacy policy updated successfully", time() + 5);
        } else {
            setcookie('error', "Error updating privacy policy: " . $stmt->error, time() + 5);
        }
        $stmt->close();
        flush_stored_results($con);
        echo "<script>window.location.href='admin_privacypolicy.php';</script>";
        exit();
    }
}

// FETCH PRIVACY POLICY DATA
$privacy_data = null;
$stmt = $con->prepare("CALL SitePages_Select('privacy')");
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $privacy_data = $res->fetch_assoc();
    }
    $res->free();
    $stmt->close();
    flush_stored_results($con);
}
?>

<div class="">
    <!-- Row 1: Heading -->
    <div class="mb-3">
        <h4 class="mb-0" style="color:var(--teal)">Manage Privacy Policy</h4>
        <p class="text-muted">Update your privacy policy content</p>
    </div>

    <div class="table-card">
        <?php if ($privacy_data): ?>
            <form method="POST" action="admin_privacypolicy.php">
                <input type="hidden" name="id" value="<?= $privacy_data['id'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">Page Title</label>
                    <input name="page_title" class="form-control"
                        value="<?= htmlspecialchars($privacy_data['page_title']) ?>"
                        data-validation="required">
                    <span class="error text-danger" id="page_titleError"></span>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Privacy Policy Content</label>
                    <textarea name="page_content" id="page_content" class="form-control" rows="5"
                        data-validation="required"><?= htmlspecialchars($privacy_data['page_content']) ?></textarea>
                    <span class="error text-danger" id="page_contentError"></span>
                    <small class="text-muted">You can use HTML tags for formatting (e.g., &lt;h4&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;strong&gt;)</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select" data-validation="required">
                        <option value="Active" <?= $privacy_data['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $privacy_data['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <span class="error text-danger" id="statusError"></span>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Last Updated</label>
                    <p><?= date('M d, Y h:i A', strtotime($privacy_data['updated_at'])) ?></p>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="update_privacy" class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;">
                        <i class="fa fa-save me-1"></i> Update Privacy Policy
                    </button>
                    <a href="privacy.php" target="_blank" class="btn btn-outline-secondary" style="font-size:small;">
                        <i class="fa fa-eye me-1"></i> Preview Page
                    </a>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle me-2"></i> Privacy policy data not found. Please contact administrator.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content_admin = ob_get_clean();
include 'admin_layout.php';
?>