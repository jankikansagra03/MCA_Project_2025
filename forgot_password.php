<?php
include 'mailer.php';
include_once 'db_config.php';

$db_success = false;
$send_email = false;
$redirect_url = null;

if (isset($_POST['send_otp'])) {
    $email = $_POST['email'];

    $stmt_get = $con->prepare("CALL  PasswordToken_Select(?)");
    if ($stmt_get === false) {
        die("Prepare failed: " . $con->error);
    }

    $stmt_get->bind_param("s", $email);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    $token_data = $result->fetch_assoc(); // Get the one row (or null)
    $result->free();
    $stmt_get->close();

    flush_stored_results($con);

    $new_otp = rand(100000, 999999);
    $email_time = date("Y-m-d H:i:s");
    $expiry_time = date("Y-m-d H:i:s", strtotime('+2 minutes'));


    if ($token_data) {

        $attempts = $token_data['otp_attempts'];
        $otp = $token_data['otp'];

        if ($attempts >= 3) {
            setcookie('error', "Maximum OTP limit reached. Please try again after 24 hours.", time() + 5, "/");
            $redirect_url = "login.php";
        } else {
            $attempts += 1;

            $stmt3 = $con->prepare("CALL PasswordToken_Update(?, ?, ?, ?, ?)");
            if ($stmt3 === false) {
                die("Prepare failed: " . $con->error);
            }

            $stmt3->bind_param("sissi", $email, $new_otp, $email_time, $expiry_time, $attempts);

            if ($stmt3->execute()) {
                $db_success = true;
                $send_email = true; // Mark email for sending
            }
            $stmt3->close();
        }
    } else {

        $attempts = 0; // Set attempts to 0

        $stmt2 = $con->prepare("CALL PasswordToken_Insert(?, ?, ?, ?, ?)");
        if ($stmt2 === false) {
            die("Prepare failed: " . $con->error);
        }

        $stmt2->bind_param("sissi", $email, $new_otp, $email_time, $expiry_time, $attempts);

        if ($stmt2->execute()) {
            $db_success = true;
            $send_email = true; // Mark email for sending
        }
        $stmt2->close();
    }

    // --- 3. SEND EMAIL (if needed) ---
    if ($db_success && $send_email) {
        $subject = "Password Reset - OTP";
        $body = "<html>

<head>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f7f7;
            padding: 0;
            margin: 0;
        }

        .email-wrapper {
            max-width: 620px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .header {
            background: #0d9488;
            padding: 18px;
            text-align: center;
            color: white;
            font-size: 22px;
            font-weight: 700;
        }

        .content {
            padding: 25px 30px;
            color: #0f3d3a;
            font-size: 16px;
        }

        .otp-box {
            margin: 20px auto;
            padding: 14px 0;
            width: 60%;
            text-align: center;
            background: #d4af37;
            border-radius: 8px;
            color: white;
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 5px;
        }

        .note {
            font-size: 14px;
            color: #d97706;
            margin-top: 10px;
        }

        .footer {
            margin-top: 24px;
            padding: 16px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            background: #f1f5f9;
        }
    </style>
</head>

<body>
    <div class='email-wrapper'>

        <div class='header'>
            Password Reset Request
        </div>

        <div class='content'>
            <p>Hello,</p>

            <p>We received a request to reset the password associated with your account.</p>

            <p>Please use the following One-Time Password (OTP) to continue:</p>

            <div class='otp-box'>
                $new_otp
            </div>

            <p>This OTP is valid for the next <b>2 minutes</b>.</p>

            <p>If you did not request a password reset, please ignore this email.</p>

            <p class='note'><b>Note:</b> Do not share this OTP with anyone.</p>
        </div>

        <div class='footer'>
            This is an automated email. Please do not reply.
        </div>

    </div>
</body>

</html>";

        if (sendEmail($email, $subject, $body, "")) {
            $_SESSION['forgot_email'] = $email;
            setcookie('success', 'OTP sent! Please check your email (and spam folder).', time() + 5, "/");
            $redirect_url = "otp_form.php";
        } else {
            setcookie('error', 'Failed to send the email. Please try again.', time() + 5, "/");
            $redirect_url = "forgot_password.php";
        }
    } else if ($db_success == false && $redirect_url == null) {
        // This catches if the DB insert/update failed
        setcookie('error', 'Failed to update the database. Please try again.', time() + 5, "/");
        $redirect_url = "forgot_password.php";
    }

    // --- 4. REDIRECT ---
    if ($redirect_url) {
        echo "<script>window.location.href = '$redirect_url';</script>";
        exit();
    }
}

// Start the HTML buffer
ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-7">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="text-center py-4" style="background-color:#0d9488;font-size:small;">
                    <h4 class="fw-bold text-white mb-0"><i class="fa-solid fa-key me-2"></i>Forgot Password</h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-center mb-4" style="color:#0d9488;">
                        Enter your registered email address to reset your password.
                    </p>
                    <form id="forgotForm" method="post" action="forgot_password.php">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control border-2 border-teal" id="email" name="email" placeholder="Enter your email" data-validation="required email">
                            <span class="error text-danger" id="emailError" style="font-size: 0.9em;"></span>
                        </div>
                        <div>
                            <p class="text-muted mb-0" style="font-size: 0.9rem;">
                                An OTP will be sent to your email for verification.</p>
                            <p>
                                <span class="text-danger" style="font-size: 0.9rem;">Note:</span> Please check your spam/junk folder if you do not see the email in your inbox.
                            </p>
                        </div>
                        <div class="d-grid mt-3">
                            <button type="submit" class="btn text-white fw-semibold" style="background-color:#0d9488;font-size:small;" name="send_otp" id="sendOtpButton">Send OTP</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <p class="mb-0">Remembered your password?
                            <a href="login.php" class="fw-semibold text-decoration-none" style="color:#0d9488;">Login Now</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>

<script>
    $(document).ready(function() {
        $('#email').on('blur', function() {
            var email = $(this).val();
            if (email.length == 0) {
                $('#emailError').text('');
                $('#email').removeClass('is-invalid');
                $('#sendOtpButton').prop('disabled', false); // Enable button
                return;
            }
            $.ajax({
                type: 'GET',
                url: 'check_duplicate_Email.php',
                data: {
                    email1: email
                },
                success: function(response) {
                    var responseTrimmed = response.trim();

                    // We assume 'check_duplicate_Email.php' returns:
                    // 'true'  = Email is NOT registered (available)
                    // 'false' = Email IS registered (duplicate)

                    if (responseTrimmed == 'false') {
                        // 'true' means NOT registered. This is an error for forgot password.
                        $('#emailError').text('Email is not registered. Please enter a registered email address.').show();
                        $('#email').addClass('is-invalid');
                        $('#sendOtpButton').prop('disabled', true); // Disable button
                    } else if (responseTrimmed == 'true') {
                        // 'false' means IS registered. This is good.
                        $('#emailError').text('').hide();
                        $('#email').removeClass('is-invalid');
                        $('#sendOtpButton').prop('disabled', false); // Enable button
                    } else {
                        // Handle unexpected response
                        $('#emailError').text('Error validating email.').show();
                        $('#sendOtpButton').prop('disabled', true);
                    }
                }
            });
        });
    });
</script>