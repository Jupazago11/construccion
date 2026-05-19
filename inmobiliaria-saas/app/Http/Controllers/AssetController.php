<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\AssetStoreRequest;
use App\Http\Requests\AssetUpdateRequest;
use App\Models\Asset;
use App\Models\AssetNovelty;
use App\Models\AssetType;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;

class AssetController extends Controller
{
    // Lista activos con filtros por empresa y búsqueda, y soporta refresco parcial por AJAX.
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Asset::class);

        $authUser = $request->user();
        $search = trim((string) $request->string('search'));
        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;

        $baseQuery = $this->buildIndexBaseQuery($request, $companyId, $search);

        $assets = (clone $baseQuery)
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
                        ->orWhere('asset_type', 'like', "%{$search}%")
                        ->orWhereHas('type', fn ($typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"))
                        ->orWhere('asset_condition', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('assets._table_body', compact('assets'))->render(),
                'pagination_html' => ViewFacade::make('pagination::tailwind', ['paginator' => $assets])->render(),
            ]);
        }

        return view('assets.index', [
            'assets' => $assets,
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

    // Renderiza el modal de creación desde la vista principal y precarga empresas y tipos disponibles.
    public function create(Request $request): View|string|RedirectResponse
    {
        $this->authorize('create', Asset::class);

        if ($request->ajax()) {
            return view('assets._modal_form', [
                'asset' => new Asset(['purchase_date' => today()]),
                'companies' => $this->companiesForForm($request->user()),
                'assetTypes' => $this->assetTypesForForm($request, $request->user()->isSuperAdmin() ? $request->integer('company_id') ?: null : $request->user()->company_id),
                'action' => route('assets.store', $request->query()),
                'method' => 'POST',
            ])->render();
        }

        return redirect()
            ->route('assets.index')
            ->with('status', 'La creación de activos se realiza desde la vista principal.');
    }

    // Crea un activo, recarga sus agregados y devuelve la fila/resumen actualizados cuando aplica.
    public function store(AssetStoreRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Asset::class);

        $authUser = $request->user();
        $data = $request->validated();

        $asset = Asset::query()->create([
            'company_id' => $authUser->isSuperAdmin() ? $data['company_id'] : $authUser->company_id,
            'name' => $data['name'],
            'asset_type_id' => $data['asset_type_id'],
            'asset_type' => AssetType::query()->find($data['asset_type_id'])?->name ?? '',
            'asset_condition' => $data['asset_condition'],
            'quantity' => (int) $data['quantity'],
            'purchase_value' => (float) ($data['purchase_value'] ?? 0),
            'purchase_date' => $data['purchase_date'] ?? null,
            'status' => EntityStatus::Active->value,
        ]);

        $this->loadAssetListRelations($asset);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $asset->id,
                'row_html' => view('assets._row', compact('asset'))->render(),
                'summary_html' => view('assets._summary', [
                    'summary' => $this->resolveSummary($this->buildIndexBaseQuery(
                        $request,
                        $authUser->isSuperAdmin() ? $request->integer('company_id') ?: null : $authUser->company_id,
                        trim((string) $request->string('search'))
                    )),
                ])->render(),
                'message' => 'Activo creado correctamente.',
            ]);
        }

        return redirect()
            ->route('assets.index')
            ->with('status', 'Activo creado correctamente.');
    }

    // Renderiza el modal de edición reutilizando la misma vista del formulario.
    public function edit(Request $request, Asset $asset): View|string|RedirectResponse
    {
        $this->authorize('update', $asset);

        if ($request->ajax()) {
            return view('assets._modal_form', [
                'asset' => $asset,
                'companies' => $this->companiesForForm($request->user()),
                'assetTypes' => $this->assetTypesForForm($request, $asset->company_id),
                'action' => route('assets.update', ['asset' => $asset] + $request->query()),
                'method' => 'PATCH',
            ])->render();
        }

        return redirect()
            ->route('assets.index')
            ->with('status', 'La edición de activos se realiza desde la vista principal.');
    }

    // Actualiza el activo y recompone los fragmentos visibles del listado principal.
    public function update(AssetUpdateRequest $request, Asset $asset): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $asset);

        $authUser = $request->user();
        $data = $request->validated();

        $asset->update([
            'company_id' => $authUser->isSuperAdmin() ? ($data['company_id'] ?? $asset->company_id) : $asset->company_id,
            'name' => $data['name'],
            'asset_type_id' => $data['asset_type_id'],
            'asset_type' => AssetType::query()->find($data['asset_type_id'])?->name ?? $asset->asset_type,
            'asset_condition' => $data['asset_condition'],
            'quantity' => (int) $data['quantity'],
            'purchase_value' => (float) ($data['purchase_value'] ?? 0),
            'purchase_date' => $data['purchase_date'] ?? null,
        ]);

        $this->loadAssetListRelations($asset);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $asset->id,
                'row_html' => view('assets._row', compact('asset'))->render(),
                'summary_html' => view('assets._summary', [
                    'summary' => $this->resolveSummary($this->buildIndexBaseQuery(
                        $request,
                        $authUser->isSuperAdmin() ? $request->integer('company_id') ?: null : $authUser->company_id,
                        trim((string) $request->string('search'))
                    )),
                ])->render(),
                'message' => 'Activo actualizado correctamente.',
            ]);
        }

        return redirect()
            ->route('assets.index')
            ->with('status', 'Activo actualizado correctamente.');
    }

    // Archiva el activo mediante borrado lógico y recalcula el resumen filtrado actual.
    public function destroy(Request $request, Asset $asset): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $asset);

        $asset->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $asset->id,
                'summary_html' => view('assets._summary', [
                    'summary' => $this->resolveSummary($this->buildIndexBaseQuery(
                        $request,
                        $request->user()->isSuperAdmin() ? $request->integer('company_id') ?: null : $request->user()->company_id,
                        trim((string) $request->string('search'))
                    )),
                ])->render(),
                'message' => 'Activo archivado correctamente.',
            ]);
        }

        return redirect()
            ->route('assets.index')
            ->with('status', 'Activo archivado correctamente.');
    }

    // Devuelve las empresas disponibles en el formulario según el rol del usuario autenticado.
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

    // Carga las relaciones y métricas agregadas que necesita cada fila del listado de activos.
    protected function loadAssetListRelations(Asset $asset): void
    {
        $asset->load(['company', 'type']);
        $asset->loadCount([
            'novelties as active_novelties_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            'media as active_media_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
        ]);
        $asset->loadSum([
            'novelties as active_novelties_cost_sum' => fn ($query) => $query
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->whereHas('type', fn ($typeQuery) => $typeQuery->where('adds_value', true)),
        ], 'cost');
    }

    // Construye la consulta base reutilizable para listado, resúmenes y respuestas AJAX.
    protected function buildIndexBaseQuery(Request $request, ?int $companyId, string $search): Builder
    {
        return Asset::query()
            ->when($companyId, fn (Builder $query) => $query->where('company_id', $companyId))
            ->when(! $request->user()->isSuperAdmin(), fn (Builder $query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('asset_type', 'like', "%{$search}%")
                        ->orWhereHas('type', fn (Builder $typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"))
                        ->orWhere('asset_condition', 'like', "%{$search}%");
                });
            });
    }

    // Calcula los totales económicos visibles en la cabecera del módulo de activos.
    protected function resolveSummary(Builder $baseQuery): array
    {
        $assetIds = (clone $baseQuery)->select('id');

        return [
            'assets_purchase_total' => (clone $baseQuery)->get()->sum(fn (Asset $asset) => (float) $asset->purchase_value * (int) ($asset->quantity ?: 1)),
            'novelties_cost_total' => AssetNovelty::query()
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->whereIn('asset_id', $assetIds)
                ->whereHas('type', fn ($typeQuery) => $typeQuery->where('adds_value', true))
                ->sum('cost'),
        ];
    }

    // Obtiene los tipos de activo disponibles para el formulario junto con metadatos de gestión.
    protected function assetTypesForForm(Request $request, ?int $companyId)
    {
        if (! $companyId) {
            return collect();
        }

        return AssetType::query()
            ->where('company_id', $companyId)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->withCount([
                'assets as active_assets_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (AssetType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'status' => $type->status,
                'can_delete' => ((int) $type->active_assets_count) === 0,
                'update_url' => route('asset-types.update', $type),
                'delete_url' => route('asset-types.destroy', $type),
            ])
            ->values();
    }
}
