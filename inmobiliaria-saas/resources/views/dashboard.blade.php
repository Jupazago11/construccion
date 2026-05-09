<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Panel principal" :description="$currentUser->isSuperAdmin() ? 'Visión global del SaaS sobre empresas, usuarios, proyectos y actividad financiera.' : ''" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-2 {{ $currentUser->isSuperAdmin() ? 'xl:grid-cols-3' : 'xl:grid-cols-4' }}">
                @if ($currentUser->isSuperAdmin())
                    <x-metric-card label="Empresas" :value="number_format($stats['companies'])" :hint="number_format($stats['active_companies']).' empresas activas'" />
                @endif
                <x-metric-card label="Usuarios" :value="number_format($stats['users'])" hint="Usuarios internos de la plataforma" />
                <x-metric-card label="Proyectos" :value="number_format($stats['projects'])" hint="Proyectos registrados" />
                <x-metric-card label="Gastos" :value="number_format($stats['expenses'])" hint="Registros de gastos creados" />
                <x-metric-card label="Volumen de gasto" :value="'$ '.number_format((float) $stats['expense_total'], 2)" hint="Monto total de gastos registrados" class="{{ $currentUser->isSuperAdmin() ? 'md:col-span-2 xl:col-span-1' : '' }}" />
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Dirección de la plataforma</h2>
                    <p class="mt-3 text-sm leading-6 text-stone-600">
                        La implementación actual ya está lista para la base operativa del módulo <code>construction_finance</code>.
                        El siguiente orden recomendado de construcción es proyectos, árbol de categorías, proveedores, gastos, adjuntos y finalmente reportes/exportaciones.
                    </p>
                    @can('viewAny', App\Models\Project::class)
                        <div class="mt-5">
                            <a href="{{ route('projects.index') }}" class="inline-flex items-center rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
                                Ir a proyectos
                            </a>
                        </div>
                    @endcan
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Acceso actual</h2>
                    <dl class="mt-4 space-y-4 text-sm text-stone-600">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-400">Usuario</dt>
                            <dd class="mt-1 font-medium text-stone-900">{{ $currentUser->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-400">Alcance</dt>
                            <dd class="mt-1">{{ $currentUser->isSuperAdmin() ? 'SaaS global' : ($currentUser->company?->name ?: 'Sin empresa asignada') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-400">Rol principal</dt>
                            <dd class="mt-1">{{ $currentUser->roles->pluck('name')->join(', ') ?: 'Sin rol asignado' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
