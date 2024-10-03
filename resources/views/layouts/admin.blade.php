<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Manager Dashboard')</title>
    <!-- Include Bootstrap CSS -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('styles')
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        #sidebar-wrapper {
            width: 250px;
            transition: all 0.3s ease;
        }

        #sidebar-wrapper.toggled {
            margin-left: -250px;
        }

        #sidebar-wrapper {
            width: 250px;
            background-color: #ffcc99;
        }

        .sidebar-heading {
            padding: 1rem;
            font-size: 1.5rem;
            color: white;
            background-color: #6a1b0e;
        }
        .list-group-item:hover {
            background-color: #000!important;
            color: white;
        }


        .list-group-item {
            background-color: #ffcc99;
            border: none;
        }

        .card {
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        footer {
            padding: 1rem;
            color: #6a1b0e;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="d-flex" id="wrapper">
        @include('layouts.sidemenu')
        <!-- Page Content -->
        <div class="flex-grow-1" id="page-content-wrapper">
            @include('layouts.header') <!-- Reusable header -->
            <div class="container mt-4">
                @yield('content') <!-- Main content will be injected here -->
            </div>
            <footer class="footer mt-4">
                <p class="text-center">2021© GAMESPOT</p>
            </footer>
        </div>
    </div>

    @include('layouts.footer') <!-- Reusable footer -->

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <!-- FontAwesome for icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"></script>
    <script>
        document.getElementById("menu-toggle").addEventListener("click", function(e) {
            e.preventDefault();
            document.getElementById("sidebar-wrapper").classList.toggle("toggled");
        });
    </script>
    @stack('scripts')
</body>
</html>
