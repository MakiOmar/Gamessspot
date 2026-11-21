<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your login code</title>
</head>
<body>
    <h1>Your login code</h1>
    <p>Hello {{ $user->name }},</p>
    <p>Your Games Spot Accounts login code is:</p>
    <p style="font-size: 28px; font-weight: bold; letter-spacing: 4px;">{{ $code }}</p>
    <p>This code expires in {{ $expireMinutes }} minutes. If you did not request it, ignore this email.</p>
</body>
</html>
