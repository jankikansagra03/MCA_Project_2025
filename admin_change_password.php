<?php
include_once 'db_config.php';
include_once 'admin_authentication.php';

$admin_email = $_SESSION['admin_email'];

// =======================
// UPDATE PASSWORD
// =======================
if (isset($_POST['updatePasswordBtn'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate passwords match
    if ($new_password !== $confirm_password) {
        setcookie("error", "New passwords do not match.", time() + 5, '/');
?>
        <script>
            window.location.href = 'admin_change_password.php';
        </script>
    <?php
        exit();
    }

    // Validate password length
    if (strlen($new_password) < 8 || strlen($new_password) > 25) {
        setcookie("error", "Password must be between 8-25 characters.", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'admin_change_password.php';
        </script>
    <?php
        exit();
    }

    // =======================
    // FETCH CURRENT ADMIN DATA
    // =======================
    $admin_data = null;
    $select_stmt = $con->prepare("CALL Registration_Select(NULL, ?)");

    if ($select_stmt === false) {
        setcookie("error", "Database error: " . $con->error, time() + 5, '/');
    ?>
        <script>
            window.location.href = 'admin_change_password.php';
        </script>
    <?php
        exit();
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

    // Check if admin found
    if (!$admin_data) {
        setcookie("error", "Admin not found.", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'admin_login.php';
        </script>
    <?php
        exit();
    }

    // =======================
    // VERIFY CURRENT PASSWORD
    // =======================
    $db_password = $admin_data['password'];

    // Check if password is hashed or plain text
    $password_verified = false;
    if (password_get_info($db_password)['algo']) {
        // Password is hashed - use password_verify
        $password_verified = password_verify($current_password, $db_password);
    } else {
        // Password is plain text - direct comparison
        $password_verified = ($db_password === $current_password);
    }

    if (!$password_verified) {
        setcookie("error", "Current password is incorrect.", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'admin_change_password.php';
        </script>
    <?php
        exit();
    }

    // =======================
    // UPDATE PASSWORD
    // =======================
    // Hash new password for security
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Prepare NULL values for fields we don't want to update
    $name = NULL;
    $phone = NULL;
    $gender = NULL;
    $profile_picture = NULL;
    $address = NULL;
    $status = NULL;
    $role = NULL;

    $update_stmt = $con->prepare("CALL Registration_Update(?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($update_stmt === false) {
        setcookie("error", "Database error: " . $con->error, time() + 5, '/');
    ?>
        <script>
            window.location.href = 'admin_change_password.php';
        </script>
    <?php
        exit();
    }

    $update_stmt->bind_param("sssisssss", $name, $admin_email, $hashed_password, $phone, $gender, $profile_picture, $address, $status, $role);

    if ($update_stmt->execute()) {
        $update_stmt->close();

        // Flush stored results
        while ($con->more_results()) {
            $con->next_result();
            if ($res = $con->store_result()) {
                $res->free();
            }
        }

        setcookie("success", "Password changed successfully!", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'admin_change_password.php';
        </script>
    <?php
        exit();
    } else {
        setcookie("error", "Failed to update password: " . $update_stmt->error, time() + 5, '/');
    ?>
        <script>
            window.location.href = 'admin_change_password.php';
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
            <i class="fa fa-key me-2"></i>Change Password
        </h4>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header" style="background-color:#0d9488; color:white;">
                    <h5 class="mb-0">
                        <i class="fa fa-lock me-2"></i>Update Your Password
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="admin_change_password.php">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Current Password</label>
                            <input type="password"
                                name="current_password"
                                id="current_password"
                                class="form-control"
                                placeholder="Enter current password"
                                data-validation="required">
                            <span class="error text-danger" id="current_passwordError"></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Password</label>
                            <input type="password"
                                name="new_password"
                                id="new_password"
                                class="form-control"
                                placeholder="Enter new password (8-25 characters)"
                                data-validation="required strongPassword min max"
                                data-min="8"
                                data-max="25">
                            <span class="error text-danger" id="new_passwordError"></span>

                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password"
                                name="confirm_password"
                                id="confirm_password"
                                class="form-control"
                                placeholder="Confirm new password"
                                data-validation="required confirmPassword"
                                data-password-id="new_password">
                            <span class="error text-danger" id="confirm_passwordError"></span>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit"
                                name="updatePasswordBtn"
                                class="btn text-white fw-semibold"
                                style="background-color:#0d9488;font-size:small;">
                                <i class="fa fa-save me-2"></i>Update Password
                            </button>
                            <a href="admin_dashboard.php" class="btn btn-outline-secondary" style="font-size:small;">
                                <i class="fa fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Password Requirements -->

        </div>
    </div>
</div>

<?php
$content_admin = ob_get_clean();
include 'admin_layout.php';
?>