<!DOCTYPE html>
<html lang="es-CO" translate="no" class="notranslate">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="google" content="notranslate">

        <title>{{ config('app.name', 'EJE 3') }} · Vehículo</title>

        <style>
            [x-cloak] { display: none !important; }
        </style>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased app-shell notranslate">
        <div class="min-h-screen bg-gray-100">
            <nav class="border-b border-stone-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <span class="text-sm font-semibold uppercase tracking-[0.14em] text-stone-500">Vehículo</span>
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <a href="{{ route('vehiculo.index') }}" class="rounded-2xl px-3 py-2 transition {{ request()->routeIs('vehiculo.index') ? 'bg-stone-900 text-white' : 'text-stone-600 hover:bg-stone-100' }}">
                            Registros
                        </a>
                        <a href="{{ route('vehiculo.dashboard') }}" class="rounded-2xl px-3 py-2 transition {{ request()->routeIs('vehiculo.dashboard') ? 'bg-stone-900 text-white' : 'text-stone-600 hover:bg-stone-100' }}">
                            Indicadores
                        </a>
                        @auth
                            <a href="{{ '/'.ltrim(route(auth()->user()->homeRouteName(), [], false), '/') }}" class="ml-1 inline-flex items-center gap-1.5 rounded-2xl border border-stone-300 px-3 py-2 text-stone-600 transition hover:bg-stone-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                Volver al inicio
                            </a>
                        @endauth
                    </div>
                </div>
            </nav>

            <div {{ $attributes }}>
                @isset($header)
                    <header class="border-b border-stone-200 bg-white shadow-sm">
                        <div class="mx-auto max-w-7xl px-4 py-6 text-center sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
