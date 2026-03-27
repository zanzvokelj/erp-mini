<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            window.apiFetch = (url, options = {}) => {
                const csrfToken = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content');

                const headers = new Headers(options.headers || {});

                if (!headers.has('Accept')) {
                    headers.set('Accept', 'application/json');
                }

                if (!headers.has('X-Requested-With')) {
                    headers.set('X-Requested-With', 'XMLHttpRequest');
                }

                if (csrfToken && !headers.has('X-CSRF-TOKEN')) {
                    headers.set('X-CSRF-TOKEN', csrfToken);
                }

                return fetch(url, {
                    credentials: 'same-origin',
                    ...options,
                    headers,
                });
            };
        </script>
    </head>
    <body class="font-sans antialiased bg-gray-100">

    <div class="flex h-screen">

        @include('components.sidebar')

        <div class="flex-1 flex flex-col">

            @include('components.navbar')

            <main class="p-6">
                {{ $slot }}
            </main>

        </div>

    </div>

    </body>
</html>
