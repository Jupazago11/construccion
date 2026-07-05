<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\VehicleRecordStoreRequest;
use App\Http\Requests\VehicleRecordUpdateRequest;
use App\Models\VehicleRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VehicleRecordController extends Controller
{
    // Lista los registros del vehículo (ingresos y gastos) sin requerir autenticación.
    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->string('search'));
        $category = $request->string('category')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $records = $this->buildIndexQuery($search, $category, $dateFrom, $dateTo)
            ->latest('record_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('vehiculo._table_body', compact('records'))->render(),
                'pagination_html' => $records->links('pagination::tailwind')->toHtml(),
            ]);
        }

        return view('vehiculo.index', [
            'records' => $records,
            'summary' => $this->resolveSummary(),
            'filters' => [
                'search' => $search,
                'category' => $category,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    // Renderiza el modal de creación de un registro nuevo.
    // $request->query() se reenvía en la action y en los redirects de este controlador (create/store/
    // edit/update/destroy) para que filtros y página no se pierdan si el navegador no ejecuta el JS.
    public function create(Request $request): string
    {
        return view('vehiculo._modal_form', [
            'record' => new VehicleRecord(['category' => 'gasto']),
            'action' => route('vehiculo.store', $request->query()),
            'method' => 'POST',
        ])->render();
    }

    // Crea un registro con fecha automática y devuelve la fila/resumen actualizados.
    public function store(VehicleRecordStoreRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        $record = VehicleRecord::query()->create([
            'record_date' => today(),
            'category' => $data['category'],
            'concept' => $data['concept'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'status' => EntityStatus::Active->value,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $record->id,
                'row_html' => view('vehiculo._row', compact('record'))->render(),
                'summary_html' => view('vehiculo._summary', ['summary' => $this->resolveSummary()])->render(),
                'message' => 'Registro creado correctamente.',
            ]);
        }

        return redirect()->route('vehiculo.index', $request->query())->with('status', 'Registro creado correctamente.');
    }

    // Renderiza el modal de edición de un registro existente.
    public function edit(Request $request, VehicleRecord $record): string
    {
        return view('vehiculo._modal_form', [
            'record' => $record,
            'action' => route('vehiculo.update', ['record' => $record] + $request->query()),
            'method' => 'PATCH',
        ])->render();
    }

    // Actualiza el registro (la fecha de creación no se modifica) y recompone la fila/resumen visibles.
    public function update(VehicleRecordUpdateRequest $request, VehicleRecord $record): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        $record->update([
            'category' => $data['category'],
            'concept' => $data['concept'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $record->id,
                'row_html' => view('vehiculo._row', compact('record'))->render(),
                'summary_html' => view('vehiculo._summary', ['summary' => $this->resolveSummary()])->render(),
                'message' => 'Registro actualizado correctamente.',
            ]);
        }

        return redirect()->route('vehiculo.index', $request->query())->with('status', 'Registro actualizado correctamente.');
    }

    // Archiva el registro en vez de borrarlo físicamente.
    public function destroy(Request $request, VehicleRecord $record): JsonResponse|RedirectResponse
    {
        $record->update(['status' => EntityStatus::Deleted->value]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $record->id,
                'summary_html' => view('vehiculo._summary', ['summary' => $this->resolveSummary()])->render(),
                'message' => 'Registro archivado correctamente.',
            ]);
        }

        return redirect()->route('vehiculo.index', $request->query())->with('status', 'Registro archivado correctamente.');
    }

    // Muestra los indicadores del vehículo: semana seleccionada, histórico y series para gráficas.
    public function dashboard(Request $request): View
    {
        $baseQuery = fn () => VehicleRecord::query()->where('status', '!=', EntityStatus::Deleted->value);

        $currentWeekStart = now()->startOfWeek(Carbon::MONDAY)->startOfDay();

        $weeksWithData = $baseQuery()
            ->selectRaw("date_trunc('week', record_date)::date as week_start")
            ->groupBy('week_start')
            ->pluck('week_start')
            ->map(fn ($date) => Carbon::parse($date));

        $weekStarts = $weeksWithData
            ->push($currentWeekStart)
            ->unique(fn (Carbon $date) => $date->toDateString())
            ->sortByDesc(fn (Carbon $date) => $date->toDateString())
            ->values();

        $weekOptions = $weekStarts->map(function (Carbon $start) {
            $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

            return [
                'value' => $start->toDateString(),
                'label' => sprintf(
                    'S%d - %d de %s a %d de %s',
                    (int) $start->format('W'),
                    $start->day,
                    $start->locale('es')->translatedFormat('F'),
                    $end->day,
                    $end->locale('es')->translatedFormat('F')
                ),
            ];
        })->values();

        $requestedWeek = $request->string('week')->toString();
        $selectedWeekStart = $weekStarts->first(fn (Carbon $date) => $date->toDateString() === $requestedWeek) ?? $currentWeekStart;
        $selectedWeekEnd = $selectedWeekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $weekQuery = fn () => $baseQuery()->whereBetween('record_date', [
            $selectedWeekStart->toDateString(),
            $selectedWeekEnd->toDateString(),
        ]);

        $weekIngresos = (float) $weekQuery()->where('category', 'ingreso')->sum('amount');
        $weekGastos = (float) $weekQuery()->where('category', 'gasto')->sum('amount');

        $totalIngresos = $baseQuery()->where('category', 'ingreso')->sum('amount');
        $totalGastos = $baseQuery()->where('category', 'gasto')->sum('amount');
        $totalRegistros = $baseQuery()->count();

        // Serie mensual separada por ingresos/gastos, para poder graficar cada una y la rentabilidad (ingresos - gastos).
        $monthlyByCategory = $baseQuery()
            ->selectRaw("to_char(record_date, 'YYYY-MM') as month, category, SUM(amount) as total")
            ->groupBy('month', 'category')
            ->get()
            ->groupBy('month');

        $months = $monthlyByCategory->keys()->sort()->values();

        $monthlyIngresos = $months->map(fn ($month) => (float) ($monthlyByCategory[$month]->firstWhere('category', 'ingreso')->total ?? 0));
        $monthlyGastos = $months->map(fn ($month) => (float) ($monthlyByCategory[$month]->firstWhere('category', 'gasto')->total ?? 0));
        $monthlyBalance = $months->map(fn ($month, $index) => $monthlyIngresos[$index] - $monthlyGastos[$index]);
        $monthlyLabels = $months->map(fn ($month) => Carbon::createFromFormat('Y-m', $month)->locale('es')->translatedFormat('M Y'))->values();

        // Totales por categoría (dinámico, no asume solo ingreso/gasto) para la torta y su drill-down por concepto.
        $categoryLabels = ['ingreso' => 'Ingresos', 'gasto' => 'Gastos'];

        $buildCategoryTotals = fn (\Closure $scopedQuery) => $scopedQuery()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'value' => $row->category,
                'label' => $categoryLabels[$row->category] ?? ucfirst($row->category),
                'total' => (float) $row->total,
            ])
            ->values();

        $buildConceptTotals = fn (\Closure $scopedQuery) => $scopedQuery()
            ->selectRaw('category, concept, SUM(amount) as total')
            ->groupBy('category', 'concept')
            ->orderByDesc('total')
            ->get()
            ->groupBy('category')
            ->map(fn ($rows) => $rows->map(fn ($row) => [
                'concept' => $row->concept,
                'total' => (float) $row->total,
            ])->values());

        $selectedWeekLabel = collect($weekOptions)->firstWhere('value', $selectedWeekStart->toDateString())['label'] ?? '';

        return view('vehiculo.dashboard', [
            'weekOptions' => $weekOptions,
            'selectedWeek' => $selectedWeekStart->toDateString(),
            'selectedWeekLabel' => $selectedWeekLabel,
            'weekIngresos' => $weekIngresos,
            'weekGastos' => $weekGastos,
            'weekBalance' => $weekIngresos - $weekGastos,
            'weekRegistros' => (int) $weekQuery()->count(),
            'totalIngresos' => (float) $totalIngresos,
            'totalGastos' => (float) $totalGastos,
            'balance' => (float) $totalIngresos - (float) $totalGastos,
            'totalRegistros' => (int) $totalRegistros,
            'monthlyLabels' => $monthlyLabels,
            'monthlyIngresos' => $monthlyIngresos->values(),
            'monthlyGastos' => $monthlyGastos->values(),
            'monthlyBalance' => $monthlyBalance->values(),
            'categoryTotals' => $buildCategoryTotals($baseQuery),
            'conceptTotals' => $buildConceptTotals($baseQuery),
            'weekCategoryTotals' => $buildCategoryTotals($weekQuery),
            'weekConceptTotals' => $buildConceptTotals($weekQuery),
        ]);
    }

    // Construye la query base del listado con filtros de búsqueda, categoría y rango de fechas.
    protected function buildIndexQuery(string $search, string $category, string $dateFrom, string $dateTo)
    {
        return VehicleRecord::query()
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('record_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('record_date', '<=', $dateTo))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('concept', 'like', "%{$search}%");
                });
            });
    }

    // Calcula los totales visibles en la cabecera del listado.
    protected function resolveSummary(): array
    {
        $baseQuery = fn () => VehicleRecord::query()->where('status', '!=', EntityStatus::Deleted->value);

        return [
            'total_ingresos' => (float) $baseQuery()->where('category', 'ingreso')->sum('amount'),
            'total_gastos' => (float) $baseQuery()->where('category', 'gasto')->sum('amount'),
            'total_registros' => (int) $baseQuery()->count(),
        ];
    }
}
