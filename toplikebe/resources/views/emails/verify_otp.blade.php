<!DOCTYPE html>
<html>

<head>
    <title>Verify Your Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #a855f7;
        }

        .content {
            margin-bottom: 30px;
            color: #333333;
            line-height: 1.6;
        }

        .otp-box {
            background: #f3e8ff;
            border: 1px dashed #a855f7;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 5px;
            color: #7e22ce;
            margin: 20px 0;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #888888;
            margin-top: 30px;
            border-top: 1px solid #eeeeee;
            padding-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">TopLike</div>
        </div>

        <div class="content">
            <p>Hello {{ $user->name }},</p>
            <p>Welcome to TopLike! To complete your registration and verify your account, please use the One-Time
                Password (OTP) below:</p>

            <div class="otp-box">
                {{ $otp }}
            </div>

            <p>This code will expire in 15 minutes. If you did not sign up for a TopLike account, please ignore this
                email.</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} TopLike. All rights reserved.
        </div>
    </div>
</body>

</html>