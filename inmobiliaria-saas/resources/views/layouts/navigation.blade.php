@php
    $navUrl = fn (string $name, array $parameters = []) => '/'.ltrim(route($name, $parameters, false), '/');
    $mobileNavClass = fn (bool $active) => $active
        ? 'block w-full touch-manipulation border-l-4 border-sky-300 bg-sky-50 py-2 pe-4 ps-3 text-left text-base font-medium text-sky-800 transition duration-150 ease-in-out focus:bg-sky-100 focus:text-sky-900 focus:outline-none'
        : 'block w-full touch-manipulation border-l-4 border-transparent py-2 pe-4 ps-3 text-left text-base font-medium text-stone-600 transition duration-150 ease-in-out active:bg-stone-50 active:text-stone-900 focus:bg-stone-50 focus:text-stone-800 focus:outline-none';

    $authUser = Auth::user();
    $mobileRoleLabel = match(true) {
        $authUser->isSuperAdmin()                                                        => 'SuperAdmin',
        $authUser->hasRole(\App\Enums\SystemRole::CompanyAdmin->value)                  => 'Administrador',
        $authUser->hasRole(\App\Enums\SystemRole::Operator->value)                      => 'Operador',
        $authUser->hasRole(\App\Enums\SystemRole::Viewer->value)                        => 'Visualizador',
        $authUser->hasRole(\App\Enums\SystemRole::BuyerUser->value)                     => 'Comprador',
        default                                                                          => 'Usuario',
    };
    $mobileInitials = collect(explode(' ', trim($authUser->name)))->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
@endphp

<nav x-data="{ open: false, mastersOpen: {{ request()->routeIs('users.*', 'assets.*', 'assets2.*', 'providers.*', 'providers2.*', 'product-catalog.*', 'activity-catalog.*') ? 'true' : 'false' }} }" class="sticky top-0 z-50 border-b border-stone-200 bg-white sm:bg-white/95 sm:backdrop-blur">
    <!-- Primary Navigation Menu -->
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                <!-- Logo -->
                <div class="flex shrink-0 items-center">
                    <a href="{{ $navUrl(Auth::user()->homeRouteName()) }}">
                        @if (! Auth::user()->isSuperAdmin() && Auth::user()->company?->logo_path)
                            <img
                                src="{{ route('companies.logo', Auth::user()->company) }}"
                                alt="{{ Auth::user()->company->name }}"
                                class="block h-9 w-auto max-w-[9rem] object-contain"
                            >
                        @else
                            <x-application-logo class="app-logo block h-9 w-auto fill-current text-stone-800" />
                        @endif
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-2 sm:-my-px sm:ms-10 sm:flex sm:items-center">
                    @if (Auth::user()->isSuperAdmin())
                        <x-nav-link :href="$navUrl('dashboard')" :active="request()->routeIs('dashboard')">
                            Panel
                        </x-nav-link>
                        <x-nav-link :href="$navUrl('companies.index')" :active="request()->routeIs('companies.*')">
                            Empresas
                        </x-nav-link>
                    @endif
                    @can('viewAny', App\Models\Project::class)
                        <x-nav-link :href="$navUrl('projects.index')" :active="request()->routeIs('projects.*')">
                            Proyectos
                        </x-nav-link>
                    @endcan
                    @if (Auth::user()->can('viewAny', App\Models\User::class) || Auth::user()->can('viewAny', App\Models\Asset::class) || Auth::user()->can('viewAny', App\Models\Asset2::class) || Auth::user()->can('viewAny', App\Models\Provider::class) || Auth::user()->can('viewAny', App\Models\ProductGroup::class))
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-1 border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none {{ request()->routeIs('users.*', 'assets.*', 'assets2.*', 'providers.*', 'product-catalog.*', 'activity-catalog.*') ? 'border-sky-500 text-stone-900' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700' }}">
                                    Maestras
                                    <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                @can('viewAny', App\Models\User::class)
                                    <x-dropdown-link :href="$navUrl('users.index')">Usuarios</x-dropdown-link>
                                @endcan
                                @can('viewAny', App\Models\Asset2::class)
                                    <x-dropdown-link :href="$navUrl('assets2.index')">Activos</x-dropdown-link>
                                @endcan
                                @can('viewAny', App\Models\Provider2::class)
                                    <x-dropdown-link :href="$navUrl('providers2.index')">Proveedores</x-dropdown-link>
                                @endcan
                                @can('viewAny', App\Models\ProductGroup::class)
                                    <x-dropdown-link :href="$navUrl('product-catalog.index')">Productos</x-dropdown-link>
                                    <x-dropdown-link :href="$navUrl('activity-catalog.index')">Actividades</x-dropdown-link>
                                @endcan
                            </x-slot>
                        </x-dropdown>
                    @endif
                    @can('viewAny', App\Models\Expense::class)
                        <x-nav-link :href="$navUrl('expenses.index')" :active="request()->routeIs('expenses.*')">
                            Gastos
                        </x-nav-link>
                    @endcan
                    @can('viewAny', App\Models\Purchase::class)
                        <x-nav-link :href="$navUrl('purchases.index')" :active="request()->routeIs('purchases.*')">
                            Compras
                        </x-nav-link>
                    @endcan
                    @can('reports.view')
                        <x-nav-link :href="$navUrl('reports.index')" :active="request()->routeIs('reports.*')">
                            Reportes
                        </x-nav-link>
                    @endcan
                    @can('viewAny', App\Models\Activity::class)
                        <x-nav-link :href="$navUrl('audit.index')" :active="request()->routeIs('audit.*')">
                            Auditoría
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:ms-6 sm:flex sm:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm font-medium leading-4 text-stone-600 transition hover:text-stone-900 focus:outline-none">
                            <div class="text-left">
                                <div>{{ Auth::user()->name }}</div>
                                <div class="text-xs text-stone-400">
                                    {{ Auth::user()->isSuperAdmin() ? 'SuperAdmin' : optional(Auth::user()->company)->name }}
                                </div>
                            </div>

                            <div class="ms-1">
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="$navUrl('profile.edit')">
                            Perfil
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ $navUrl('logout') }}">
                            @csrf

                            <x-dropdown-link
                                :href="$navUrl('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();"
                            >
                                Cerrar sesión
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-3"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-3"
        class="fixed inset-x-0 bottom-0 top-16 z-40 flex flex-col bg-stone-50 sm:hidden"
    >
        <div class="min-h-0 flex-1 overflow-y-auto">
        <div class="mx-auto max-w-sm space-y-1 px-4 py-4">

            {{-- User card --}}
            <div class="mb-4 flex items-center gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-stone-800 text-sm font-bold text-white">
                    {{ $mobileInitials }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-stone-800">{{ $authUser->name }}</p>
                    <p class="text-xs text-stone-400">{{ $mobileRoleLabel }}</p>
                </div>
            </div>

            @if ($authUser->isSuperAdmin())
                <a href="{{ $navUrl('dashboard') }}" class="mobile-nav-item {{ request()->routeIs('dashboard') ? 'mobile-nav-item--active' : '' }}">
                    <span class="mobile-nav-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                    </span>
                    <span>Panel</span>
                </a>
                <a href="{{ $navUrl('companies.index') }}" class="mobile-nav-item {{ request()->routeIs('companies.*') ? 'mobile-nav-item--active' : '' }}">
                    <span class="mobile-nav-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </span>
                    <span>Empresas</span>
                </a>
            @endif

            @can('viewAny', App\Models\Project::class)
                <a href="{{ $navUrl('projects.index') }}" class="mobile-nav-item {{ request()->routeIs('projects.*') ? 'mobile-nav-item--active' : '' }}">
                    <span class="mobile-nav-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </span>
                    <span>Proyectos</span>
                </a>
            @endcan

            @if ($authUser->can('viewAny', App\Models\User::class) || $authUser->can('viewAny', App\Models\Asset2::class) || $authUser->can('viewAny', App\Models\Provider2::class) || $authUser->can('viewAny', App\Models\ProductGroup::class))
                <button type="button" x-on:click="mastersOpen = ! mastersOpen"
                    class="mobile-nav-item w-full {{ request()->routeIs('users.*', 'assets.*', 'assets2.*', 'providers2.*', 'product-catalog.*', 'activity-catalog.*') ? 'mobile-nav-item--active' : '' }}">
                    <span class="mobile-nav-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                    </span>
                    <span class="flex-1 text-left">Maestras</span>
                    <svg class="h-4 w-4 shrink-0 text-stone-400 transition-transform duration-200" x-bind:class="mastersOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="mastersOpen" x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="ml-4 space-y-1 border-l-2 border-stone-200 pl-2">
                    @can('viewAny', App\Models\Provider2::class)
                        <a href="{{ $navUrl('providers2.index') }}" class="mobile-nav-item {{ request()->routeIs('providers2.*') ? 'mobile-nav-item--active' : '' }}">
                            <span class="mobile-nav-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
                            </span>
                            <span>Proveedores</span>
                        </a>
                    @endcan
                    @can('viewAny', App\Models\Asset2::class)
                        <a href="{{ $navUrl('assets2.index') }}" class="mobile-nav-item {{ request()->routeIs('assets2.*') ? 'mobile-nav-item--active' : '' }}">
                            <span class="mobile-nav-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                            </span>
                            <span>Activos</span>
                        </a>
                    @endcan
                    @can('viewAny', App\Models\ProductGroup::class)
                        <a href="{{ $navUrl('product-catalog.index') }}" class="mobile-nav-item {{ request()->routeIs('product-catalog.*') ? 'mobile-nav-item--active' : '' }}">
                            <span class="mobile-nav-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                            </span>
                            <span>Productos</span>
                        </a>
                    @endcan
                    @can('viewAny', App\Models\CatalogActivityGroup::class)
                        <a href="{{ $navUrl('activity-catalog.index') }}" class="mobile-nav-item {{ request()->routeIs('activity-catalog.*') ? 'mobile-nav-item--active' : '' }}">
                            <span class="mobile-nav-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </span>
                            <span>Actividades</span>
                        </a>
                    @endcan
                    @can('viewAny', App\Models\User::class)
                        <a href="{{ $navUrl('users.index') }}" class="mobile-nav-item {{ request()->routeIs('users.*') ? 'mobile-nav-item--active' : '' }}">
                            <span class="mobile-nav-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </span>
                            <span>Usuarios</span>
                        </a>
                    @endcan
                </div>
            @endif

            @can('viewAny', App\Models\Expense::class)
                <a href="{{ $navUrl('expenses.index') }}" class="mobile-nav-item {{ request()->routeIs('expenses.*') ? 'mobile-nav-item--active' : '' }}">
                    <span class="mobile-nav-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21l-7-7-7 7V5a2 2 0 012-2h10a2 2 0 012 2v16z" /></svg>
                    </span>
                    <span>Gastos</span>
                </a>
            @endcan

            @can('viewAny', App\Models\Purchase::class)
                <a href="{{ $navUrl('purchases.index') }}" class="mobile-nav-item {{ request()->routeIs('purchases.*') ? 'mobile-nav-item--active' : '' }}">
                    <span class="mobile-nav-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </span>
                    <span>Compras</span>
                </a>
            @endcan

            @can('reports.view')
                <a href="{{ $navUrl('reports.index') }}" class="mobile-nav-item {{ request()->routeIs('reports.*') ? 'mobile-nav-item--active' : '' }}">
                    <span class="mobile-nav-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </span>
                    <span>Reportes</span>
                </a>
            @endcan

            @can('viewAny', App\Models\Activity::class)
                <a href="{{ $navUrl('audit.index') }}" class="mobile-nav-item {{ request()->routeIs('audit.*') ? 'mobile-nav-item--active' : '' }}">
                    <span class="mobile-nav-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    </span>
                    <span>Auditoría</span>
                </a>
            @endcan

            <a href="{{ $navUrl('profile.edit') }}" class="mobile-nav-item {{ request()->routeIs('profile.*') ? 'mobile-nav-item--active' : '' }}">
                <span class="mobile-nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
                <span>Mi perfil</span>
            </a>

        </div>
        </div>

        {{-- Cerrar sesión siempre al fondo --}}
        <div class="shrink-0 border-t border-stone-200 bg-stone-50 px-4 py-3">
            <div class="mx-auto max-w-sm">
                <form method="POST" action="{{ $navUrl('logout') }}">
                    @csrf
                    <button type="submit" class="mobile-nav-item w-full text-rose-500 hover:text-rose-700">
                        <span class="mobile-nav-icon !bg-rose-50 !text-rose-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </span>
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
