<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\AssetNoveltyStoreRequest;
use App\Models\Asset;
use App\Models\AssetNovelty;
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
            'cost' => $data['cost'],
            'description' => $data['description'],
            'asset_status' => $data['asset_status'],
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
            'cost' => $data['cost'],
            'description' => $data['description'],
            'asset_status' => $data['asset_status'],
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
        $this->authorize('delete', $novelty);

        $novelty->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        if ($request->expectsJson()) {
            return $this->assetListResponse($request, $asset, 'Novedad eliminada correctamente.');
        }

        return redirect()
            ->route('assets.index')
            ->with('status', 'Novedad eliminada correctamente.');
    }

    protected function assetListResponse(Request $request, Asset $asset, string $message): JsonResponse
    {
        $this->loadAssetListRelations($asset);
        $summary = $this->resolveSummary(
            $this->buildAssetBaseQuery(
                $request,
                $request->user()->isSuperAdmin() ? $request->integer('company_id') ?: null : $request->user()->company_id,
                trim((string) $request->string('search'))
            )
        );

        return response()->json([
            'id' => $asset->id,
            'row_html' => view('assets._row', compact('asset'))->render(),
            'summary_html' => view('assets._summary', compact('summary'))->render(),
            'message' => $message,
        ]);
    }

    protected function loadNoveltyContext(Asset $asset): void
    {
        $asset->load([
            'novelties' => fn ($query) => $query
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->with('creator')
                ->latest('novelty_date')
                ->latest('id')
                ->limit(8),
        ]);
    }

    protected function loadAssetListRelations(Asset $asset): void
    {
        $asset->refresh();
        $asset->load('company');
        $asset->loadCount([
            'novelties as active_novelties_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
        ]);
        $asset->loadSum([
            'novelties as active_novelties_cost_sum' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
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
                ->sum('cost'),
        ];
    }
}
