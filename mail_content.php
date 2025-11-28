<html>

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