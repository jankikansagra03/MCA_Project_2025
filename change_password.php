<?php
session_start();
include_once 'db_config.php';
include 'user_authentication.php'; // Ensure user is logged in

// =======================
// UPDATE PASSWORD
// =======================
if (isset($_POST['updatePasswordBtn'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $user_email = $_SESSION['user_email'];

    // Validate passwords match
    if ($new_password !== $confirm_password) {
        setcookie("error", "New passwords do not match.", time() + 5, '/');
?>
        <script>
            window.location.href = 'change_password.php';
        </script>
    <?php
        exit();
    }

    // Validate password length
    if (strlen($new_password) < 8 || strlen($new_password) > 25) {
        setcookie("error", "Password must be between 8-25 characters.", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'change_password.php';
        </script>
    <?php
        exit();
    }

    // =======================
    // FETCH CURRENT USER DATA
    // =======================
    $user_data = null;
    $select_stmt = $con->prepare("CALL Registration_Select(NULL, ?)");

    if ($select_stmt === false) {
        setcookie("error", "Database error: " . $con->error, time() + 5, '/');
    ?>
        <script>
            window.location.href = 'change_password.php';
        </script>
    <?php
        exit();
    }

    $select_stmt->bind_param("s", $user_email);
    $select_stmt->execute();
    $result = $select_stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
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

    // Check if user found
    if (!$user_data) {
        setcookie("error", "User not found.", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'login.php';
        </script>
    <?php
        exit();
    }

    // =======================
    // VERIFY CURRENT PASSWORD
    // =======================
    $db_password = $user_data['password'];

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
            window.location.href = 'change_password.php';
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
            window.location.href = 'change_password.php';
        </script>
    <?php
        exit();
    }

    $update_stmt->bind_param("sssisssss", $name, $user_email, $hashed_password, $phone, $gender, $profile_picture, $address, $status, $role);

    if ($update_stmt->execute()) {
        $update_stmt->close();

        // Flush stored results
        while ($con->more_results()) {
            $con->next_result();
            if ($res = $con->store_result()) {
                $res->free();
            }
        }

        setcookie("success", "Password updated successfully. Please login again.", time() + 5, '/');

        // Logout user for security
        session_destroy();
    ?>
        <script>
            window.location.href = 'login.php';
        </script>
    <?php
    } else {
        setcookie("error", "Error updating password.", time() + 5, '/');
    ?>
        <script>
            window.location.href = 'change_password.php';
        </script>
<?php
    }

    exit();
}

ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">

                <!-- Header -->
                <div class="text-center py-4" style="background-color:#0d9488;font-size:small;">
                    <h4 class="fw-bold text-white mb-0">
                        <i class="fa-solid fa-key me-2"></i> Change Password
                    </h4>
                </div>

                <!-- Form -->
                <form method="post" action="change_password.php" id="changePasswordForm">
                    <div class="card-body p-4">

                        <!-- Password Requirements -->
                        <div class="alert alert-info" role="alert">
                            <i class="fa-solid fa-info-circle me-2"></i>
                            <strong>Password Requirements:</strong>
                            <ul class="mb-0 mt-2 small">
                                <li>Minimum 8 characters, maximum 25 characters</li>
                                <li>Include uppercase and lowercase letters</li>
                                <li>Include at least one number</li>
                                <li>Include at least one special character (!@#$%^&*)</li>
                            </ul>
                        </div>

                        <!-- Current Password -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold">
                                <i class="fa-solid fa-lock me-1"></i> Current Password
                            </label>
                            <div class="input-group">
                                <input type="password"
                                    class="form-control border-2"
                                    style="border-color:#0d9488;"
                                    id="current_password"
                                    name="current_password"
                                    placeholder="Enter current password"
                                    data-validation="required"
                                    autocomplete="current-password">
                                <button type="button"
                                    class="btn btn-outline-secondary" style="font-size:small;"
                                    onclick="togglePassword('current_password', this)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <div class="error text-danger" id="current_passwordError"></div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-semibold">
                                <i class="fa-solid fa-key me-1"></i> New Password
                            </label>
                            <div class="input-group">
                                <input type="password"
                                    class="form-control border-2"
                                    style="border-color:#0d9488;"
                                    id="new_password"
                                    name="new_password"
                                    placeholder="Enter new password"
                                    data-validation="required strongPassword min max"
                                    data-min="8"
                                    data-max="25"
                                    autocomplete="new-password">
                                <button type="button"
                                    class="btn btn-outline-secondary" style="font-size:small;"
                                    onclick="togglePassword('new_password', this)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <div class="error text-danger" id="new_passwordError"></div>
                            <div class="form-text mt-1">
                                <small id="password-strength" class="text-muted">Password strength will appear here</small>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label fw-semibold">
                                <i class="fa-solid fa-check-double me-1"></i> Confirm New Password
                            </label>
                            <div class="input-group">
                                <input type="password"
                                    class="form-control border-2"
                                    style="border-color:#0d9488;"
                                    id="confirm_password"
                                    name="confirm_password"
                                    placeholder="Re-enter new password"
                                    data-validation="required confirmPassword"
                                    data-password-id="new_password"
                                    autocomplete="new-password">
                                <button type="button"
                                    class="btn btn-outline-secondary" style="font-size:small;"
                                    onclick="togglePassword('confirm_password', this)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <div class="error text-danger" id="confirm_passwordError"></div>
                            <div id="password-match-message" class="mt-1"></div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit"
                                class="btn text-white fw-semibold"
                                style="background-color:#0d9488;font-size:small;"
                                name="updatePasswordBtn">
                                <i class="fa-solid fa-save me-2"></i> Update Password
                            </button>
                            <a href="user_dashboard.php"
                                class="btn btn-outline-secondary" style="font-size:small;">
                                <i class="fa-solid fa-arrow-left me-2"></i> Back to Dashboard
                            </a>
                        </div>

                    </div>
                </form>
                <!-- End Form -->

            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    // Toggle Password Visibility
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

    // Password Strength Checker
    document.getElementById('new_password').addEventListener('input', function() {
        const password = this.value;
        const strengthDisplay = document.getElementById('password-strength');

        let strength = 0;
        let message = '';
        let color = '';

        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[@$!%*?&#]/)) strength++;

        switch (strength) {
            case 0:
            case 1:
                message = 'Very Weak';
                color = 'text-danger';
                break;
            case 2:
                message = 'Weak';
                color = 'text-warning';
                break;
            case 3:
                message = 'Fair';
                color = 'text-info';
                break;
            case 4:
                message = 'Good';
                color = 'text-primary';
                break;
            case 5:
                message = 'Strong';
                color = 'text-success';
                break;
        }

        strengthDisplay.textContent = 'Password Strength: ' + message;
        strengthDisplay.className = color + ' fw-bold';
    });

    // Password Match Checker (Real-time)
    document.getElementById('confirm_password').addEventListener('input', function() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = this.value;
        const messageDiv = document.getElementById('password-match-message');

        if (confirmPassword === '') {
            messageDiv.innerHTML = '';
            return;
        }


    });

    // Also check when new password changes
    document.getElementById('new_password').addEventListener('input', function() {
        const confirmPassword = document.getElementById('confirm_password').value;
        if (confirmPassword !== '') {
            document.getElementById('confirm_password').dispatchEvent(new Event('input'));
        }
    });
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>