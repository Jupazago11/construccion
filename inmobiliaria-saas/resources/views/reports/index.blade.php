<x-app-layout x-data="reportsPage()" x-on:click="handleHistoryClick($event)">
    <x-slot name="header">
        <x-page-header title="Reportes" description="" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form method="GET" id="reports-filter-form" class="grid gap-4 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm md:grid-cols-[180px_220px_220px_180px_180px_auto]">
                <div>
                    <x-input-label for="report_type" :value="'Indicadores'" />
                    <select id="report_type" name="report_type" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                        <option value="expense" @selected($filters['report_type'] === 'expense')>Gastos</option>
                        <option value="purchase" @selected($filters['report_type'] === 'purchase')>Compras</option>
                    </select>
                </div>

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
                        <option value="">Selecciona un proyecto</option>
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

            @if ($selectedProject)
            <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">{{ $movementSingular }} total</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-stone-900">$ {{ number_format((float) $summary['total_amount'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-stone-500">Suma de {{ strtolower($movementLabel) }} de {{ $selectedProject->name }}</p>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Total por grupo</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="bg-stone-50 text-left text-stone-500">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Grupo</th>
                                    <th class="px-4 py-3 font-medium">{{ $movementLabel }}</th>
                                    <th class="px-4 py-3 font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @forelse ($totalsByCategory as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-stone-900">{{ $item->name }}</td>
                                        <td class="px-4 py-3 text-stone-600">{{ number_format((int) ($item->movements_count ?? $item->expenses_count ?? 0)) }}</td>
                                        <td class="px-4 py-3 text-stone-900">$ {{ number_format((float) ($item->movement_total ?? $item->total_amount ?? 0), 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-8 text-center text-stone-500">Sin datos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Total por subgrupo</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="bg-stone-50 text-left text-stone-500">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Subgrupo</th>
                                    <th class="px-4 py-3 font-medium">{{ $movementLabel }}</th>
                                    <th class="px-4 py-3 font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @forelse ($totalsBySubcategory as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-stone-900">{{ $item->name }}</td>
                                        <td class="px-4 py-3 text-stone-600">{{ number_format((int) ($item->movements_count ?? $item->expenses_count ?? 0)) }}</td>
                                        <td class="px-4 py-3 text-stone-900">$ {{ number_format((float) ($item->movement_total ?? $item->total_amount ?? 0), 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-8 text-center text-stone-500">Sin datos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Total por producto</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="bg-stone-50 text-left text-stone-500">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Producto</th>
                                    <th class="px-4 py-3 font-medium">{{ $movementLabel }}</th>
                                    <th class="px-4 py-3 font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @forelse ($totalsByAuxiliary as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-stone-900">{{ $item->name }}</td>
                                        <td class="px-4 py-3 text-stone-600">{{ number_format((int) ($item->movements_count ?? $item->expenses_count ?? 0)) }}</td>
                                        <td class="px-4 py-3 text-stone-900">$ {{ number_format((float) ($item->movement_total ?? $item->total_amount ?? 0), 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-8 text-center text-stone-500">Sin datos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">{{ $movementLabel }} por fecha</h2>
                    <div class="mt-4 h-80">
                        <canvas id="expenses-by-date-chart"></canvas>
                    </div>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Participación por grupo</h2>
                    <div class="mt-4 h-80">
                        <canvas id="expenses-by-category-chart"></canvas>
                    </div>
                    <div id="category-chart-detail" class="mt-4 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-600">
                        Toca un color de la gráfica para ver valor y porcentaje.
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-900">Histórico detallado</h2>
                <div x-ref="historyBlock">
                    @include('reports._history', ['history' => $history])
                </div>
            </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterForm = document.getElementById('reports-filter-form');
            const reportTypeSelect = document.getElementById('report_type');
            const projectSelect = document.getElementById('project_id');
            const dateFromInput = document.getElementById('date_from');
            const dateToInput = document.getElementById('date_to');
            const projectRanges = @json($projectRanges);
            const hasSelectedProject = @json((bool) $selectedProject);

            const applyProjectDateRange = (projectId) => {
                if (!projectId || !projectRanges[projectId]) {
                    return false;
                }

                const range = projectRanges[projectId];

                if (range.oldest_movement_date) {
                    dateFromInput.value = range.oldest_movement_date;
                }

                dateToInput.value = range.today;

                return true;
            };

            projectSelect?.addEventListener('change', () => {
                if (applyProjectDateRange(projectSelect.value) && filterForm) {
                    filterForm.submit();
                }
            });

            reportTypeSelect?.addEventListener('change', () => {
                if (applyProjectDateRange(projectSelect?.value) && filterForm) {
                    filterForm.submit();
                    return;
                }

                filterForm?.submit();
            });

            if (!hasSelectedProject || typeof Chart === 'undefined') {
                return;
            }

            const dateLabels = @json($seriesByDate->pluck('movement_date')->map(fn ($date) => \Illuminate\Support\Carbon::parse($date)->format('Y-m-d'))->values());
            const dateValues = @json($seriesByDate->pluck('movement_total')->map(fn ($value) => (float) $value)->values());

            const categoryLabels = @json($totalsByCategory->take(8)->pluck('name')->values());
            const categoryValues = @json($totalsByCategory->take(8)->pluck('movement_total')->map(fn ($value) => (float) $value)->values());
            const palette = ['#0f766e', '#0284c7', '#7c3aed', '#db2777', '#ea580c', '#ca8a04', '#65a30d', '#2563eb'];
            const currency = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });

            new Chart(document.getElementById('expenses-by-date-chart'), {
                type: 'line',
                data: {
                    labels: dateLabels,
                    datasets: [{
                        label: @js($movementLabel.' por fecha'),
                        data: dateValues,
                        borderColor: '#0284c7',
                        pointBackgroundColor: palette,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        backgroundColor: 'rgba(2, 132, 199, 0.18)',
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

            const categoryChartDetail = document.getElementById('category-chart-detail');
            const categoryTotal = categoryValues.reduce((sum, value) => sum + value, 0);

            const renderCategoryDetail = (index) => {
                if (!categoryChartDetail || index === null || categoryLabels[index] === undefined) {
                    return;
                }

                const value = Number(categoryValues[index] ?? 0);
                const percentage = categoryTotal > 0 ? ((value / categoryTotal) * 100) : 0;

                categoryChartDetail.innerHTML = `
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Grupo</div>
                            <div class="mt-1 font-semibold text-stone-900">${categoryLabels[index]}</div>
                        </div>
                        <span class="inline-flex h-3.5 w-3.5 rounded-full" style="background:${palette[index % palette.length]}"></span>
                    </div>
                    <div class="mt-3 flex items-end justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Valor</div>
                            <div class="mt-1 text-lg font-semibold text-stone-900">$ ${currency.format(value)}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Participación</div>
                            <div class="mt-1 text-lg font-semibold text-stone-900">${percentage.toFixed(1)}%</div>
                        </div>
                    </div>
                `;
            };

            const categoryChart = new Chart(document.getElementById('expenses-by-category-chart'), {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryValues,
                        backgroundColor: palette,
                        borderColor: '#ffffff',
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: (_, elements) => {
                        if (!elements.length) {
                            return;
                        }

                        renderCategoryDetail(elements[0].index);
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            onClick: (_, legendItem) => {
                                renderCategoryDetail(legendItem.index);
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const value = Number(context.raw ?? 0);
                                    const percentage = categoryTotal > 0 ? ((value / categoryTotal) * 100) : 0;

                                    return `${context.label}: $ ${currency.format(value)} (${percentage.toFixed(1)}%)`;
                                },
                            },
                        },
                    },
                },
            });

            if (categoryLabels.length > 0) {
                renderCategoryDetail(0);
                categoryChart.setActiveElements([{ datasetIndex: 0, index: 0 }]);
                categoryChart.update();
            }
        });
    </script>
</x-app-layout>
