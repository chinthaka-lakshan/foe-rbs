<!DOCTYPE html>
<html>
<head>
    <title>Password Reset OTP</title>
</head>
<body>
    <h1>Password Reset Request</h1>
    <p>We received a request to reset your password. Use the following One-Time Password (OTP) code:</p>
    
    <h2 style="color: #4BB66D;">{{ $otpCode }}</h2>
    
    <p>This code is valid for 15 minutes. If you did not request this, please ignore this email.</p>
</body>
</html>