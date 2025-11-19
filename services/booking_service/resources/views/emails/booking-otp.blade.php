<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking OTP Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .otp-box {
            background-color: #3498db;
            color: white;
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            letter-spacing: 8px;
            margin: 30px 0;
        }
        .info {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning {
            color: #e74c3c;
            font-size: 14px;
            text-align: center;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            color: #7f8c8d;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Booking Verification</h1>
            <p>SJP University Resource Booking System</p>
        </div>

        <p>Hello,</p>
        
        <p>Thank you for booking with us. Please use the following OTP code to verify your booking:</p>

        <div class="otp-box">
            {{ $otpCode }}
        </div>

        @if($bookingReference)
        <div class="info">
            <strong>Booking Reference:</strong> {{ $bookingReference }}
        </div>
        @endif

        <div class="warning">
            ⚠️ This OTP will expire in {{ $expiresInMinutes }} minutes.
        </div>

        <p style="margin-top: 30px;">If you did not request this booking, please ignore this email.</p>

        <div class="footer">
            <p>© {{ date('Y') }} SJP University. All rights reserved.</p>
            <p>This is an automated email. Please do not reply.</p>
        </div>
    </div>
</body>
</html>