<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$company->name" description="Resumen de la empresa, módulos habilitados y actividad operativa reciente.">
            @can('update', $company)
                <a href="{{ route('companies.edit', $company) }}" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                    Editar empresa
                </a>
            @endcan
            @can('create', App\Models\User::class)
                <a href="{{ route('users.create', ['company_id' => $company->id]) }}" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
                    Nuevo usuario
                </a>
            @endcan
            @can('create', App\Models\Project::class)
                <a href="{{ route('projects.create', ['company_id' => $company->id]) }}" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
                    Nuevo proyecto
                </a>
            @endcan
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1.2fr_0.8fr] lg:px-8">
            <div class="space-y-6">
                @if (session('status'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-3">
                    <x-metric-card label="Usuarios" :value="$company->users_count" />
                    <x-metric-card label="Proyectos" :value="$company->projects_count" />
                    <x-metric-card label="Proveedores" :value="$company->providers_count" />
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Detalle de la empresa</h2>
                    <dl class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-400">Razón social</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $company->legal_name ?: 'No registrada' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-400">Estado</dt>
                            <dd class="mt-1"><x-status-badge :value="$company->status" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-400">NIT</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $company->nit ?: 'No registrado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-400">Color principal</dt>
                            <dd class="mt-1 flex items-center gap-3 text-sm text-stone-700">
                                @if ($company->primary_color)
                                    <span class="h-4 w-4 rounded-full border border-stone-200" style="background-color: {{ $company->primary_color }}"></span>
                                @endif
                                {{ $company->primary_color ?: 'No definido' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-400">Correo</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $company->email ?: 'No registrado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-400">Teléfono</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $company->phone ?: 'No registrado' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-stone-900">Usuarios recientes</h2>
                        <a href="{{ route('users.index', ['company_id' => $company->id]) }}" class="text-sm font-medium text-stone-900 hover:text-stone-600">
                            Ver todos
                        </a>
                    </div>
                    <div class="mt-5 space-y-4">
                        @forelse ($company->users as $user)
                            <div class="flex items-center justify-between rounded-2xl border border-stone-100 p-4">
                                <div>
                                    <p class="font-medium text-stone-900">{{ $user->name }}</p>
                                    <p class="text-sm text-stone-500">{{ $user->email }}</p>
                                </div>
                                <x-status-badge :value="$user->status" />
                            </div>
                        @empty
                            <p class="text-sm text-stone-500">Aún no hay usuarios creados.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Disponibilidad de módulos</h2>
                    <div class="mt-5 space-y-3">
                        @foreach ($availableModules as $module)
                            @php
                                $enabledModule = $company->modules->firstWhere('module_id', $module->id);
                            @endphp
                            <div class="flex items-center justify-between rounded-2xl border border-stone-100 p-4">
                                <div>
                                    <p class="font-medium text-stone-900">{{ $module->name }}</p>
                                    <p class="text-sm text-stone-500">{{ $module->description }}</p>
                                </div>
                                <x-status-badge :value="$enabledModule?->status ?? 'inactive'" />
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-stone-900">Proyectos recientes</h2>
                    </div>
                    <div class="mt-5 space-y-4">
                        @forelse ($company->projects as $project)
                            <div class="rounded-2xl border border-stone-100 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-medium text-stone-900">{{ $project->name }}</p>
                                        <p class="text-sm text-stone-500">{{ $project->city ?: 'Ciudad no definida' }}</p>
                                    </div>
                                    <x-status-badge :value="$project->status" />
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-stone-500">Aún no hay proyectos registrados.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
