<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\AssetNoveltyStoreRequest;
use App\Models\Asset;
use App\Models\AssetNovelty;
use App\Models\AssetNoveltyType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssetNoveltyController extends Controller
{
    public function create(Request $request, Asset $asset): string|RedirectResponse
    {
        $this->authorize('update', $asset);
        $this->authorize('create', AssetNovelty::class);

        if ($request->ajax()) {
            $this->loadNoveltyContext($asset);

            return view('assets._novelty_modal_form', [
                'asset' => $asset,
                'novelty' => new AssetNovelty(['novelty_date' => today()]),
                'noveltyTypes' => $this->noveltyTypesForForm($asset),
                'activeNoveltyTypes' => $this->activeNoveltyTypesForForm($asset),
                'action' => route('assets.novelties.store', ['asset' => $asset] + $request->query()),
                'method' => 'POST',
            ])->render();
        }

        return redirect()
            ->route('assets.index')
            ->with('status', 'El registro de novedades se realiza desde la vista principal.');
    }

    public function store(AssetNoveltyStoreRequest $request, Asset $asset): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $asset);
        $this->authorize('create', AssetNovelty::class);

        $data = $request->validated();

        AssetNovelty::query()->create([
            'asset_id' => $asset->id,
            'created_by' => $request->user()->id,
            'asset_novelty_type_id' => $data['asset_novelty_type_id'],
            'name' => $data['name'],
            'cost' => $data['cost'],
            'description' => $data['description'] ?? null,
            'asset_status' => $data['asset_status'] ?? $asset->asset_condition,
            'novelty_date' => $data['novelty_date'],
            'status' => EntityStatus::Active->value,
        ]);

        if ($request->expectsJson()) {
            return $this->assetListResponse($request, $asset, 'Novedad registrada correctamente.');
        }

        return redirect()
            ->route('assets.index')
            ->with('status', 'Novedad registrada correctamente.');
    }

    public function edit(Request $request, Asset $asset, AssetNovelty $novelty): string|RedirectResponse
    {
        abort_unless($novelty->asset_id === $asset->id, 404);

        $this->authorize('update', $asset);
        $this->authorize('update', $novelty);

        if ($request->ajax()) {
            $this->loadNoveltyContext($asset);

            return view('assets._novelty_modal_form', [
                'asset' => $asset,
                'novelty' => $novelty,
                'noveltyTypes' => $this->noveltyTypesForForm($asset),
                'activeNoveltyTypes' => $this->activeNoveltyTypesForForm($asset),
                'action' => route('assets.novelties.update', ['asset' => $asset, 'novelty' => $novelty] + $request->query()),
                'method' => 'PATCH',
            ])->render();
        }

        return redirect()
            ->route('assets.index')
            ->with('status', 'La edición de novedades se realiza desde la vista principal.');
    }

    public function update(AssetNoveltyStoreRequest $request, Asset $asset, AssetNovelty $novelty): JsonResponse|RedirectResponse
    {
        abort_unless($novelty->asset_id === $asset->id, 404);

        $this->authorize('update', $asset);
        $this->authorize('update', $novelty);

        $data = $request->validated();

        $novelty->update([
            'asset_novelty_type_id' => $data['asset_novelty_type_id'],
            'name' => $data['name'],
            'cost' => $data['cost'],
            'description' => $data['description'] ?? null,
            'asset_status' => $data['asset_status'] ?? $asset->asset_condition ?? $novelty->asset_status,
            'novelty_date' => $data['novelty_date'],
        ]);

        if ($request->expectsJson()) {
            return $this->assetListResponse($request, $asset, 'Novedad actualizada correctamente.');
        }

        return redirect()
            ->route('assets.index')
            ->with('status', 'Novedad actualizada correctamente.');
    }

    public function destroy(Request $request, Asset $asset, AssetNovelty $novelty): JsonResponse|RedirectResponse
    {
        abort_unless($novelty->asset_id === $asset->id, 404);

        $this->authorize('update', $asset);

        if ($novelty->status === EntityStatus::Deleted->value) {
            if ($request->expectsJson()) {
                return $this->assetListResponse($request, $asset, 'La novedad ya estaba eliminada.', true);
            }

            return redirect()
                ->route('assets.index')
                ->with('status', 'La novedad ya estaba eliminada.');
        }

        $this->authorize('delete', $novelty);

        $novelty->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        if ($request->expectsJson()) {
            return $this->assetListResponse($request, $asset, 'Novedad eliminada correctamente.', true);
        }

        return redirect()
            ->route('assets.index')
            ->with('status', 'Novedad eliminada correctamente.');
    }

    protected function assetListResponse(Request $request, Asset $asset, string $message, bool $includeNoveltyModal = false): JsonResponse
    {
        $this->loadAssetListRelations($asset);
        $summary = $this->resolveSummary(
            $this->buildAssetBaseQuery(
                $request,
                $request->user()->isSuperAdmin() ? $request->integer('company_id') ?: null : $request->user()->company_id,
                trim((string) $request->string('search'))
            )
        );

        $payload = [
            'id' => $asset->id,
            'row_html' => view('assets._row', compact('asset'))->render(),
            'summary_html' => view('assets._summary', compact('summary'))->render(),
            'message' => $message,
        ];

        if ($includeNoveltyModal) {
            $this->loadNoveltyContext($asset);

            $payload['modal_html'] = view('assets._novelty_modal_form', [
                'asset' => $asset,
                'novelty' => new AssetNovelty(['novelty_date' => today()]),
                'noveltyTypes' => $this->noveltyTypesForForm($asset),
                'activeNoveltyTypes' => $this->activeNoveltyTypesForForm($asset),
                'action' => route('assets.novelties.store', ['asset' => $asset] + $request->query()),
                'method' => 'POST',
            ])->render();
        }

        return response()->json($payload);
    }

    protected function loadNoveltyContext(Asset $asset): void
    {
        $asset->load([
            'novelties' => fn ($query) => $query
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->with(['creator', 'type'])
                ->latest('novelty_date')
                ->latest('id')
                ->limit(8),
        ]);
    }

    protected function loadAssetListRelations(Asset $asset): void
    {
        $asset->refresh();
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

    protected function buildAssetBaseQuery(Request $request, ?int $companyId, string $search): Builder
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
                ->whereHas('type', fn ($typeQuery) => $typeQuery->where('adds_value', true))
                ->sum('cost'),
        ];
    }

    protected function noveltyTypesForForm(Asset $asset)
    {
        return AssetNoveltyType::query()
            ->where('company_id', $asset->company_id)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->withCount([
                'novelties as active_novelties_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (AssetNoveltyType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'adds_value' => (bool) $type->adds_value,
                'status' => $type->status,
                'can_delete' => ((int) $type->active_novelties_count) === 0,
                'update_url' => route('asset-novelty-types.update', $type),
                'delete_url' => route('asset-novelty-types.destroy', $type),
            ])
            ->values();
    }

    protected function activeNoveltyTypesForForm(Asset $asset)
    {
        return $this->noveltyTypesForForm($asset)
            ->where('status', EntityStatus::Active->value)
            ->values();
    }
}
