@extends('layouts.admin')
@push('css')
    <style>
        input,
        select,
        textarea {
            font-size: 17px;
        }

        .stock-level-card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .stock-level-card .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.5rem;
        }
        .stock-level-card .table {
            margin-bottom: 0;
        }
        .stock-level-card .table thead th {
            border-bottom-width: 1px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        .circle-stat {
        text-align: center;
        }
        
        .circle-progress {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto;
            border-radius: 50%;
            background: conic-gradient(#e9ecef 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .circle-progress::before {
            content: '';
            position: absolute;
            width: 90px;
            height: 90px;
            background: white;
            border-radius: 50%;
        }
        
        .circle-inner {
            position: relative;
            z-index: 1;
            font-size: 2rem;
        }
        
        .circle-progress[data-value] {
            background: conic-gradient(#4e73df 0%, #4e73df calc(var(--value) * 1%), #e9ecef calc(var(--value) * 1%) 100%);
            transition: background 1.5s ease;
        }
        
        .circle-progress[data-value="75"] { --value: 75; }
        .circle-progress[data-value="{{ min(100, ($totalUserCount/100)*100) }}"] { --value: {{ min(100, ($totalUserCount/100)*100) }}; }
        .circle-progress[data-value="{{ min(100, ($uniqueBuyerPhoneCount/500)*100) }}"] { --value: {{ min(100, ($uniqueBuyerPhoneCount/500)*100) }}; }
        .circle-progress[data-value="{{ min(100, ($newUsersCount/50)*100) }}"] { --value: {{ min(100, ($newUsersCount/50)*100) }}; }
        
        .text-warning { color: #f6c23e !important; }
        .text-primary { color: #4e73df !important; }
        .text-danger { color: #e74a3b !important; }
        .text-success { color: #1cc88a !important; }
            .history .card {
                position: relative;
                display: flex;
                flex-direction: column;
                min-width: 0;
                word-wrap: break-word;
                background-color: #fff;
                background-clip: border-box;
                border: 1px solid rgba(26, 54, 126, 0.125);
                border-radius: 0.25rem;
                box-shadow: 0 0.46875rem 2.1875rem rgba(4, 9, 20, 0.03), 0 0.9375rem 1.40625rem rgba(4, 9, 20, 0.03), 0 0.25rem 0.53125rem rgba(4, 9, 20, 0.05), 0 0.125rem 0.1875rem rgba(4, 9, 20, 0.03);
                border-width: 0;
                transition: all 0.2s;
                padding: 0;
            }

            .history .card-body {
                flex: 1 1 auto;
                padding: 1.25rem;
            }

            .history .vertical-timeline {
                width: 100%;
                position: relative;
                padding: 1.5rem 0 1rem;
            }

            .history .vertical-timeline::before {
                content: '';
                position: absolute;
                top: 0;
                left: 90px;
                height: 100%;
                width: 4px;
                background: #e9ecef;
                border-radius: 0.25rem;
            }

            .history .vertical-timeline-element {
                position: relative;
                margin: 0 0 1rem;
            }

            .history .vertical-timeline--animate .vertical-timeline-element-icon.bounce-in {
                visibility: visible;
                animation: cd-bounce-1 0.8s;
            }

            .history .vertical-timeline-element-icon {
                position: absolute;
                top: 0;
                left: 82px;
            }

            .history .vertical-timeline-element-icon .badge-dot-xl {
                box-shadow: 0 0 0 5px #fff;
            }

            .history .badge-dot-xl {
                width: 18px;
                height: 18px;
                position: relative;
            }

            .history .badge:empty {
                display: none;
            }

            .history .badge-dot-xl::before {
                content: '';
                width: 10px;
                height: 10px;
                border-radius: 0.25rem;
                position: absolute;
                left: 50%;
                top: 50%;
                margin: -5px 0 0 -5px;
                background: #fff;
            }

            .history .vertical-timeline-element-content {
                position: relative;
                margin-left: 110px;
                font-size: 0.8rem;
            }

            .history .vertical-timeline-element-content .timeline-title {
                font-size: 0.8rem;
                text-transform: uppercase;
                margin: 0 0 0.5rem;
                padding: 2px 0 0;
                font-weight: bold;
            }

            .history .vertical-timeline-element-content .vertical-timeline-element-date {
                display: block;
                position: absolute;
                left: -100px;
                top: 0;
                padding-right: 10px;
                text-align: center;
                color: #adb5bd;
                font-size: 0.7619rem;
                white-space: nowrap;
            }

            .history .vertical-timeline-element-content:after {
                content: "";
                display: table;
                clear: both;
            }

            /*Sales states*/
            .sales-stats .card-header {
                background-color: #f64e60; /* Red background similar to the image */
                border-radius: 0.25rem 0.25rem 0 0;
                padding: 2rem;
            }

            .sales-stats .card-title {
                font-size: 1.25rem;
                color: #ffffff;
                font-weight: bold;
            }

            .sales-stats .btn {
                background-color: #ff6f61;
                color: white;
                border-radius: 1rem;
            }

            .sales-stats .tile {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                background-color: #ffffff;
                border-radius: 1rem;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                padding: 1.5rem;
                transition: transform 0.3s ease;
            }

            .sales-stats .tile:hover {
                transform: translateY(-5px);
            }

            .sales-stats .icon-wrapper {
                background-color: rgba(255, 255, 255, 0.7);
                border-radius: 50%;
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .sales-stats .icon {
                font-size: 2rem;
            }

            .sales-stats .text-warning {
                color: #f8af42 !important;
            }

            .sales-stats .text-primary {
                color: #3699ff !important;
            }

            .sales-stats .text-danger {
                color: #f64e60 !important;
            }

            .sales-stats .text-success {
                color: #1bc5bd !important;
            }

            .sales-stats .rounded-xl {
                border-radius: 1rem !important;
            }

            .sales-stats .bg-light-warning {
                background-color: #fff4e6 !important;
            }

            .sales-stats .bg-light-primary {
                background-color: #e6f4ff !important;
            }

            .sales-stats .bg-light-danger {
                background-color: #ffe2e5 !important;
            }

            .sales-stats .bg-light-success {
                background-color: #e6fffb !important;
            }

            .sales-stats .card-spacer {
                margin-top: -50px;
            }
    </style>
@endpush

{{-- Customize layout sections --}}

@section('subtitle', 'Manager')
@section('content_header_title', 'Dashboard')
@section('content_header_subtitle', 'Welcome')

{{-- Content body: main page content --}}

@section('content_body')     
    @if ( Auth::user()->roles->contains('name', 'admin') )
    @include('manager.dashboard-admin')
    @elseif(Auth::user()->roles->contains('name', 'sales') || Auth::user()->roles->contains('name', 'account manager'))
    @include('manager.dashboard-sales')
    @elseif(Auth::user()->roles->contains('name', 'accountant'))
    @include('manager.stores')
    @endif
@stop

{{-- Push extra CSS --}}

@push('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@endpush

{{-- Push extra scripts --}}

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate circles on load
            const circles = document.querySelectorAll('.circle-progress');
            circles.forEach(circle => {
                const value = circle.getAttribute('data-value');
                circle.style.setProperty('--value', value);
            });
            
            // Initialize chart
            const ctx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Sales',
                        data: [12000, 19000, 15000, 20000, 17000, 22000],
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: true
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
        </script>
        <script>
            let idleTime = 0;
            const maxIdleMinutes = @json(config('session.lifetime'));;
        
            const idleInterval = setInterval(() => {
                idleTime++;
                if (idleTime >= maxIdleMinutes) {
                    logoutUser(); // frontend auto logout
                }
            }, 60000);
        
            ['mousemove', 'keydown', 'scroll', 'click'].forEach(event => {
                document.addEventListener(event, () => {
                    idleTime = 0;
                });
            });
        
            function logoutUser() {
                fetch("{{ route('manager.logout') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                }).then(() => {
                    window.location.href = "/";
                });
            }
        </script>
        
        
@endpush
