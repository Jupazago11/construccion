<x-app-layout x-data="reportsPage()" x-on:click="handleHistoryClick($event)">
    <x-slot name="header">
        <x-page-header title="Reportes" description="" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section x-data="{ filtersOpen: {{ $selectedProject ? 'false' : 'true' }} }" class="rounded-3xl border border-stone-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 px-5 py-4">
                    <h2 class="text-sm font-semibold text-stone-900">Filtros</h2>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50"
                        x-on:click="filtersOpen = !filtersOpen"
                        x-text="filtersOpen ? 'Ocultar' : 'Expandir'"
                    ></button>
                </div>

                <form method="GET" id="reports-filter-form" class="border-t border-stone-200 p-5">
                    <div
                        x-show="filtersOpen"
                        x-cloak
                        x-transition:enter="transition ease-out duration-[875ms]"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-[613ms]"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        class="grid gap-4 md:grid-cols-[180px_220px_220px_180px_180px_auto]"
                    >
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
                    </div>
                </form>
            </section>

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
                        @if ($seriesByDate->isNotEmpty())
                            <canvas id="expenses-by-date-chart"></canvas>
                        @else
                            <div class="flex h-full flex-col items-center justify-center gap-3 text-stone-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 16l4-5 4 3 4-6" />
                                </svg>
                                <p class="text-sm text-stone-400">Aún no hay {{ strtolower($movementLabel) }} en este período.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Participación por grupo</h2>
                    <div class="mt-4 h-80">
                        @if ($totalsByCategory->isNotEmpty())
                            <canvas id="expenses-by-category-chart"></canvas>
                        @else
                            <div class="flex h-full flex-col items-center justify-center gap-3 text-stone-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                                </svg>
                                <p class="text-sm text-stone-400">Aún no hay {{ strtolower($movementLabel) }} en este período.</p>
                            </div>
                        @endif
                    </div>
                    @if ($totalsByCategory->isNotEmpty())
                        <div id="category-chart-detail" class="mt-4 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-600">
                            Toca un color de la gráfica para ver valor y porcentaje.
                        </div>
                    @endif
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
            const palette = ['#93c5e8', '#b8a9dc', '#f4a7bb', '#f8c4a0', '#f5dc9a', '#a8d5b5', '#99d4d0', '#a0b4e8'];
            const currency = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });

            const dateCanvas = document.getElementById('expenses-by-date-chart');
            if (dateCanvas) {
                new Chart(dateCanvas, {
                    type: 'line',
                    data: {
                        labels: dateLabels,
                        datasets: [{
                            label: @js($movementLabel.' por fecha'),
                            data: dateValues,
                            borderColor: '#93c5e8',
                            pointBackgroundColor: palette,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            backgroundColor: 'rgba(147, 197, 232, 0.22)',
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
            }

            const categoryCanvas = document.getElementById('expenses-by-category-chart');
            if (categoryCanvas) {
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

                const categoryChart = new Chart(categoryCanvas, {
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
            }
        });
    </script>
</x-app-layout>
