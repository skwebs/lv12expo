<!DOCTYPE html>
<html>

    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
            }

            .container {
                max-width: 600px;
                margin: 20px auto;
                background: white;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .header {
                background: #dc3545;
                color: white;
                padding: 30px 20px;
                text-align: center;
            }

            .header h1 {
                margin: 0;
                font-size: 24px;
            }

            .content {
                padding: 30px;
            }

            .otp-box {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                padding: 15px;
                margin: 20px 0;
                font-family: monospace;
                font-size: 22px;
                text-align: center;
                letter-spacing: 8px;
                font-weight: bold;
            }

            .warning {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 12px;
                margin: 15px 0;
            }

            .footer {
                background: #f8f9fa;
                padding: 20px;
                text-align: center;
                font-size: 12px;
                color: #6c757d;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <div class="header">
                <h1>🔒 Password Reset OTP</h1>
            </div>
            <div class="content">
                <h2>Hello, {{ $userName }}!</h2>
                <p>We received a request to reset your password.</p>
                <p>Use the OTP below to verify your request:</p>
                <div class="otp-box">{{ $otp }}</div>
                <div class="warning">
                    ⏰ <strong>Note:</strong> This OTP will expire in {{ $expiryMinutes }} minutes.
                </div>
                <p>If you didn’t request a password reset, please ignore this email.</p>
                <p>Best regards,<br><strong>{{ config('app.name') }} Security Team</strong></p>
            </div>
            <div class="footer">
                <p>This is an automated email. Please do not reply.</p>
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </body>

</html>
