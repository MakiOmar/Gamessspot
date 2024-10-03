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
</head>
<body>

    @include('layouts.header') <!-- Reusable header -->

    <div class="container mt-4">
        @yield('content') <!-- Main content will be injected here -->
    </div>

    @include('layouts.footer') <!-- Reusable footer -->

    <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    @stack('scripts')
</body>
</html>
