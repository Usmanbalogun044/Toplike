<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
</head>
<body style="background-color: #f3f4f6; padding: 40px; font-family: Arial, sans-serif;">
    <div style="max-width: 600px; margin: auto; background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);">

        <!-- Logo Section -->
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="https://venueri.com/assets/images/g3.png" alt="{{ $title_site }}" style="width: 120px; height: auto; border-radius: 8px;">
            <h2 style="color: #1e293b; margin-top: 10px;">{{ $title_site }}</h2>
        </div>

        <!-- Greeting -->
        <h3 style="color: #374151; text-align: center;">Hello, {{ $name }} 👋</h3>
        <p style="color: #6b7280; text-align: center; font-size: 16px;">
            You're almost there! Please confirm your email to activate your account.
        </p>

        <!-- Verification Button -->
        <div style="text-align: center; margin-top: 20px;">
            <a href="{{ $verification_link }}" style="display: inline-block; padding: 12px 24px; background: #3b82f6; color: #ffffff; text-decoration: none; font-size: 16px; border-radius: 6px; font-weight: bold;">
                Verify Email
            </a>
        </div>

        <!-- Alternative Link -->
        <p style="color: #6b7280; font-size: 14px; text-align: center; margin-top: 20px;">
            If the button above doesn’t work, copy and paste the following link in your browser:
        </p>
        <p style="word-wrap: break-word; text-align: center; font-size: 14px; color: #1e40af;">
            <a href="{{ $verification_link }}" style="color: #1e40af; text-decoration: underline;">
                {{ $verification_link }}
            </a>
        </p>

        <!-- Footer -->
        <div style="margin-top: 30px; text-align: center; color: #9ca3af; font-size: 12px;">
            <p>Need help? Contact our support team.</p>
            <p>© {{ date('Y') }} {{ $title_site }}. All rights reserved.</p>
        </div>

    </div>
</body>
</html>
