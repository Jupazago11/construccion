@php
    $authUser = auth()->user();
    $tenantColor = null;

    if ($authUser && ! $authUser->isSuperAdmin()) {
        $rawTenantColor = $authUser->company?->primary_color;

        if (is_string($rawTenantColor) && preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $rawTenantColor)) {
            $tenantColor = $rawTenantColor;
        }
    }
@endphp

<!DOCTYPE html>
<html lang="es-CO" translate="no" class="notranslate">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="google" content="notranslate">
        <meta name="current-route" content="{{ request()->route()?->getName() }}">
        <meta name="current-path" content="{{ request()->path() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <style>
            [x-cloak] { display: none !important; }
        </style>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body
        @class(['font-sans antialiased app-shell notranslate', 'tenant-branded' => $tenantColor])
        @style([$tenantColor ? '--app-accent: '.$tenantColor : null])
        data-current-route="{{ request()->route()?->getName() }}"
        data-current-path="{{ request()->path() }}"
    >
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <div {{ $attributes }}>
                <!-- Page Heading -->
                @isset($header)
                    <header class="app-page-heading border-b border-stone-200 bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-center">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
