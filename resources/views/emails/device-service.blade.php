<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Service Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 0;
            background-color: #f4f4f4;
        }
        
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background-color: #000000;
            padding: 20px;
            text-align: center;
        }
        
        .logo {
            max-width: 200px;
            height: auto;
        }
        
        .content {
            padding: 30px;
        }
        
        .greeting {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }
        
        .message {
            font-size: 16px;
            color: #555;
            margin-bottom: 25px;
        }
        
        .device-details {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .device-details h3 {
            margin-top: 0;
            color: #333;
            font-size: 18px;
        }
        
        .detail-item {
            margin: 10px 0;
            font-size: 14px;
        }
        
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        
        .tracking-code {
            background-color: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .status {
            background-color: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .action-button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .action-button:hover {
            background-color: #0056b3;
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        
        .footer p {
            margin: 5px 0;
            color: #666;
        }
        
        .signature {
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header with Logo -->
        <div class="header">
            <img src="{{ $logoUrl }}" alt="Gamesspot Logo" class="logo">
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="greeting">
                @if($type === 'created')
                    Hello {{ $deviceRepair->user->name }}!
                @else
                    Hello {{ $deviceRepair->user->name }}!
                @endif
            </div>
            
            <div class="message">
                @if($type === 'created')
                    We have successfully received your device for service. Our team will begin working on it shortly.
                @else
                    Your device service status has been updated. Please see the details below.
                @endif
            </div>
            
            <!-- Device Details -->
            <div class="device-details">
                <h3>Device Details:</h3>
                <div class="detail-item">
                    <span class="detail-label">Device Model:</span> {{ $deviceRepair->deviceModel ? $deviceRepair->deviceModel->full_name : 'N/A' }}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Serial Number:</span> {{ $deviceRepair->device_serial_number }}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Tracking Code:</span> 
                    <span class="tracking-code">{{ $deviceRepair->tracking_code }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Current Status:</span> 
                    <span class="status">{{ ucfirst($deviceRepair->status) }}</span>
                </div>
                @if($deviceRepair->notes)
                <div class="detail-item">
                    <span class="detail-label">Notes:</span> {{ $deviceRepair->notes }}
                </div>
                @endif
            </div>
            
            <!-- Action Button -->
            @if($type === 'created')
                <a href="{{ route('device.tracking', ['code' => $deviceRepair->tracking_code]) }}" class="action-button">
                    Track Your Device
                </a>
                <p>You can track your device status anytime using the tracking code above.</p>
            @else
                <a href="{{ route('device.tracking', ['code' => $deviceRepair->tracking_code]) }}" class="action-button">
                    View Status Update
                </a>
                <p>Click the button above to view the latest status of your device.</p>
            @endif
            
            <p>Thank you for choosing Gamesspot for your device repair needs!</p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p class="signature">Best regards,</p>
            <p class="signature">Gamesspot Team</p>
        </div>
    </div>
</body>
</html>
