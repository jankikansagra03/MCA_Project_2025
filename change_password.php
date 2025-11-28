<?php
include 'db_config.php';
include 'user_authentication.php'; // Ensure user is logged in
if (isset($_POST['updatePasswordBtn'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user_email'];
    $fullname = NULL;
    $password = NULL;
    $status = NULL;
    $role = NULL;
    $token = NULL;
    $mobile = NULL;
    $stmt = $con->prepare("CALL Registration_Select(?, ?, ?, ?, ?,?,?)");
    if ($stmt === false) {
        die("Prepare failed: " . $con->error);
    }
    $stmt->bind_param("ssssssi", $fullname, $user_id, $role, $status, $password, $token, $mobile);
    $stmt->execute();
    $result = $stmt->get_result(); // Get the result set
    $users = $result->fetch_all(MYSQLI_ASSOC); // Fetch all matching users

    $stmt->close();

    $db_pass = $users[0]['password'];
    if ($db_pass == $current_password) {
        $update_query = "UPDATE registration SET password='$new_password' WHERE email='$user_id'";
        if (mysqli_query($con, $update_query)) {
            setcookie("success", "Password updated successfully.", time() + 5);
?>
            <script>
                window.location.href = 'user_dashboard.php';
            </script>
        <?php
        } else {
            setcookie("error", "Error in updating password.", time() + 5);
        ?>
            <script>
                window.location.href = 'change_password.php';
            </script>
        <?php
        }
    } else {
        setcookie("error", "Incorrect Current Password", time() + 5);
        ?>
        <script>
            // window.location.href = 'change_password.php';
        </script>
<?php
    }
}
ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">

                <!-- Header -->
                <div class="text-center py-4" style="background-color:#0d9488;">
                    <h3 class="fw-bold text-white mb-0">
                        <i class="fa-solid fa-key me-2"></i> Change Password
                    </h3>
                </div>

                <!-- Form -->
                <form method="post" action="change_password.php">
                    <div class="card-body p-4">

                        <!-- Current Password -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control border-2 border-teal" id="current_password"
                                    name="current_password" placeholder="Enter current password"
                                    data-validation="required">
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePassword('current_password', this)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <div class="error text-danger" id="current_passwordError"></div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-semibold">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control border-2 border-teal" id="new_password"
                                    name="new_password" placeholder="Enter new password"
                                    data-validation="required strongPassword min max" data-min="8" data-max="25">
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePassword('new_password', this)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <div class="error text-danger" id="new_passwordError"></div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label fw-semibold">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control border-2 border-teal" id="confirm_password"
                                    name="confirm_password" placeholder="Re-enter new password"
                                    data-validation="required confrimPassword" data-password-id="new_password">
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePassword('confirm_password', this)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <div class="error text-danger" id="confirm_passwordError"></div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn text-white fw-semibold" style="background-color:#0d9488;"
                                name="updatePasswordBtn">
                                <i class="fa-solid fa-save me-1"></i> Update Password
                            </button>
                        </div>

                        <div class="mt-3 text-center small text-muted">
                            <a href="user_dashboard.php" class="text-decoration-none" style="color:#0d9488;"><i
                                    class="fa-solid fa-arrow-left me-1"></i>Back to Profile</a>
                        </div>

                    </div>
                </form>
                <!-- End Form -->

            </div>
        </div>
    </div>
</div>

<!-- Toggle Password Script -->
<script>
    function togglePassword(fieldId, btn) {
        const input = document.getElementById(fieldId);
        const icon = btn.querySelector("i");
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>