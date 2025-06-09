{{-- filepath: c:\DATA DISK\Projects\application generator\application_letters\resources\views\layouts\guest.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        {{-- <link rel="preconnect" href="https://fonts.bunny.net"> --}}
        {{-- <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> --}}

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="min-vh-100 d-flex flex-column justify-content-center align-items-center bg-light">
            <div class="mb-4">
                <a href="/" wire:navigate class="d-flex justify-content-center align-items-center">
                    <x-application-logo class="w-25 h-25" />
                </a>
                <h2 class="logo-text">SIRAPEL</h2>
            </div>

            <div class="w-100" style="max-width: 400px;">
                <div class="card shadow-sm">
                    <div class="card-body">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>

        <script type="module">
            $(document).ready(function() {


                // // Initialize Bootstrap tooltips
                // $('[data-bs-toggle="tooltip"]').tooltip();

                // // Initialize Bootstrap popovers
                // $('[data-bs-toggle="popover"]').popover();
            });
        </script>

        <!-- Bootstrap JS (optional, for interactive components) -->
        {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> --}}
    </body>
</html>
