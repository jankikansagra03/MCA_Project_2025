<?php
session_start();
include 'db_config.php';

if (isset($_POST['reset_pwd_btn'])) {

    if (isset($_SESSION['forgot_email'])) {
        $email = $_SESSION['forgot_email'];


        $new_password = $_POST['npwd'];



        $stmt = $con->prepare("CALL Registration_Update(?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt == false) {
            die('Prepare failed: ' . $con->error);
        }

        $p_email = $email;
        $p_fullname = NULL;
        $p_password = $new_password; // Pass the hash
        $p_role = NULL;
        $p_status = NULL; // Pass real NULL, not the string 'NULL'
        $p_mobile = NULL;
        $p_profile_pic = NULL;

        $stmt->bind_param(
            "sssisssss",

            $p_fullname,
            $p_email,
            $p_password,
            $p_mobile,
            $p_gender,
            $p_profile_pic,
            $p_address,
            $p_status,
            $p_role
        );

        if ($stmt->execute()) {
            $stmt->close();

            $stmt_del = $con->prepare("CALL PasswordToken_Delete(?)");
            if ($stmt_del == false) {
                die('Prepare failed (Delete): ' . $con->error);
            }

            $stmt_del->bind_param("s", $email);
            $stmt_del->execute();
            $stmt_del->close();

            setcookie('success', 'Password has been reset successfully. You can now log in.', time() + 5, '/');
            unset($_SESSION['forgot_email']);
?>
            <script>
                sessionStorage.removeItem('otpTimer');
                window.location.href = 'login.php';
            </script>
<?php

            echo "<script>window.location.href = 'login.php';</script>";
            exit();
        } else {

            setcookie('error', 'Failed to update password. Please try again.', time() + 5, '/');
        }
        $stmt->close();
    } else {
        setcookie('error', 'Session expired. Please start the reset process again.', time() + 5, '/');
    }

    // Redirect if any errors happened
    echo "<script>window.location.href = 'reset_password.php';</script>";
    exit();
}

ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center align-items-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="text-center py-4" style="background-color:#0d9488;">
                    <h3 class="fw-bold text-white mb-0"><i class="fa-solid fa-user-circle me-2"></i>Reset Password</h3>
                </div>

                <div class="card-body p-4">

                    <form id="reset_password_form" method="post" action="reset_password.php">
                        <div class=" mb-3">
                            <label for="np" class="form-label fw-semibold">New Password</label>
                            <input type="password" class="form-control border-2 border-teal" id="np" name="npwd"
                                placeholder="New password" data-validation="required strongPassword min max" data-min="8" data-max="25">
                            <span class="error text-danger" id="npwdError"></span>
                        </div>

                        <div class="mb-3">
                            <label for="cpassword" class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password" class="form-control border-2 border-teal" id="cpassword"
                                name="cpassword" placeholder="Confirm your password" data-validation="required confirmPassword" data-password-id="np">
                            <span class="error text-danger" id="cpasswordError"></span>
                        </div>

                        <button type="submit" class="btn w-100 text-white fw-semibold" style="background-color:#0d9488;"
                            name="reset_pwd_btn">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>