<?php
include_once 'db_config.php';
if (isset($_POST['otp_btn'])) {

    if (isset($_SESSION['forgot_email'])) {
        $email = $_SESSION['forgot_email'];
        $otp = $_POST['otp'];
        $stmt = $con->prepare("CALL PasswordToken_Select(?)");
        if ($stmt == false) {
            die('Prepare failed: ' . $con->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result1 = $stmt->get_result();
        $users = $result1->fetch_all(MYSQLI_ASSOC);

        if ($result1->num_rows > 0) {
            // $row = mysqli_fetch_assoc($result);
            $db_otp = $users[0]['otp'];
            if (!$db_otp) {
                setcookie('error', 'OTP has expired. Regenerate New OTP', time() + 5, '/');
?>
                <script>
                    window.location.href = 'forgot_password.php';
                </script>
                <?php
            }
            // Compare the OTPs
            else {
                if ($otp == $db_otp) {
                    // Redirect to new password page
                ?>
                    <script>
                        window.location.href = 'reset_password.php';
                    </script>
                <?php

                } else {
                    setcookie('error', 'Incorrect OTP', time() + 5, '/');
                ?>

                    <script>
                        window.location.href = 'otp_form.php';
                    </script>
            <?php
                }
            }
        } else {
            setcookie('error', 'OTP has expired. Regenerate New OTP', time() + 5, '/');
            ?>
            <script>
                window.location.href = 'forgot_password.php';
            </script>
<?php
        }
    }
}

ob_start();
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-7">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">

                <!-- Header Banner -->
                <div class="text-center py-4" style="background-color:#0d9488;">
                    <h3 class="fw-bold text-white mb-0"><i class="fa-solid fa-key me-2"></i>OTP Verification</h3>
                </div>

                <!-- Form -->
                <div class="card-body p-4">
                    <p class="text-center mb-4" style="color:#0d9488;">
                        Enter the OTP sent to your email address.
                    </p>

                    <form id="otpForm" method="post" action="otp_form.php">
                        <div class="mb-3">
                            <label for="otp" class="form-label fw-semibold">One-Time Password (OTP)</label>
                            <input type="text" class="form-control border-2 border-teal" id="otp" name="otp" placeholder="Enter the OTP" data-validation="required number">
                            <span class="error text-danger" id="otpError"></span>
                        </div>
                        <div id="timer" class="text-danger"></div>
                        <br>
                        <div class="d-flex justify-content-center mb-4" name="otp">
                            <button type="button" id="resend_otp" class="btn text-white fw-semibold" style="background-color:#0d9488;">Resend OTP
                            </button>
                        </div>
                        <script>
                            let timeLeft = 120; // Default timer value
                            const timerDisplay = document.getElementById('timer');
                            const resendButton = document.getElementById('resend_otp');

                            let countdown; // <-- FIX 1: Make the interval ID global

                            // Function to check if the user is refreshing or coming from another page
                            function isPageRefresh() {
                                return !!sessionStorage.getItem('otpTimer'); // If otpTimer exists, it's a refresh
                            }

                            // If the page is refreshed, use sessionStorage value
                            if (isPageRefresh()) {
                                timeLeft = parseInt(sessionStorage.getItem('otpTimer'), 10);
                            } else {
                                // If the user comes from another page, reset timer
                                sessionStorage.setItem('otpTimer', 120);
                                timeLeft = 120;
                            }

                            function startCountdown() {
                                resendButton.style.display = "none"; // Hide the button initially
                                timerDisplay.innerHTML = `Resend OTP in ${timeLeft} seconds`;

                                // Assign the interval to the global 'countdown' variable
                                countdown = setInterval(() => {
                                    if (timeLeft <= 0) {
                                        clearInterval(countdown);
                                        timerDisplay.innerHTML = "You can now resend the OTP.";
                                        resendButton.style.display = "inline";
                                        sessionStorage.removeItem('otpTimer'); // Clear sessionStorage after timer ends
                                    } else {
                                        timerDisplay.innerHTML = `Resend OTP in ${timeLeft} seconds`;
                                        timeLeft -= 1;
                                        sessionStorage.setItem('otpTimer', timeLeft); // Update sessionStorage
                                    }
                                }, 1000);
                            }

                            // Start countdown only if the timer is above 0
                            if (timeLeft > 0) {
                                startCountdown();
                            } else {
                                resendButton.style.display = "inline";
                                timerDisplay.innerHTML = "You can now resend the OTP.";
                            }

                            resendButton.onclick = function(event) {
                                event.preventDefault(); // Prevent default form submission

                                // <-- FIX 2: Stop the timer IMMEDIATELY!
                                clearInterval(countdown);

                                sessionStorage.setItem('otpTimer', 120); // Reset timer
                                window.location.href = 'resend_otp_forgot_password.php';
                            };
                        </script>
                        <div class="d-grid mt-3">
                            <button type="submit" class="btn text-white fw-semibold" style="background-color:#0d9488;" name="otp_btn">Verify OTP</button>
                        </div>
                    </form>
                </div>
                <!-- Footer Banner -->
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include 'layout.php';
?>