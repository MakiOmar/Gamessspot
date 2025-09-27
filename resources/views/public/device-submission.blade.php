<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Service Submission - GamesSpot</title>
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
            max-width: 600px;
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
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            color: #ffffff;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
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
        
        /* intlTelInput styling */
        label {
            display: block;
        }
        
        div.iti {
            width: 100%;
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
        
        .tracking-link {
            color: #3c8dbc;
            text-decoration: none;
            font-weight: 600;
        }
        
        .tracking-link:hover {
            color: #357ca5;
            text-decoration: underline;
        }
        
        .footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #f4f4f4;
            color: #666;
            font-size: 0.9rem;
        }
        
        .icon {
            color: #ff6b35;
            margin-right: 8px;
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
                    <i class="fas fa-gamepad icon"></i>
                    Device Service Center
                </h1>
                <p class="login-subtitle">Submit your gaming device for account installation service</p>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form action="{{ route('device.submit.store') }}" method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="client_name" class="form-label">
                                <i class="fas fa-user icon"></i>
                                Client Name
                            </label>
                            <input type="text" class="form-control" id="client_name" name="client_name" 
                                   value="{{ old('client_name') }}" placeholder="Enter your full name" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="device_model" class="form-label">
                                <i class="fas fa-laptop icon"></i>
                                Device Model
                            </label>
                            <input type="text" class="form-control" id="device_model" name="device_model" 
                                   value="{{ old('device_model') }}" placeholder="e.g., PS5, Xbox Series X, Nintendo Switch" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone_number" class="form-label">
                        <i class="fas fa-phone icon"></i>
                        Phone Number
                    </label>
                    <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                           value="{{ old('phone_number') }}" placeholder="Enter your phone number" required>
                </div>
                
                <div class="form-group">
                    <label for="device_serial_number" class="form-label">
                        <i class="fas fa-barcode icon"></i>
                        Device Serial Number
                    </label>
                    <input type="text" class="form-control" id="device_serial_number" name="device_serial_number" 
                           value="{{ old('device_serial_number') }}" placeholder="Enter device serial number" required>
                </div>
                
                <div class="form-group">
                    <label for="notes" class="form-label">
                        <i class="fas fa-sticky-note icon"></i>
                        Notes or Information
                    </label>
                    <textarea class="form-control" id="notes" name="notes" rows="4" 
                              placeholder="Describe what games or accounts you want installed, or any additional information...">{{ old('notes') }}</textarea>
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary me-3">
                        <i class="fas fa-paper-plane"></i>
                        Submit Device
                    </button>
                    <a href="{{ route('device.tracking') }}" class="btn btn-secondary">
                        <i class="fas fa-search"></i>
                        Track Service
                    </a>
                </div>
            </form>
            
            <div class="footer">
                <p>
                    <i class="fas fa-shield-alt"></i>
                    Your device information is secure and will be processed by our professional team.
                </p>
                <p class="small">
                    <a href="{{ route('device.tracking') }}" class="tracking-link">Track your service status</a>
                    | 
                    <a href="#" class="tracking-link">Contact Support</a>
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/intlTelInput.min.js') }}"></script>
    <script src="{{ asset('assets/js/utils.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize intlTelInput for phone number
            const input = document.querySelector("#phone_number");
            const iti = window.intlTelInput(input, {
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
            
            // Update hidden fields when phone number changes
            input.addEventListener('change', function() {
                const countryData = iti.getSelectedCountryData();
                // You can add hidden fields for country code if needed
                // document.getElementById('country_code').value = countryData.dialCode;
            });
            
            // Create floating particles effect
            function createParticles() {
                const particlesContainer = document.getElementById('particles');
                const particleCount = 30;
                
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
        
        document.getElementById('device_serial_number').addEventListener('input', function(e) {
            // Convert to uppercase
            e.target.value = e.target.value.toUpperCase();
        });
    </script>
</body>
</html>
