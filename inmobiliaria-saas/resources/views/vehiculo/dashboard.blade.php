<x-public-layout>
    <x-slot name="header">
        <x-page-header title="Indicadores del vehículo" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form method="GET" class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm">
                <x-input-label for="week" :value="'Semana'" />
                <select
                    id="week"
                    name="week"
                    class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900 md:max-w-sm"
                    onchange="this.form.submit()"
                >
                    @foreach ($weekOptions as $option)
                        <option value="{{ $option['value'] }}" @selected($option['value'] === $selectedWeek)>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </form>

            <div>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-[0.14em] text-stone-400">Semana seleccionada</h2>
                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Ingresos</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight text-stone-900">$ {{ number_format($weekIngresos, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Gastos</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight text-stone-900">$ {{ number_format($weekGastos, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Balance</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight {{ $weekBalance >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">$ {{ number_format($weekBalance, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Registros</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight text-stone-900">{{ number_format($weekRegistros) }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Ingresos por mes</h2>
                    <div class="mt-4 h-64">
                        @if ($monthlyIngresos->sum() > 0)
                            <canvas id="vehiculo-monthly-ingresos-chart"></canvas>
                        @else
                            <div class="flex h-full flex-col items-center justify-center gap-2 text-stone-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 16l4-5 4 3 4-6" />
                                </svg>
                                <p class="text-xs text-stone-400">Sin ingresos registrados.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Gastos por mes</h2>
                    <div class="mt-4 h-64">
                        @if ($monthlyGastos->sum() > 0)
                            <canvas id="vehiculo-monthly-gastos-chart"></canvas>
                        @else
                            <div class="flex h-full flex-col items-center justify-center gap-2 text-stone-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 16l4-5 4 3 4-6" />
                                </svg>
                                <p class="text-xs text-stone-400">Sin gastos registrados.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Rentabilidad por mes</h2>
                    <div class="mt-4 h-64">
                        @if ($monthlyLabels->isNotEmpty())
                            <canvas id="vehiculo-monthly-balance-chart"></canvas>
                        @else
                            <div class="flex h-full flex-col items-center justify-center gap-2 text-stone-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 16l4-5 4 3 4-6" />
                                </svg>
                                <p class="text-xs text-stone-400">Aún no hay registros.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="relative rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <button
                        type="button"
                        id="category-view-toggle"
                        onclick="window.__toggleCategoryView()"
                        class="inline-flex items-center gap-2 rounded-2xl border border-stone-300 px-3 py-2 text-xs font-semibold text-stone-600 transition hover:bg-stone-50"
                        title="Alternar entre histórico y la semana seleccionada"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                        </svg>
                        <span id="category-view-label">Histórico</span>
                    </button>
                </div>

                <h2 class="mt-4 text-lg font-semibold text-stone-900">Ingresos vs. gastos</h2>
                <p class="mt-1 text-sm text-stone-500">Toca un color para ver el detalle por concepto.</p>
                <div class="mt-4 grid gap-6 lg:grid-cols-[1fr_320px] lg:items-center">
                    <div class="relative h-80">
                        <canvas id="vehiculo-category-chart"></canvas>
                        <div id="category-chart-empty" class="absolute inset-0 hidden flex-col items-center justify-center gap-3 text-stone-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                            <p class="text-sm text-stone-400">Sin registros en este período.</p>
                        </div>
                    </div>
                    <div id="category-chart-detail" class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-600">
                        Toca un color de la gráfica para ver valor y porcentaje.
                    </div>
                </div>
            </div>

            <div>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-[0.14em] text-stone-400">Histórico</h2>
                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Total ingresos</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight text-stone-900">$ {{ number_format($totalIngresos, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Total gastos</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight text-stone-900">$ {{ number_format($totalGastos, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Balance</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight {{ $balance >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">$ {{ number_format($balance, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Registros</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight text-stone-900">{{ number_format($totalRegistros) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de detalle por concepto (drill-down al hacer clic en una categoría de la torta) --}}
    <div id="concept-modal" class="fixed inset-0 z-50 hidden overflow-hidden">
        <div class="fixed inset-0 bg-stone-900/50" onclick="closeConceptModal()"></div>
        <div class="relative flex h-full w-full items-center justify-center px-3 py-3 sm:px-6 sm:py-6">
            <div class="grid max-h-[88dvh] w-full max-w-2xl grid-rows-[auto,minmax(0,1fr)] overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-stone-200 px-6 py-5">
                    <h2 id="concept-modal-title" class="text-xl font-semibold text-stone-900">Detalle por concepto</h2>
                    <button type="button" class="rounded-full p-2 text-stone-500 transition hover:bg-stone-100 hover:text-stone-900" onclick="closeConceptModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <div class="min-h-0 overflow-y-auto px-6 py-6">
                    <div class="relative h-80">
                        <canvas id="vehiculo-concept-chart"></canvas>
                        <div id="concept-chart-empty" class="absolute inset-0 hidden flex-col items-center justify-center gap-3 text-stone-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                            <p class="text-sm text-stone-400">Sin registros en este período.</p>
                        </div>
                    </div>
                    <div id="concept-modal-detail" class="mt-4 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-600"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof Chart === 'undefined') {
                return;
            }

            const palette = ['#93c5e8', '#b8a9dc', '#f4a7bb', '#f8c4a0', '#f5dc9a', '#a8d5b5', '#99d4d0', '#a0b4e8'];
            const currency = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });
            const monthlyLabels = @json($monthlyLabels);

            const buildLineChart = (canvasId, label, data, color) => {
                const canvas = document.getElementById(canvasId);
                if (!canvas) {
                    return;
                }

                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: monthlyLabels,
                        datasets: [{
                            label,
                            data,
                            borderColor: color,
                            pointBackgroundColor: color,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 3,
                            backgroundColor: color + '38',
                            tension: 0.35,
                            fill: true,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (context) => `$ ${currency.format(Number(context.raw ?? 0))}`,
                                },
                            },
                        },
                    },
                });
            };

            buildLineChart('vehiculo-monthly-ingresos-chart', 'Ingresos', @json($monthlyIngresos), '#a8d5b5');
            buildLineChart('vehiculo-monthly-gastos-chart', 'Gastos', @json($monthlyGastos), '#f4a7bb');

            const balanceCanvas = document.getElementById('vehiculo-monthly-balance-chart');
            if (balanceCanvas) {
                const balanceValues = @json($monthlyBalance);
                const balanceColors = balanceValues.map((value) => (value >= 0 ? '#a8d5b5' : '#f4a7bb'));

                new Chart(balanceCanvas, {
                    type: 'bar',
                    data: {
                        labels: monthlyLabels,
                        datasets: [{
                            label: 'Rentabilidad',
                            data: balanceValues,
                            backgroundColor: balanceColors,
                            borderRadius: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (context) => `$ ${currency.format(Number(context.raw ?? 0))}`,
                                },
                            },
                        },
                    },
                });
            }

            // Torta de categorías: dinámica según lo que exista realmente en la base de datos,
            // con toggle (ícono de ojo) entre vista histórica y la semana seleccionada.
            const categoryCanvas = document.getElementById('vehiculo-category-chart');
            if (categoryCanvas) {
                const dataByMode = {
                    historico: {
                        label: 'Histórico',
                        categories: @json($categoryTotals),
                        concepts: @json($conceptTotals),
                    },
                    semana: {
                        label: @js($selectedWeekLabel),
                        categories: @json($weekCategoryTotals),
                        concepts: @json($weekConceptTotals),
                    },
                };

                let categoryMode = 'historico';
                let categoryChart = null;
                let conceptChart = null;
                let activeCategoryKeys = [];
                let activeCategoryLabels = [];

                const categoryChartDetail = document.getElementById('category-chart-detail');
                const categoryChartEmpty = document.getElementById('category-chart-empty');
                const categoryViewLabel = document.getElementById('category-view-label');

                const renderCategoryDetail = (index) => {
                    if (!categoryChartDetail || index === null || activeCategoryLabels[index] === undefined) {
                        return;
                    }

                    const categoryValues = dataByMode[categoryMode].categories.map((item) => item.total);
                    const categoryTotal = categoryValues.reduce((sum, value) => sum + value, 0);
                    const value = Number(categoryValues[index] ?? 0);
                    const percentage = categoryTotal > 0 ? ((value / categoryTotal) * 100) : 0;

                    categoryChartDetail.innerHTML = `
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Categoría</div>
                                <div class="mt-1 font-semibold text-stone-900">${activeCategoryLabels[index]}</div>
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
                        <button type="button" class="mt-3 w-full rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50" onclick="window.__openConceptModal(${index})">
                            Ver conceptos de ${activeCategoryLabels[index]}
                        </button>
                    `;
                };

                const renderCategoryChart = () => {
                    const current = dataByMode[categoryMode];
                    activeCategoryLabels = current.categories.map((item) => item.label);
                    activeCategoryKeys = current.categories.map((item) => item.value);
                    const categoryValues = current.categories.map((item) => item.total);
                    const categoryTotal = categoryValues.reduce((sum, value) => sum + value, 0);

                    if (categoryChart) {
                        categoryChart.destroy();
                        categoryChart = null;
                    }

                    if (categoryTotal <= 0) {
                        categoryChartEmpty?.classList.remove('hidden');
                        categoryChartEmpty?.classList.add('flex');
                        categoryCanvas.classList.add('hidden');
                        if (categoryChartDetail) {
                            categoryChartDetail.innerHTML = 'Sin registros en este período.';
                        }
                        return;
                    }

                    categoryChartEmpty?.classList.add('hidden');
                    categoryChartEmpty?.classList.remove('flex');
                    categoryCanvas.classList.remove('hidden');

                    categoryChart = new Chart(categoryCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: activeCategoryLabels,
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
                                window.__openConceptModal(elements[0].index);
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

                    renderCategoryDetail(0);
                };

                window.__toggleCategoryView = () => {
                    categoryMode = categoryMode === 'historico' ? 'semana' : 'historico';
                    if (categoryViewLabel) {
                        categoryViewLabel.textContent = dataByMode[categoryMode].label;
                    }
                    renderCategoryChart();
                };

                renderCategoryChart();

                // Modal de detalle por concepto dentro de la categoría seleccionada (respeta la vista activa).
                window.__openConceptModal = (categoryIndex) => {
                    const categoryKey = activeCategoryKeys[categoryIndex];
                    const categoryLabel = activeCategoryLabels[categoryIndex];
                    const concepts = dataByMode[categoryMode].concepts[categoryKey] ?? [];

                    document.getElementById('concept-modal-title').textContent = `${categoryLabel} por concepto`;
                    document.getElementById('concept-modal').classList.remove('hidden');

                    const conceptCanvas = document.getElementById('vehiculo-concept-chart');
                    const conceptEmpty = document.getElementById('concept-chart-empty');
                    const conceptLabels = concepts.map((item) => item.concept);
                    const conceptValues = concepts.map((item) => item.total);
                    const conceptTotal = conceptValues.reduce((sum, value) => sum + value, 0);
                    const detailEl = document.getElementById('concept-modal-detail');

                    const renderConceptDetail = (index) => {
                        if (!detailEl || index === null || conceptLabels[index] === undefined) {
                            return;
                        }

                        const value = Number(conceptValues[index] ?? 0);
                        const percentage = conceptTotal > 0 ? ((value / conceptTotal) * 100) : 0;

                        detailEl.innerHTML = `
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold text-stone-900">${conceptLabels[index]}</span>
                                <span class="inline-flex h-3 w-3 rounded-full" style="background:${palette[index % palette.length]}"></span>
                            </div>
                            <div class="mt-2 flex items-end justify-between gap-3">
                                <span class="text-lg font-semibold text-stone-900">$ ${currency.format(value)}</span>
                                <span class="text-sm text-stone-500">${percentage.toFixed(1)}%</span>
                            </div>
                        `;
                    };

                    if (conceptChart) {
                        conceptChart.destroy();
                        conceptChart = null;
                    }

                    if (detailEl) {
                        detailEl.innerHTML = '';
                    }

                    if (conceptTotal <= 0) {
                        conceptCanvas?.classList.add('hidden');
                        conceptEmpty?.classList.remove('hidden');
                        conceptEmpty?.classList.add('flex');
                        if (detailEl) {
                            detailEl.innerHTML = 'Sin registros en este período.';
                        }
                        return;
                    }

                    conceptCanvas?.classList.remove('hidden');
                    conceptEmpty?.classList.add('hidden');
                    conceptEmpty?.classList.remove('flex');

                    // Se crea en el siguiente frame para que el modal ya esté visible y el canvas mida su tamaño real.
                    window.requestAnimationFrame(() => {
                        conceptChart = new Chart(conceptCanvas, {
                            type: 'doughnut',
                            data: {
                                labels: conceptLabels,
                                datasets: [{
                                    data: conceptValues,
                                    backgroundColor: palette,
                                    borderColor: '#ffffff',
                                    borderWidth: 2,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                onClick: (_, elements) => {
                                    if (elements.length) {
                                        renderConceptDetail(elements[0].index);
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        onClick: (_, legendItem) => renderConceptDetail(legendItem.index),
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: (context) => {
                                                const value = Number(context.raw ?? 0);
                                                const percentage = conceptTotal > 0 ? ((value / conceptTotal) * 100) : 0;

                                                return `${context.label}: $ ${currency.format(value)} (${percentage.toFixed(1)}%)`;
                                            },
                                        },
                                    },
                                },
                            },
                        });

                        renderConceptDetail(0);
                    });
                };
            }
        });

        function closeConceptModal() {
            document.getElementById('concept-modal').classList.add('hidden');
        }
    </script>
</x-public-layout>
