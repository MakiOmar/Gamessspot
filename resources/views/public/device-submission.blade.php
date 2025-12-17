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
            background: linear-gradient(135deg, #000000 0%, #111111 100%);
            min-height: 100vh;
            font-family: 'Source Sans Pro', sans-serif;
            overflow-x: hidden;
            position: relative;
        }
        
        .login-container {
            background: rgba(0, 0, 0, 0.9);
            border: 1px solid #db890a;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7), 0 0 40px rgba(219, 137, 10, 0.35);
            padding: 2.5rem 2.25rem;
            margin: 3rem auto;
            max-width: 600px;
            backdrop-filter: blur(10px);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(219, 137, 10, 0.45);
        }
        
        .login-title {
            color: #ffffff;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-shadow: 0 0 18px rgba(219, 137, 10, 0.8);
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
            background: rgba(15, 15, 15, 0.95);
            border: 1px solid rgba(219, 137, 10, 0.7);
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
            color: #f2f2f2;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(8, 8, 8, 1);
            border-color: #db890a;
            box-shadow: 0 0 18px rgba(219, 137, 10, 0.5);
            outline: 0;
            transform: translateY(-2px);
        }
        
        .form-control::placeholder {
            color: #999;
        }
        
        /* intlTelInput styling */
        label {
            display: block;
        }
        
        div.iti {
            width: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #db890a, #f4b74b);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 18px rgba(219, 137, 10, 0.7);
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #f4b74b, #db890a);
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(219, 137, 10, 0.9);
        }
        
        .btn-secondary {
            background: transparent;
            border: 1px solid rgba(219, 137, 10, 0.7);
            color: #db890a;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-secondary:hover {
            background: rgba(219, 137, 10, 0.12);
            border-color: #f4b74b;
            color: #f4b74b;
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 4px;
            border: 1px solid rgba(219, 137, 10, 0.4);
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: rgba(18, 40, 18, 0.95);
            color: #e5ffe5;
            border-left: 4px solid #db890a;
        }
        
        .alert-danger {
            background-color: rgba(50, 12, 12, 0.96);
            color: #ffd4d4;
            border-left: 4px solid #db890a;
        }
        
        .tracking-link {
            color: #db890a;
            text-decoration: none;
            font-weight: 600;
        }
        
        .tracking-link:hover {
            color: #f4b74b;
            text-decoration: underline;
        }
        
        .footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #262626;
            color: #999;
            font-size: 0.9rem;
        }
        
        .icon {
            color: #db890a;
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
            background: #db890a;
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
            background: radial-gradient(circle, #db890a 0%, transparent 70%);
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
            background: radial-gradient(circle, #db890a 0%, transparent 70%);
            border-radius: 50%;
            animation: pulse 3s ease-in-out infinite reverse;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.1; }
            50% { transform: scale(1.2); opacity: 0.2; }
        }

        .app-logo {
            max-height: 250px;
            width: auto;
            object-fit: contain;
            display: block;
            margin: 0 auto 1.5rem;
            filter: drop-shadow(0 0 18px rgba(219, 137, 10, 0.8));
        }
    </style>
</head>
<body>
    <div class="gaming-bg"></div>
    <div class="particles" id="particles"></div>
    
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                @if(!empty($appLogo))
                    <img src="{{ asset($appLogo) }}" alt="GamesSpot Logo" class="app-logo">
                @endif
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
                            <label for="client_email" class="form-label">
                                <i class="fas fa-envelope icon"></i>
                                Email Address
                            </label>
                            <input type="email" class="form-control" id="client_email" name="client_email" 
                                   value="{{ old('client_email') }}" placeholder="Enter your email address" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="device_model_id" class="form-label">
                                <i class="fas fa-laptop icon"></i>
                                Device Model
                            </label>
                            <select class="form-control" id="device_model_id" name="device_model_id" required>
                                <option value="">Select Device Model</option>
                                @foreach($deviceModels as $model)
                                    <option value="{{ $model->id }}" {{ old('device_model_id') == $model->id ? 'selected' : '' }}>
                                        {{ $model->full_name }}
                                    </option>
                                @endforeach
                            </select>
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
