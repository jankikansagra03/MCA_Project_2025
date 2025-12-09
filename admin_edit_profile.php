<?php
include_once 'db_config.php';
include_once 'admin_authentication.php';

if (!isset($_SESSION['admin_email'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_email = $_SESSION['admin_email'];
$admin_data = null;

// =======================
// FETCH ADMIN DATA
// =======================
$select_stmt = $con->prepare("CALL Registration_Select(NULL, ?, NULL,NULL,NULL,NULL,NULL)");
if ($select_stmt === false) {
    die("Database error: " . $con->error);
}

$select_stmt->bind_param("s", $admin_email);
$select_stmt->execute();
$result = $select_stmt->get_result();

if ($result && $result->num_rows > 0) {
    $admin_data = $result->fetch_assoc();
}

$result->free();
$select_stmt->close();

// Flush stored results
while ($con->more_results()) {
    $con->next_result();
    if ($res = $con->store_result()) {
        $res->free();
    }
}

if (!$admin_data) {
    setcookie("error", "Admin not found.", time() + 5, '/');
    header("Location: admin_login.php");
    exit();
}

// =======================
// UPDATE PROFILE
// =======================
if (isset($_POST['updateProfileBtn'])) {
    $fullname = trim($_POST['fullname']);
    $mobile = trim($_POST['mobile']);
    $gender = trim($_POST['gender']);
    $address = trim($_POST['address']);

    // Validate inputs
    if (empty($fullname) || empty($mobile) || empty($gender)) {
        setcookie("error", "Please fill all required fields.", time() + 5, '/');
?>
        <script>
            window.location.href = 'admin_edit_profile.php';
        </script>
    <?php
        exit();
    }

    // Validate mobile number
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        setcookie("error", "Mobile number must be 10 digits.", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'admin_edit_profile.php';
        </script>
    <?php
        exit();
    }

    // NULL values for fields we don't want to update
    $password = NULL;
    $profile_picture = NULL;
    $status = NULL;
    $role = NULL;

    $update_stmt = $con->prepare("CALL Registration_Update(?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($update_stmt === false) {
        setcookie("error", "Database error: " . $con->error, time() + 5, '/');
    ?>
        <script>
            window.location.href = 'admin_edit_profile.php';
        </script>
    <?php
        exit();
    }

    $update_stmt->bind_param("sssisssss", $fullname, $admin_email, $password, $mobile, $gender, $profile_picture, $address, $status, $role);

    if ($update_stmt->execute()) {
        $update_stmt->close();

        // Flush stored results
        while ($con->more_results()) {
            $con->next_result();
            if ($res = $con->store_result()) {
                $res->free();
            }
        }

        setcookie("success", "Profile updated successfully!", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'admin_edit_profile.php';
        </script>
    <?php
        exit();
    } else {
        setcookie("error", "Failed to update profile: " . $update_stmt->error, time() + 5, '/');
    ?>
        <script>
            window.location.href = 'admin_edit_profile.php';
        </script>
<?php
        exit();
    }
}

ob_start();
?>

<div class="">
    <!-- Heading -->
    <div class="mb-4">
        <h4 class="mb-0" style="color:var(--teal)">
            <i class="fa fa-user-edit me-2"></i>Edit Profile
        </h4>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header" style="background-color:#0d9488; color:white;">
                    <h5 class="mb-0">
                        <i class="fa fa-user me-2"></i>Update Your Information
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="admin_edit_profile.php">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Full Name</label>
                                <input type="text"
                                    name="fullname"
                                    class="form-control"
                                    value="<?= htmlspecialchars($admin_data['fullname']) ?>"
                                    placeholder="Enter full name"
                                    required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email (cannot be changed)</label>
                                <input type="email"
                                    class="form-control"
                                    value="<?= htmlspecialchars($admin_data['email']) ?>"
                                    readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Mobile Number</label>
                                <input type="text"
                                    name="mobile"
                                    class="form-control"
                                    value="<?= htmlspecialchars($admin_data['mobile']) ?>"
                                    placeholder="Enter 10-digit mobile number"
                                    pattern="[0-9]{10}"
                                    maxlength="10"
                                    required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Gender</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?= $admin_data['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $admin_data['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $admin_data['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Address</label>
                                <textarea name="address"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Enter your address"><?= htmlspecialchars($admin_data['address']) ?></textarea>
                            </div>

                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit"
                                name="updateProfileBtn"
                                class="btn text-white fw-semibold"
                                style="background-color:#0d9488;font-size:small;">
                                <i class="fa fa-save me-2"></i>Update Profile
                            </button>
                            <a href="admin_dashboard.php" class="btn btn-outline-secondary" style="font-size:small;">
                                <i class="fa fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content_admin = ob_get_clean();
include 'admin_layout.php';
?>