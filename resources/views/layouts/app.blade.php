<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Dot Forms') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('images/dot_forms.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
        
        <style>
            :root {
                --yellow: #F5B800;
                --yellow-dark: #C9950A;
                --yellow-light: #FFF4C2;
                --red: #D32F2F;
                --red-light: #FFEBEB;
                --ink: #1A1A1A;
                --muted: #6B7280;
                --surface: #FFFFFF;
                --bg: #FAFAFA;
            }
            * { box-sizing: border-box; }
            body {
                font-family: 'Inter', sans-serif;
                background-color: var(--bg);
                color: var(--ink);
                margin: 0;
            }
            .display { font-family: 'Sora', sans-serif; }
        </style>
    </head>
    <body class="antialiased">
        <x-banner />

        <div style="display: flex; flex-direction: column; min-height: 100vh; background-color: var(--bg);">
            @livewire('navigation-menu')

            <!-- Page Heading -->
            @if (isset($header))
                <header style="background: white; border-bottom: 1px solid #F0F0F0; box-shadow: 0 1px 3px rgba(0,0,0,.05);">
                    <div style="max-width: 1200px; margin: 0 auto; padding: 24px; display: flex; align-items: center; justify-content: space-between;">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main style="flex: 1; padding: 32px 24px;">
                <div style="max-width: 1200px; margin: 0 auto;">
                    {{ $slot }}
                </div>
            </main>
        </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
