<?php
session_start();
include_once 'db_config.php';
include 'mailer.php';

if (isset($_SESSION['forgot_email'])) {
    $email = $_SESSION['forgot_email'];

    $stmt_get = $con->prepare("CALL PasswordToken_Select(?)");
    if ($stmt_get === false) {
        die("Prepare failed (GetToken): " . $con->error);
    }

    $stmt_get->bind_param("s", $email);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    $row = $result->fetch_assoc();

    // Clean up the first call
    $result->free();
    $stmt_get->close();
    flush_stored_results($con);


    if (!$row) {
        // This should not happen if they are on this page, but it's a good safeguard.
        setcookie('error', "No password reset request found. Please try again.", time() + 5, "/");
        echo "<script>window.location.href = 'forgot_password.php';</script>";
        exit();
    }

    $attempts = $row['otp_attempts'];

    // Block further resends after 3 attempts
    if ($attempts >= 3) {
        setcookie('error', "OTP resend limit reached. You can generate a new OTP after 24 hours.", time() + 5, "/");
?>
        <script>
            window.location.href = 'login.php';
        </script>
        <?php
        exit();
    }

    // If limit is not reached, generate new data
    $email_time = date("Y-m-d H:i:s");
    $expiry_time = date("Y-m-d H:i:s", strtotime('+2 minutes'));
    $new_otp = rand(100000, 999999);

    // --- CONVERTED OPERATION 2: Update the token ---
    $stmt_update = $con->prepare("CALL PasswordToken_Update(?, ?, ?, ?, ?)");
    if ($stmt_update === false) {
        die("Prepare failed (Update): " . $con->error);
    }
    $attempts += 1;
    // Bind the 4 parameters for this procedure
    $stmt_update->bind_param("sisss", $email, $new_otp, $email_time, $expiry_time, $attempts);

    if ($stmt_update->execute()) {
        $stmt_update->close(); // Close the statement
        while ($con->more_results()) {
            $con->next_result();
        }
        $to = $email;
        $subject = "Reset password";
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

</html>
            ";

        if (sendEmail($to, $subject, $body, "")) {
            setcookie("success", "A new OTP has been sent successfully.", time() + 5, "/");
        ?>
            <script>
                window.location.href = "otp_form.php";
            </script>
        <?php
        } else {
            setcookie("error", "Error in sending the new OTP.", time() + 5, "/");
        ?>
            <script>
                window.location.href = "forgot_password.php";
            </script>
        <?php
        }
    } else {
        // This 'else' catches a database update failure
        setcookie("error", "Error updating token in database.", time() + 5, "/");
        ?>
        <script>
            window.location.href = "forgot_password.php";
        </script>
<?php
    }

    exit(); // Make sure the script stops
} else {
    // If session is not set, redirect to login
    header("Location: login.php");
    exit();
}
?>