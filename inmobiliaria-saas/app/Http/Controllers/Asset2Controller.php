<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\Asset2StoreRequest;
use App\Http\Requests\Asset2UpdateRequest;
use App\Models\Asset2;
use App\Models\Asset2Novelty;
use App\Models\Asset2Type;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Asset2Controller extends Controller
{
    // Lista activos con filtros tenant y resume novedades/medios para la grilla principal.
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Asset2::class);

        $authUser = $request->user();
        $search = trim((string) $request->string('search'));
        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;

        $baseQuery = $this->buildIndexBaseQuery($request, $companyId, $search);

        $assets2 = (clone $baseQuery)
            ->with(['company', 'type'])
            ->withCount([
                'novelties as active_novelties_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
                'media as active_media_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            ])
            ->withSum([
                'novelties as active_novelties_cost_sum' => fn ($query) => $query
                    ->where('status', '!=', EntityStatus::Deleted->value)
                    ->whereHas('type', fn ($typeQuery) => $typeQuery->where('adds_value', true)),
            ], 'cost')
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('asset2_type', 'like', "%{$search}%")
                        ->orWhereHas('type', fn ($typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"))
                        ->orWhere('asset_condition', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('assets2._table_body', compact('assets2'))->render(),
                'pagination_html' => $assets2->links('pagination::tailwind')->toHtml(),
            ]);
        }

        return view('assets2.index', [
            'assets2' => $assets2,
            'companies' => $authUser->isSuperAdmin()
                ? Company::query()->where('status', '!=', EntityStatus::Deleted->value)->orderBy('name')->get()
                : collect(),
            'summary' => $this->resolveSummary($baseQuery),
            'filters' => [
                'search' => $search,
                'company_id' => $companyId,
            ],
        ]);
    }

    // Renderiza el modal de creación de activo con tipos disponibles según empresa.
    public function create(Request $request): View|string|RedirectResponse
    {
        $this->authorize('create', Asset2::class);

        if ($request->ajax()) {
            return view('assets2._modal_form', [
                'asset2' => new Asset2(['purchase_date' => today()]),
                'companies' => $this->companiesForForm($request->user()),
                'asset2Types' => $this->asset2TypesForForm($request, $request->user()->isSuperAdmin() ? $request->integer('company_id') ?: null : $request->user()->company_id),
                'action' => route('assets2.store', $request->query()),
                'method' => 'POST',
            ])->render();
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'La creación de activos 2 se realiza desde la vista principal.');
    }

    // Crea un activo y devuelve la fila parcial cuando el flujo es AJAX.
    public function store(Asset2StoreRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Asset2::class);

        $authUser = $request->user();
        $data = $request->validated();

        $asset2 = Asset2::query()->create([
            'company_id' => $authUser->isSuperAdmin() ? $data['company_id'] : $authUser->company_id,
            'name' => $data['name'],
            'asset2_type_id' => $data['asset2_type_id'],
            'asset2_type' => Asset2Type::query()->find($data['asset2_type_id'])?->name ?? '',
            'asset_condition' => $data['asset_condition'],
            'quantity' => (int) $data['quantity'],
            'purchase_value' => (float) ($data['purchase_value'] ?? 0),
            'purchase_date' => $data['purchase_date'] ?? null,
            'status' => EntityStatus::Active->value,
        ]);

        $this->loadAsset2ListRelations($asset2);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $asset2->id,
                'row_html' => view('assets2._row', compact('asset2'))->render(),
                'summary_html' => view('assets2._summary', [
                    'summary' => $this->resolveSummary($this->buildIndexBaseQuery(
                        $request,
                        $authUser->isSuperAdmin() ? $request->integer('company_id') ?: null : $authUser->company_id,
                        trim((string) $request->string('search'))
                    )),
                ])->render(),
                'message' => 'Activo 2 creado correctamente.',
            ]);
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'Activo 2 creado correctamente.');
    }

    // Renderiza el modal de edición de activo con sus tipos disponibles.
    public function edit(Request $request, Asset2 $asset2): View|string|RedirectResponse
    {
        $this->authorize('update', $asset2);

        if ($request->ajax()) {
            return view('assets2._modal_form', [
                'asset2' => $asset2,
                'companies' => $this->companiesForForm($request->user()),
                'asset2Types' => $this->asset2TypesForForm($request, $asset2->company_id),
                'action' => route('assets2.update', ['asset2' => $asset2] + $request->query()),
                'method' => 'PATCH',
            ])->render();
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'La edición de activos 2 se realiza desde la vista principal.');
    }

    // Actualiza los datos base del activo sin tocar media ni novedades.
    public function update(Asset2UpdateRequest $request, Asset2 $asset2): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $asset2);

        $authUser = $request->user();
        $data = $request->validated();

        $asset2->update([
            'company_id' => $authUser->isSuperAdmin() ? ($data['company_id'] ?? $asset2->company_id) : $asset2->company_id,
            'name' => $data['name'],
            'asset2_type_id' => $data['asset2_type_id'],
            'asset2_type' => Asset2Type::query()->find($data['asset2_type_id'])?->name ?? $asset2->asset2_type,
            'asset_condition' => $data['asset_condition'],
            'quantity' => (int) $data['quantity'],
            'purchase_value' => (float) ($data['purchase_value'] ?? 0),
            'purchase_date' => $data['purchase_date'] ?? null,
        ]);

        $this->loadAsset2ListRelations($asset2);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $asset2->id,
                'row_html' => view('assets2._row', compact('asset2'))->render(),
                'summary_html' => view('assets2._summary', [
                    'summary' => $this->resolveSummary($this->buildIndexBaseQuery(
                        $request,
                        $authUser->isSuperAdmin() ? $request->integer('company_id') ?: null : $authUser->company_id,
                        trim((string) $request->string('search'))
                    )),
                ])->render(),
                'message' => 'Activo 2 actualizado correctamente.',
            ]);
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'Activo 2 actualizado correctamente.');
    }

    // Archiva el activo si no existen dependencias activas que bloqueen la operación.
    public function destroy(Request $request, Asset2 $asset2): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $asset2);

        $asset2->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $asset2->id,
                'summary_html' => view('assets2._summary', [
                    'summary' => $this->resolveSummary($this->buildIndexBaseQuery(
                        $request,
                        $request->user()->isSuperAdmin() ? $request->integer('company_id') ?: null : $request->user()->company_id,
                        trim((string) $request->string('search'))
                    )),
                ])->render(),
                'message' => 'Activo 2 archivado correctamente.',
            ]);
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'Activo 2 archivado correctamente.');
    }

    // Devuelve empresas disponibles para formularios según el alcance del actor actual.
    protected function companiesForForm($authUser)
    {
        if ($authUser->isSuperAdmin()) {
            return Company::query()
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->orderBy('name')
                ->get();
        }

        return Company::query()
            ->whereKey($authUser->company_id)
            ->get();
    }

    // Precarga relaciones y agregados usados por los parciales de listado.
    protected function loadAsset2ListRelations(Asset2 $asset2): void
    {
        $asset2->load(['company', 'type']);
        $asset2->loadCount([
            'novelties as active_novelties_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            'media as active_media_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
        ]);
        $asset2->loadSum([
            'novelties as active_novelties_cost_sum' => fn ($query) => $query
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->whereHas('type', fn ($typeQuery) => $typeQuery->where('adds_value', true)),
        ], 'cost');
    }

    // Construye la query base reutilizable del índice de activos.
    protected function buildIndexBaseQuery(Request $request, ?int $companyId, string $search): Builder
    {
        return Asset2::query()
            ->when($companyId, fn (Builder $query) => $query->where('company_id', $companyId))
            ->when(! $request->user()->isSuperAdmin(), fn (Builder $query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('asset2_type', 'like', "%{$search}%")
                        ->orWhereHas('type', fn (Builder $typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"))
                        ->orWhere('asset_condition', 'like', "%{$search}%");
                });
            });
    }

    // Calcula métricas de resumen del módulo a partir de la misma query base del listado.
    protected function resolveSummary(Builder $baseQuery): array
    {
        $asset2Ids = (clone $baseQuery)->select('id');

        return [
            'assets2_purchase_total' => (clone $baseQuery)->get()->sum(fn (Asset2 $asset2) => (float) $asset2->purchase_value * (int) ($asset2->quantity ?: 1)),
            'assets2_count' => (clone $baseQuery)->count(),
            'novelties_cost_total' => Asset2Novelty::query()
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->whereIn('asset2_id', $asset2Ids)
                ->whereHas('type', fn ($typeQuery) => $typeQuery->where('adds_value', true))
                ->sum('cost'),
        ];
    }

    // Devuelve tipos de activo válidos para formularios, filtrados por empresa cuando aplica.
    protected function asset2TypesForForm(Request $request, ?int $companyId)
    {
        if (! $companyId) {
            return collect();
        }

        return Asset2Type::query()
            ->where('company_id', $companyId)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->withCount([
                'assets as active_assets_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Asset2Type $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'status' => $type->status,
                'can_delete' => ((int) $type->active_assets_count) === 0,
                'update_url' => route('asset2-types.update', $type),
                'delete_url' => route('asset2-types.destroy', $type),
            ])
            ->values();
    }
}
