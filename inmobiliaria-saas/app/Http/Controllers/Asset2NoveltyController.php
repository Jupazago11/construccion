<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\AssetNoveltyStoreRequest;
use App\Models\Asset2;
use App\Models\Asset2Novelty;
use App\Models\AssetNoveltyType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Asset2NoveltyController extends Controller
{
    public function create(Request $request, Asset2 $asset2): string|RedirectResponse
    {
        $this->authorize('update', $asset2);
        $this->authorize('create', Asset2Novelty::class);

        if ($request->ajax()) {
            $this->loadNoveltyContext($asset2);

            return view('assets2._novelty_modal_form', [
                'asset2' => $asset2,
                'novelty' => new Asset2Novelty(['novelty_date' => today()]),
                'noveltyTypes' => $this->noveltyTypesForForm($asset2),
                'activeNoveltyTypes' => $this->activeNoveltyTypesForForm($asset2),
                'action' => route('assets2.novelties.store', ['asset2' => $asset2] + $request->query()),
                'method' => 'POST',
            ])->render();
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'El registro de novedades se realiza desde la vista principal.');
    }

    public function store(AssetNoveltyStoreRequest $request, Asset2 $asset2): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $asset2);
        $this->authorize('create', Asset2Novelty::class);

        $data = $request->validated();

        Asset2Novelty::query()->create([
            'asset2_id' => $asset2->id,
            'created_by' => $request->user()->id,
            'asset_novelty_type_id' => $data['asset_novelty_type_id'],
            'name' => $data['name'],
            'cost' => $data['cost'],
            'description' => $data['description'] ?? null,
            'asset_status' => $data['asset_status'] ?? $asset2->asset_condition,
            'novelty_date' => $data['novelty_date'],
            'status' => EntityStatus::Active->value,
        ]);

        if ($request->expectsJson()) {
            return $this->asset2ListResponse($request, $asset2, 'Novedad registrada correctamente.');
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'Novedad registrada correctamente.');
    }

    public function edit(Request $request, Asset2 $asset2, Asset2Novelty $novelty): string|RedirectResponse
    {
        abort_unless($novelty->asset2_id === $asset2->id, 404);

        $this->authorize('update', $asset2);
        $this->authorize('update', $novelty);

        if ($request->ajax()) {
            $this->loadNoveltyContext($asset2);

            return view('assets2._novelty_modal_form', [
                'asset2' => $asset2,
                'novelty' => $novelty,
                'noveltyTypes' => $this->noveltyTypesForForm($asset2),
                'activeNoveltyTypes' => $this->activeNoveltyTypesForForm($asset2),
                'action' => route('assets2.novelties.update', ['asset2' => $asset2, 'novelty' => $novelty] + $request->query()),
                'method' => 'PATCH',
            ])->render();
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'La edición de novedades se realiza desde la vista principal.');
    }

    public function update(AssetNoveltyStoreRequest $request, Asset2 $asset2, Asset2Novelty $novelty): JsonResponse|RedirectResponse
    {
        abort_unless($novelty->asset2_id === $asset2->id, 404);

        $this->authorize('update', $asset2);
        $this->authorize('update', $novelty);

        $data = $request->validated();

        $novelty->update([
            'asset_novelty_type_id' => $data['asset_novelty_type_id'],
            'name' => $data['name'],
            'cost' => $data['cost'],
            'description' => $data['description'] ?? null,
            'asset_status' => $data['asset_status'] ?? $asset2->asset_condition ?? $novelty->asset_status,
            'novelty_date' => $data['novelty_date'],
        ]);

        if ($request->expectsJson()) {
            return $this->asset2ListResponse($request, $asset2, 'Novedad actualizada correctamente.');
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'Novedad actualizada correctamente.');
    }

    public function destroy(Request $request, Asset2 $asset2, Asset2Novelty $novelty): JsonResponse|RedirectResponse
    {
        abort_unless($novelty->asset2_id === $asset2->id, 404);

        $this->authorize('update', $asset2);

        if ($novelty->status === EntityStatus::Deleted->value) {
            if ($request->expectsJson()) {
                return $this->asset2ListResponse($request, $asset2, 'La novedad ya estaba eliminada.', true);
            }

            return redirect()
                ->route('assets2.index')
                ->with('status', 'La novedad ya estaba eliminada.');
        }

        $this->authorize('delete', $novelty);

        $novelty->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        if ($request->expectsJson()) {
            return $this->asset2ListResponse($request, $asset2, 'Novedad eliminada correctamente.', true);
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'Novedad eliminada correctamente.');
    }

    protected function asset2ListResponse(Request $request, Asset2 $asset2, string $message, bool $includeNoveltyModal = false): JsonResponse
    {
        $this->loadAsset2ListRelations($asset2);
        $summary = $this->resolveSummary(
            $this->buildAsset2BaseQuery(
                $request,
                $request->user()->isSuperAdmin() ? $request->integer('company_id') ?: null : $request->user()->company_id,
                trim((string) $request->string('search'))
            )
        );

        $payload = [
            'id' => $asset2->id,
            'row_html' => view('assets2._row', compact('asset2'))->render(),
            'summary_html' => view('assets2._summary', compact('summary'))->render(),
            'message' => $message,
        ];

        if ($includeNoveltyModal) {
            $this->loadNoveltyContext($asset2);

            $payload['modal_html'] = view('assets2._novelty_modal_form', [
                'asset2' => $asset2,
                'novelty' => new Asset2Novelty(['novelty_date' => today()]),
                'noveltyTypes' => $this->noveltyTypesForForm($asset2),
                'activeNoveltyTypes' => $this->activeNoveltyTypesForForm($asset2),
                'action' => route('assets2.novelties.store', ['asset2' => $asset2] + $request->query()),
                'method' => 'POST',
            ])->render();
        }

        return response()->json($payload);
    }

    protected function loadNoveltyContext(Asset2 $asset2): void
    {
        $asset2->load([
            'novelties' => fn ($query) => $query
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->with(['creator', 'type'])
                ->latest('novelty_date')
                ->latest('id')
                ->limit(8),
        ]);
    }

    protected function loadAsset2ListRelations(Asset2 $asset2): void
    {
        $asset2->refresh();
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

    protected function buildAsset2BaseQuery(Request $request, ?int $companyId, string $search): Builder
    {
        return Asset2::query()
            ->when($companyId, fn (Builder $query) => $query->where('company_id', $companyId))
            ->when(! $request->user()->isSuperAdmin(), fn (Builder $query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('asset2_type', 'like', "%{$search}%")
                        ->orWhere('asset_condition', 'like', "%{$search}%");
                });
            });
    }

    protected function resolveSummary(Builder $baseQuery): array
    {
        $asset2Ids = (clone $baseQuery)->select('id');

        return [
            'assets2_purchase_total' => (clone $baseQuery)->sum('purchase_value'),
            'assets2_count' => (clone $baseQuery)->count(),
            'novelties_cost_total' => Asset2Novelty::query()
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->whereIn('asset2_id', $asset2Ids)
                ->whereHas('type', fn ($typeQuery) => $typeQuery->where('adds_value', true))
                ->sum('cost'),
        ];
    }

    protected function noveltyTypesForForm(Asset2 $asset2)
    {
        return AssetNoveltyType::query()
            ->where('company_id', $asset2->company_id)
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

    protected function activeNoveltyTypesForForm(Asset2 $asset2)
    {
        return $this->noveltyTypesForForm($asset2)
            ->where('status', EntityStatus::Active->value)
            ->values();
    }
}
