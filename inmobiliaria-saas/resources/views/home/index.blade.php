@php
    $navUrl = fn (string $name, array $parameters = []) => '/'.ltrim(route($name, $parameters, false), '/');
    $user = Auth::user();
    $initials = collect(explode(' ', trim($user->name)))->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');

    $canSeeMaestras = $user->can('viewAny', App\Models\Provider2::class)
        || $user->can('viewAny', App\Models\Asset2::class)
        || $user->can('viewAny', App\Models\ProductGroup::class)
        || $user->can('viewAny', App\Models\User::class);
@endphp

<x-app-layout>

    <div class="py-8" x-data="{ maestrasOpen: false }">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Company + user hero --}}
            <div class="rounded-2xl border border-stone-200 bg-white px-6 pt-3 pb-6 text-center">
                @if ($user->company?->logo_path)
                    <div class="mx-auto flex h-32 w-32 items-center justify-center overflow-hidden">
                        <img
                            src="{{ route('companies.logo', $user->company) }}"
                            alt="{{ $user->company->name }}"
                            class="h-full w-full object-contain"
                        >
                    </div>
                @else
                    <div class="mx-auto flex h-32 w-32 items-center justify-center">
                        <x-application-logo class="h-20 w-20 fill-current text-stone-800" />
                    </div>
                @endif

                <div class="my-4 border-t border-stone-100"></div>

                <div class="flex items-center justify-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-stone-800 text-sm font-bold text-white">
                        {{ $initials }}
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-semibold text-stone-800">{{ $user->name }}</p>
                        <p class="text-xs text-stone-400">{{ $roleLabel }}</p>
                    </div>
                </div>
            </div>

            {{-- Module grid --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">

                @can('viewAny', App\Models\Project::class)
                <a href="{{ $navUrl('projects.index') }}" class="home-card group">
                    <div class="home-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <span class="home-card-label">Proyectos</span>
                </a>
                @endcan

                @if ($canSeeMaestras)
                <button type="button" class="home-card group" x-on:click="maestrasOpen = true">
                    <div class="home-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                    <span class="home-card-label">Maestras</span>
                </button>
                @endif

                @can('viewAny', App\Models\Expense::class)
                <a href="{{ $navUrl('expenses.index') }}" class="home-card group">
                    <div class="home-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21l-7-7-7 7V5a2 2 0 012-2h10a2 2 0 012 2v16z" />
                        </svg>
                    </div>
                    <span class="home-card-label">Gastos</span>
                </a>
                @endcan

                @can('viewAny', App\Models\Purchase::class)
                <a href="{{ $navUrl('purchases.index') }}" class="home-card group">
                    <div class="home-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <span class="home-card-label">Compras</span>
                </a>
                @endcan

                @can('reports.view')
                <a href="{{ $navUrl('reports.index') }}" class="home-card group">
                    <div class="home-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span class="home-card-label">Reportes</span>
                </a>
                @endcan

                {{-- Cerrar sesión siempre al final --}}
                <form method="POST" action="{{ $navUrl('logout') }}" class="contents">
                    @csrf
                    <button type="submit" class="home-card group">
                        <div class="home-card-icon !bg-rose-50 !text-rose-400 group-hover:!bg-rose-100 group-hover:!text-rose-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </div>
                        <span class="home-card-label !text-rose-500 group-hover:!text-rose-700">Cerrar sesión</span>
                    </button>
                </form>

            </div>

        </div>

        {{-- Maestras overlay panel --}}
        @if ($canSeeMaestras)
        <template x-teleport="body">
            <div x-show="maestrasOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">

                {{-- Backdrop --}}
                <div
                    x-show="maestrasOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 bg-black/40"
                    x-on:click="maestrasOpen = false"
                ></div>

                {{-- Panel --}}
                <div
                    x-show="maestrasOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative w-full max-w-sm rounded-2xl border border-stone-200 bg-white px-5 pb-6 pt-5 shadow-xl"
                >
                    {{-- Header --}}
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-base font-semibold text-stone-800">Maestras</p>
                        <button
                            type="button"
                            x-on:click="maestrasOpen = false"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-stone-400 transition hover:bg-stone-100 hover:text-stone-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    {{-- Sub-module grid --}}
                    <div class="grid grid-cols-2 gap-3">

                        @can('viewAny', App\Models\Provider2::class)
                        <a href="{{ $navUrl('providers2.index') }}" class="home-card group">
                            <div class="home-card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                </svg>
                            </div>
                            <span class="home-card-label">Proveedores</span>
                        </a>
                        @endcan

                        @can('viewAny', App\Models\Asset2::class)
                        <a href="{{ $navUrl('assets2.index') }}" class="home-card group">
                            <div class="home-card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <span class="home-card-label">Activos</span>
                        </a>
                        @endcan

                        @can('viewAny', App\Models\ProductGroup::class)
                        <a href="{{ $navUrl('product-catalog.index') }}" class="home-card group">
                            <div class="home-card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                            </div>
                            <span class="home-card-label">Productos</span>
                        </a>
                        @endcan

                        @can('viewAny', App\Models\CatalogActivityGroup::class)
                        <a href="{{ $navUrl('activity-catalog.index') }}" class="home-card group">
                            <div class="home-card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="home-card-label">Actividades</span>
                        </a>
                        @endcan

                        @can('viewAny', App\Models\User::class)
                        <a href="{{ $navUrl('users.index') }}" class="home-card group">
                            <div class="home-card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <span class="home-card-label">Usuarios</span>
                        </a>
                        @endcan

                    </div>
                </div>
            </div>
        </template>
        @endif

    </div>
</x-app-layout>
