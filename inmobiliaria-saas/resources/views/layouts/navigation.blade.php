@php
    $navUrl = fn (string $name, array $parameters = []) => '/'.ltrim(route($name, $parameters, false), '/');
    $mobileNavClass = fn (bool $active) => $active
        ? 'block w-full touch-manipulation border-l-4 border-sky-300 bg-sky-50 py-2 pe-4 ps-3 text-left text-base font-medium text-sky-800 transition duration-150 ease-in-out focus:bg-sky-100 focus:text-sky-900 focus:outline-none'
        : 'block w-full touch-manipulation border-l-4 border-transparent py-2 pe-4 ps-3 text-left text-base font-medium text-stone-600 transition duration-150 ease-in-out active:bg-stone-50 active:text-stone-900 focus:bg-stone-50 focus:text-stone-800 focus:outline-none';
@endphp

<nav x-data="{ open: false }" class="sticky top-0 z-50 border-b border-stone-200 bg-white sm:bg-white/95 sm:backdrop-blur">
    <!-- Primary Navigation Menu -->
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                <!-- Logo -->
                <div class="flex shrink-0 items-center">
                    <a href="{{ $navUrl(Auth::user()->homeRouteName()) }}">
                        <x-application-logo class="app-logo block h-9 w-auto fill-current text-stone-800" />
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
                    @can('viewAny', App\Models\User::class)
                        <x-nav-link :href="$navUrl('users.index')" :active="request()->routeIs('users.*')">
                            Usuarios
                        </x-nav-link>
                    @endcan
                    @can('viewAny', App\Models\Asset::class)
                        <x-nav-link :href="$navUrl('assets.index')" :active="request()->routeIs('assets.*')">
                            Activos
                        </x-nav-link>
                    @endcan
                    @can('viewAny', App\Models\Provider::class)
                        <x-nav-link :href="$navUrl('providers.index')" :active="request()->routeIs('providers.*')">
                            Proveedores
                        </x-nav-link>
                    @endcan
                    @can('viewAny', App\Models\Expense::class)
                        <x-nav-link :href="$navUrl('expenses.index')" :active="request()->routeIs('expenses.*')">
                            Gastos
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
    <div :class="{ 'block': open, 'hidden': ! open }" class="fixed inset-x-0 bottom-0 top-16 z-40 hidden overflow-y-auto bg-white sm:hidden">
        <div class="min-h-full bg-white">
            <div class="space-y-1 pb-3 pt-2">
                @if (Auth::user()->isSuperAdmin())
                    <form method="GET" action="{{ $navUrl('dashboard') }}">
                        <button type="submit" class="{{ $mobileNavClass(request()->routeIs('dashboard')) }}">Panel</button>
                    </form>
                    <form method="GET" action="{{ $navUrl('companies.index') }}">
                        <button type="submit" class="{{ $mobileNavClass(request()->routeIs('companies.*')) }}">Empresas</button>
                    </form>
                @endif
                @can('viewAny', App\Models\Project::class)
                    <form method="GET" action="{{ $navUrl('projects.index') }}">
                        <button type="submit" class="{{ $mobileNavClass(request()->routeIs('projects.*')) }}">Proyectos</button>
                    </form>
                @endcan
                @can('viewAny', App\Models\User::class)
                    <form method="GET" action="{{ $navUrl('users.index') }}">
                        <button type="submit" class="{{ $mobileNavClass(request()->routeIs('users.*')) }}">Usuarios</button>
                    </form>
                @endcan
                @can('viewAny', App\Models\Asset::class)
                    <form method="GET" action="{{ $navUrl('assets.index') }}">
                        <button type="submit" class="{{ $mobileNavClass(request()->routeIs('assets.*')) }}">Activos</button>
                    </form>
                @endcan
                @can('viewAny', App\Models\Provider::class)
                    <form method="GET" action="{{ $navUrl('providers.index') }}">
                        <button type="submit" class="{{ $mobileNavClass(request()->routeIs('providers.*')) }}">Proveedores</button>
                    </form>
                @endcan
                @can('viewAny', App\Models\Expense::class)
                    <form method="GET" action="{{ $navUrl('expenses.index') }}">
                        <button type="submit" class="{{ $mobileNavClass(request()->routeIs('expenses.*')) }}">Gastos</button>
                    </form>
                @endcan
                @can('reports.view')
                    <form method="GET" action="{{ $navUrl('reports.index') }}">
                        <button type="submit" class="{{ $mobileNavClass(request()->routeIs('reports.*')) }}">Reportes</button>
                    </form>
                @endcan
                @can('viewAny', App\Models\Activity::class)
                    <form method="GET" action="{{ $navUrl('audit.index') }}">
                        <button type="submit" class="{{ $mobileNavClass(request()->routeIs('audit.*')) }}">Auditoría</button>
                    </form>
                @endcan
            </div>

            <!-- Responsive Settings Options -->
            <div class="border-t border-gray-200 bg-white pb-1 pt-4">
                <div class="px-4">
                    <div class="text-base font-medium text-stone-800">{{ Auth::user()->name }}</div>
                    <div class="text-sm font-medium text-stone-500">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <form method="GET" action="{{ $navUrl('profile.edit') }}">
                        <button type="submit" class="{{ $mobileNavClass(request()->routeIs('profile.*')) }}">Perfil</button>
                    </form>

                    <!-- Authentication -->
                    <form method="POST" action="{{ $navUrl('logout') }}">
                        @csrf

                        <button type="submit" class="{{ $mobileNavClass(false) }}">
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>
