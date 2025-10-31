{{-- <!DOCTYPE html>
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

            .content h2 {
                color: #333;
                margin-top: 0;
            }

            .button {
                display: inline-block;
                padding: 12px 30px;
                background: #dc3545;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
                font-weight: bold;
            }

            .button:hover {
                background: #c82333;
            }

            .token-box {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                padding: 15px;
                margin: 15px 0;
                font-family: monospace;
                font-size: 16px;
                word-break: break-all;
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
                <h1>🔒 Password Reset Request</h1>
            </div>

            <div class="content">
                <h2>Hello, {{ $userName }}!</h2>

                <p>We received a request to reset your password. If you didn't make this request, you can safely ignore
                    this email.</p>

                <p>To reset your password, click the button below:</p>

                <div style="text-align: center;">
                    <a href="{{ $resetUrl }}" class="button">Reset Password</a>
                </div>

                <p>Or copy and paste this link into your browser:</p>
                <div class="token-box">
                    {{ $resetUrl }}
                </div>

                <div class="warning">
                    ⏰ <strong>Important:</strong> This password reset link will expire in {{ $expiryMinutes }} minutes.
                </div>

                <p>If you're having trouble clicking the button, copy and paste the URL above into your web browser.</p>

                <p>For security reasons:</p>
                <ul>
                    <li>Never share this link with anyone</li>
                    <li>We will never ask for your password via email</li>
                    <li>If you didn't request this, please contact support immediately</li>
                </ul>

                <p>Best regards,<br>
                    <strong>{{ config('app.name') }} Security Team</strong>
                </p>
            </div>

            <div class="footer">
                <p>This is an automated email. Please do not reply to this message.</p>
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </body>

</html> --}}
