<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\AssetStoreRequest;
use App\Http\Requests\AssetUpdateRequest;
use App\Models\Asset;
use App\Models\AssetNovelty;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Asset::class);

        $authUser = $request->user();
        $search = trim((string) $request->string('search'));
        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;

        $baseQuery = $this->buildIndexBaseQuery($request, $companyId, $search);

        $assets = (clone $baseQuery)
            ->with('company')
            ->withCount([
                'novelties as active_novelties_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            ])
            ->withSum([
                'novelties as active_novelties_cost_sum' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            ], 'cost')
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('asset_type', 'like', "%{$search}%")
                        ->orWhere('asset_condition', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

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

    public function create(Request $request): View|string|RedirectResponse
    {
        $this->authorize('create', Asset::class);

        if ($request->ajax()) {
            return view('assets._modal_form', [
                'asset' => new Asset(['purchase_date' => today()]),
                'companies' => $this->companiesForForm($request->user()),
                'action' => route('assets.store', $request->query()),
                'method' => 'POST',
            ])->render();
        }

        return redirect()
            ->route('assets.index')
            ->with('status', 'La creación de activos se realiza desde la vista principal.');
    }

    public function store(AssetStoreRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Asset::class);

        $authUser = $request->user();
        $data = $request->validated();

        $asset = Asset::query()->create([
            'company_id' => $authUser->isSuperAdmin() ? $data['company_id'] : $authUser->company_id,
            'name' => $data['name'],
            'asset_type' => $data['asset_type'],
            'asset_condition' => $data['asset_condition'],
            'purchase_value' => $data['purchase_value'],
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

    public function edit(Request $request, Asset $asset): View|string|RedirectResponse
    {
        $this->authorize('update', $asset);

        if ($request->ajax()) {
            return view('assets._modal_form', [
                'asset' => $asset,
                'companies' => $this->companiesForForm($request->user()),
                'action' => route('assets.update', ['asset' => $asset] + $request->query()),
                'method' => 'PATCH',
            ])->render();
        }

        return redirect()
            ->route('assets.index')
            ->with('status', 'La edición de activos se realiza desde la vista principal.');
    }

    public function update(AssetUpdateRequest $request, Asset $asset): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $asset);

        $authUser = $request->user();
        $data = $request->validated();

        $asset->update([
            'company_id' => $authUser->isSuperAdmin() ? ($data['company_id'] ?? $asset->company_id) : $asset->company_id,
            'name' => $data['name'],
            'asset_type' => $data['asset_type'],
            'asset_condition' => $data['asset_condition'],
            'purchase_value' => $data['purchase_value'],
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

    protected function loadAssetListRelations(Asset $asset): void
    {
        $asset->load('company');
        $asset->loadCount([
            'novelties as active_novelties_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
        ]);
        $asset->loadSum([
            'novelties as active_novelties_cost_sum' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
        ], 'cost');
    }

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
                        ->orWhere('asset_condition', 'like', "%{$search}%");
                });
            });
    }

    protected function resolveSummary(Builder $baseQuery): array
    {
        $assetIds = (clone $baseQuery)->select('id');

        return [
            'assets_purchase_total' => (clone $baseQuery)->sum('purchase_value'),
            'novelties_cost_total' => AssetNovelty::query()
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->whereIn('asset_id', $assetIds)
                ->sum('cost'),
        ];
    }
}
