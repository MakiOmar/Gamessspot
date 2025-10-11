<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Service Tracking - GamesSpot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/intlTelInput.min.css') }}">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            font-family: 'Source Sans Pro', sans-serif;
            overflow-x: hidden;
            position: relative;
        }
        
        .login-container {
            background: rgba(45, 45, 45, 0.95);
            border: 1px solid #ff6b35;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 0 20px rgba(255, 107, 53, 0.2);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 1000px;
            backdrop-filter: blur(10px);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ff6b35;
        }
        
        .login-title {
            color: #ffffff;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-shadow: 0 0 10px rgba(255, 107, 53, 0.5);
        }
        
        .login-subtitle {
            color: #cccccc;
            font-size: 1rem;
        }
        
        .tracking-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid #ff6b35;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .tracking-card:hover {
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.2);
            transform: translateY(-5px);
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .status-received {
            background: linear-gradient(45deg, #ff6b35, #ff8c42);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }
        
        .status-processing {
            background: linear-gradient(45deg, #ffc107, #ffeb3b);
            color: #212529;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }
        
        .status-ready {
            background: linear-gradient(45deg, #17a2b8, #20c997);
            color: white;
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
        }
        
        .status-delivered {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .device-info {
            color: #ffffff;
            margin-bottom: 1rem;
        }
        
        .info-label {
            color: #ff6b35;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .info-value {
            color: #ffffff;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .tracking-code {
            background: rgba(255, 107, 53, 0.1);
            border: 1px solid #ff6b35;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
        }
        
        .tracking-code-value {
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff6b35;
            text-shadow: 0 0 10px rgba(255, 107, 53, 0.5);
        }
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #ff6b35;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
            color: #333;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 1);
            border-color: #ff6b35;
            box-shadow: 0 0 15px rgba(255, 107, 53, 0.3);
            outline: 0;
            transform: translateY(-2px);
        }
        
        .form-control::placeholder {
            color: #666;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #ff6b35, #ff8c42);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #ff8c42, #ff6b35);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #ff6b35;
            color: #ff6b35;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 107, 53, 0.1);
            border-color: #ff8c42;
            color: #ff8c42;
            transform: translateY(-2px);
        }
        
        .form-label {
            color: #ffffff;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .icon {
            color: #ff6b35;
            margin-right: 8px;
        }
        
        /* intlTelInput styling */
        label {
            display: block;
        }
        
        div.iti {
            width: 100%;
        }
        
        .alert {
            border-radius: 4px;
            border: none;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        
        .no-results {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-results i {
            font-size: 4rem;
            color: #3c8dbc;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .progress-bar {
            background-color: #3c8dbc;
        }
        
        .progress {
            background-color: #f8f9fa;
            border-radius: 4px;
            height: 8px;
            margin: 1rem 0;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
            padding-left: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -7px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #6c757d;
        }
        
        .timeline-item.completed::before {
            background: #28a745;
        }
        
        .timeline-item.current::before {
            background: #3c8dbc;
        }
        
        .footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #f4f4f4;
            color: #666;
            font-size: 0.9rem;
        }
        
        .submission-link {
            color: #3c8dbc;
            text-decoration: none;
            font-weight: 600;
        }
        
        .submission-link:hover {
            color: #ff8c42;
            text-decoration: underline;
        }
        
        /* Particle effects */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #ff6b35;
            border-radius: 50%;
            animation: float 8s infinite linear;
            opacity: 0.6;
        }
        
        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.6;
            }
            90% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }
        
        /* Gaming background elements */
        .gaming-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            opacity: 0.1;
        }
        
        .gaming-bg::before {
            content: '';
            position: absolute;
            top: 20%;
            left: 10%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, #ff6b35 0%, transparent 70%);
            border-radius: 50%;
            animation: pulse 4s ease-in-out infinite;
        }
        
        .gaming-bg::after {
            content: '';
            position: absolute;
            top: 60%;
            right: 15%;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, #ff8c42 0%, transparent 70%);
            border-radius: 50%;
            animation: pulse 3s ease-in-out infinite reverse;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.1; }
            50% { transform: scale(1.2); opacity: 0.2; }
        }
        
    </style>
</head>
<body>
    <div class="gaming-bg"></div>
    <div class="particles" id="particles"></div>
    
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h1 class="login-title">
                    <i class="fas fa-search icon"></i>
                    Device Service Tracking
                </h1>
                <p class="login-subtitle">Track your gaming device service status in real-time</p>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    {{ session('error') }}
                </div>
            @endif
            
            <!-- Search Form -->
            <div class="tracking-card">
                <h4 class="text-center mb-4" style="color: #00d4ff;">
                    <i class="fas fa-search gaming-icon"></i>
                    Search Your Device
                </h4>
                
                @if(!isset($deviceRepair) && !isset($deviceRepairs))
                    <form action="{{ route('device.search') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="phone_number" class="form-label">
                                <i class="fas fa-phone icon"></i>
                                Phone Number
                            </label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                                   value="{{ old('phone_number') }}" placeholder="Enter your phone number" required>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Search
                            </button>
                        </div>
                    </form>
                @endif
                
                <!-- Direct Tracking Code Input -->
                @if(isset($trackingCode) && $trackingCode && !isset($deviceRepair))
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i>
                        No device found with tracking code: <strong>{{ $trackingCode }}</strong>
                    </div>
                @endif
            </div>
            
            <!-- Single Device Result -->
            @if(isset($deviceRepair))
                <div class="tracking-card">
                    <div class="tracking-code">
                        <div class="info-label">Tracking Code</div>
                        <div class="tracking-code-value">{{ $deviceRepair->tracking_code }}</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="device-info">
                                <div class="info-label">Client Name</div>
                                <div class="info-value">{{ $deviceRepair->client_name }}</div>
                                
                                <div class="info-label">Phone Number</div>
                                <div class="info-value">{{ $deviceRepair->full_phone_number }}</div>
                                
                                <div class="info-label">Device Model</div>
                                <div class="info-value">{{ $deviceRepair->deviceModel ? $deviceRepair->deviceModel->full_name : 'N/A' }}</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="device-info">
                                <div class="info-label">Serial Number</div>
                                <div class="info-value">{{ $deviceRepair->device_serial_number }}</div>
                                
                                <div class="info-label">Submitted Date</div>
                                <div class="info-value">{{ $deviceRepair->submitted_at->format('M d, Y H:i') }}</div>
                                
                                <div class="info-label">Last Updated</div>
                                <div class="info-value">{{ $deviceRepair->status_updated_at ? $deviceRepair->status_updated_at->format('M d, Y H:i') : 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                    
                    @if($deviceRepair->notes)
                        <div class="device-info">
                            <div class="info-label">Notes</div>
                            <div class="info-value">{{ $deviceRepair->notes }}</div>
                        </div>
                    @endif
                    
                    <div class="text-center">
                        <span class="status-badge status-{{ $deviceRepair->status }}">
                            <i class="fas fa-{{ $deviceRepair->status == 'delivered' ? 'check-circle' : ($deviceRepair->status == 'ready' ? 'hand-holding' : ($deviceRepair->status == 'processing' ? 'cogs' : 'clock')) }}"></i>
                            {{ $deviceRepair->status_display }}
                        </span>
                    </div>
                    
                    <!-- Progress Timeline -->
                    <div class="timeline mt-4">
                        <div class="timeline-item {{ $deviceRepair->status == 'received' ? 'current' : 'completed' }}">
                            <div class="info-label">Device Received</div>
                            <div class="info-value">{{ $deviceRepair->submitted_at->format('M d, Y H:i') }}</div>
                        </div>
                        
                        <div class="timeline-item {{ $deviceRepair->status == 'processing' ? 'current' : ($deviceRepair->status == 'ready' || $deviceRepair->status == 'delivered' ? 'completed' : '') }}">
                            <div class="info-label">Processing</div>
                            <div class="info-value">{{ $deviceRepair->status == 'received' ? 'Pending' : 'In Progress' }}</div>
                        </div>
                        
                        <div class="timeline-item {{ $deviceRepair->status == 'ready' ? 'current' : ($deviceRepair->status == 'delivered' ? 'completed' : '') }}">
                            <div class="info-label">Ready for Pickup</div>
                            <div class="info-value">{{ $deviceRepair->status == 'delivered' ? 'Completed' : 'Pending' }}</div>
                        </div>
                        
                        <div class="timeline-item {{ $deviceRepair->status == 'delivered' ? 'current completed' : '' }}">
                            <div class="info-label">Delivered</div>
                            <div class="info-value">{{ $deviceRepair->status == 'delivered' ? 'Completed' : 'Pending' }}</div>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Multiple Devices Result -->
            @if(isset($deviceRepairs) && $deviceRepairs->count() > 0)
                <div class="tracking-card">
                    <h4 class="text-center mb-4" style="color: #00d4ff;">
                        <i class="fas fa-list gaming-icon"></i>
                        Your Active Repairs
                    </h4>
                    
                    @foreach($deviceRepairs as $repair)
                        <div class="tracking-card mb-3">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="device-info">
                                        <div class="info-label">Device Model</div>
                                        <div class="info-value">{{ $repair->deviceModel ? $repair->deviceModel->full_name : 'N/A' }}</div>
                                        
                                        <div class="info-label">Serial Number</div>
                                        <div class="info-value">{{ $repair->device_serial_number }}</div>
                                        
                                        <div class="info-label">Tracking Code</div>
                                        <div class="info-value" style="font-family: monospace; color: #ff6b35; text-shadow: 0 0 10px rgba(255, 107, 53, 0.5);">{{ $repair->tracking_code }}</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 text-center">
                                    <span class="status-badge status-{{ $repair->status }}">
                                        <i class="fas fa-{{ $repair->status == 'delivered' ? 'check-circle' : ($repair->status == 'ready' ? 'hand-holding' : ($repair->status == 'processing' ? 'cogs' : 'clock')) }}"></i>
                                        {{ $repair->status_display }}
                                    </span>
                                    
                                    <div class="mt-3">
                                        <a href="{{ route('device.tracking', ['code' => $repair->tracking_code]) }}" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-eye"></i>
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            
            <!-- No Results -->
            @if(isset($deviceRepairs) && $deviceRepairs->count() == 0)
                <div class="tracking-card">
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h4>No Active Services Found</h4>
                        <p>No active service orders found for this phone number.</p>
                        <a href="{{ route('device.submit') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Submit New Device
                        </a>
                    </div>
                </div>
            @endif
            
            <div class="footer">
                <p>
                    <i class="fas fa-shield-alt"></i>
                    Your service information is secure and updated in real-time.
                </p>
                <p class="small">
                    <a href="{{ route('device.submit') }}" class="submission-link">Submit New Device</a>
                    | 
                    <a href="#" class="submission-link">Contact Support</a>
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/intlTelInput.min.js') }}"></script>
    <script src="{{ asset('assets/js/utils.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize intlTelInput for phone number if element exists
            const phoneInput = document.getElementById('phone_number');
            if (phoneInput) {
                const iti = window.intlTelInput(phoneInput, {
                    initialCountry: "auto",
                    separateDialCode: true,
                    countrySearch: true,
                    preferredCountries: ["us", "gb", "eg"],
                    allowDropdown: true,
                    geoIpLookup: function(success, failure) {
                        fetch("https://ipinfo.io/json", {mode: "cors"})
                        .then(response => response.json())
                        .then((response) => {
                            const countryCode = (response && response.country) ? response.country : "eg";
                            success(countryCode);
                        })
                        .catch(() => failure());
                    },
                    utilsScript: "{{ asset('assets/js/utils.js') }}"
                });
            }
            
            // Create floating particles effect
            function createParticles() {
                const particlesContainer = document.getElementById('particles');
                const particleCount = 25;
                
                for (let i = 0; i < particleCount; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'particle';
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.animationDelay = Math.random() * 8 + 's';
                    particle.style.animationDuration = (Math.random() * 4 + 6) + 's';
                    particlesContainer.appendChild(particle);
                }
            }
            
            // Initialize particles
            createParticles();
        });
        
        // Auto-refresh every 30 seconds if device is being tracked
        @if(isset($deviceRepair) && $deviceRepair->status !== 'delivered')
        setInterval(function() {
            location.reload();
        }, 30000);
        @endif
    </script>
</body>
</html>
