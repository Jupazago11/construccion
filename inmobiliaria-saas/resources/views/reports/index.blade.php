<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Reportes" description="" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form method="GET" class="grid gap-4 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm md:grid-cols-[220px_220px_180px_180px_auto]">
                @if (auth()->user()->isSuperAdmin())
                    <div>
                        <x-input-label for="company_id" :value="'Empresa'" />
                        <select id="company_id" name="company_id" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                            <option value="">Todas</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected((string) $filters['company_id'] === (string) $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <x-input-label for="project_id" :value="'Proyecto'" />
                    <select id="project_id" name="project_id" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                        <option value="">Todos</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) $filters['project_id'] === (string) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="date_from" :value="'Desde'" />
                    <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="$filters['date_from']" />
                </div>

                <div>
                    <x-input-label for="date_to" :value="'Hasta'" />
                    <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="$filters['date_to']" />
                </div>

                <div class="flex items-end">
                    <x-primary-button class="w-full justify-center md:w-auto">Filtrar</x-primary-button>
                </div>
            </form>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-metric-card label="Volumen total" :value="'$ '.number_format((float) $summary['total_amount'], 2, ',', '.')" hint="Suma de gastos filtrados" />
                <x-metric-card label="Gastos" :value="number_format((int) $summary['expenses_count'])" hint="Registros dentro del rango" />
                <x-metric-card label="Proyectos" :value="number_format((int) $summary['projects_count'])" hint="Proyectos con movimiento" />
                <x-metric-card label="Ticket promedio" :value="'$ '.number_format((float) $summary['average_ticket'], 2, ',', '.')" hint="Promedio por gasto" />
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Gastos por fecha</h2>
                    <div class="mt-4 h-80">
                        <canvas id="expenses-by-date-chart"></canvas>
                    </div>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Participación por categoría</h2>
                    <div class="mt-4 h-80">
                        <canvas id="expenses-by-category-chart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Total por proyecto</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="bg-stone-50 text-left text-stone-500">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Proyecto</th>
                                    <th class="px-4 py-3 font-medium">Gastos</th>
                                    <th class="px-4 py-3 font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @forelse ($totalsByProject as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-stone-900">{{ $item->name }}</td>
                                        <td class="px-4 py-3 text-stone-600">{{ number_format((int) $item->expenses_count) }}</td>
                                        <td class="px-4 py-3 text-stone-900">{{ number_format((float) $item->total_amount, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-8 text-center text-stone-500">Sin datos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Total por auxiliar</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="bg-stone-50 text-left text-stone-500">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Auxiliar</th>
                                    <th class="px-4 py-3 font-medium">Gastos</th>
                                    <th class="px-4 py-3 font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @forelse ($totalsByAuxiliary as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-stone-900">{{ $item->name }}</td>
                                        <td class="px-4 py-3 text-stone-600">{{ number_format((int) $item->expenses_count) }}</td>
                                        <td class="px-4 py-3 text-stone-900">{{ number_format((float) $item->total_amount, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-8 text-center text-stone-500">Sin datos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Total por categoría</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="bg-stone-50 text-left text-stone-500">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Categoría</th>
                                    <th class="px-4 py-3 font-medium">Gastos</th>
                                    <th class="px-4 py-3 font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @forelse ($totalsByCategory as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-stone-900">{{ $item->name }}</td>
                                        <td class="px-4 py-3 text-stone-600">{{ number_format((int) $item->expenses_count) }}</td>
                                        <td class="px-4 py-3 text-stone-900">{{ number_format((float) $item->total_amount, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-8 text-center text-stone-500">Sin datos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Total por subcategoría</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="bg-stone-50 text-left text-stone-500">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Subcategoría</th>
                                    <th class="px-4 py-3 font-medium">Gastos</th>
                                    <th class="px-4 py-3 font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @forelse ($totalsBySubcategory as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-stone-900">{{ $item->name }}</td>
                                        <td class="px-4 py-3 text-stone-600">{{ number_format((int) $item->expenses_count) }}</td>
                                        <td class="px-4 py-3 text-stone-900">{{ number_format((float) $item->total_amount, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-8 text-center text-stone-500">Sin datos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-900">Histórico detallado</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                        <thead class="bg-stone-50 text-left text-stone-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">Fecha</th>
                                <th class="px-4 py-3 font-medium">Número</th>
                                <th class="px-4 py-3 font-medium">Proyecto</th>
                                <th class="px-4 py-3 font-medium">Clasificación</th>
                                <th class="px-4 py-3 font-medium">Proveedor</th>
                                <th class="px-4 py-3 font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($history as $expense)
                                <tr>
                                    <td class="px-4 py-3 text-stone-600">{{ $expense->expense_date?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-stone-900">{{ $expense->expense_number }}</td>
                                    <td class="px-4 py-3 text-stone-600">{{ $expense->project?->name }}</td>
                                    <td class="px-4 py-3 text-stone-600">
                                        <div>{{ $expense->category?->name }}</div>
                                        <div>{{ $expense->subcategory?->name }}</div>
                                        <div>{{ $expense->auxiliary?->name ?: 'Sin auxiliar' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-stone-600">{{ $expense->provider?->name ?: 'Sin proveedor' }}</td>
                                    <td class="px-4 py-3 text-stone-900">{{ number_format((float) $expense->total_amount, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-stone-500">Sin datos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $history->links() }}
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof Chart === 'undefined') {
                return;
            }

            const dateLabels = @json($seriesByDate->pluck('expense_date')->map(fn ($date) => \Illuminate\Support\Carbon::parse($date)->format('Y-m-d'))->values());
            const dateValues = @json($seriesByDate->pluck('total_amount')->map(fn ($value) => (float) $value)->values());

            const categoryLabels = @json($totalsByCategory->take(8)->pluck('name')->values());
            const categoryValues = @json($totalsByCategory->take(8)->pluck('total_amount')->map(fn ($value) => (float) $value)->values());

            new Chart(document.getElementById('expenses-by-date-chart'), {
                type: 'line',
                data: {
                    labels: dateLabels,
                    datasets: [{
                        label: 'Gastos por fecha',
                        data: dateValues,
                        borderColor: '#1c1917',
                        backgroundColor: 'rgba(28, 25, 23, 0.08)',
                        tension: 0.35,
                        fill: true,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                },
            });

            new Chart(document.getElementById('expenses-by-category-chart'), {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryValues,
                        backgroundColor: ['#1c1917', '#57534e', '#a8a29e', '#0f766e', '#0369a1', '#b45309', '#be123c', '#4d7c0f'],
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                    },
                },
            });
        });
    </script>
</x-app-layout>
