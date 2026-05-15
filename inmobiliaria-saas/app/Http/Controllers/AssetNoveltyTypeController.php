<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\AssetNoveltyTypeStoreRequest;
use App\Models\Asset;
use App\Models\AssetNoveltyType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetNoveltyTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Asset::class);

        $companyId = $this->resolveCompanyId($request);

        return response()->json($this->typesPayload($companyId));
    }

    public function store(AssetNoveltyTypeStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Asset::class);

        $companyId = $this->resolveCompanyId($request);
        $data = $request->validated();

        $assetNoveltyType = AssetNoveltyType::query()->create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'adds_value' => $data['adds_value'],
            'status' => $data['status'],
        ]);

        return response()->json([
            ...$this->typesPayload($companyId),
            'selected_type_id' => $assetNoveltyType->id,
            'message' => 'Tipo de novedad creado correctamente.',
        ]);
    }

    public function update(AssetNoveltyTypeStoreRequest $request, AssetNoveltyType $assetNoveltyType): JsonResponse
    {
        $this->authorize('update', $assetNoveltyType);

        $companyId = $this->resolveCompanyId($request);
        abort_unless($assetNoveltyType->company_id === $companyId, 404);

        $data = $request->validated();

        $assetNoveltyType->update([
            'name' => $data['name'],
            'adds_value' => $data['adds_value'],
            'status' => $data['status'],
        ]);

        return response()->json([
            ...$this->typesPayload($companyId),
            'message' => 'Tipo de novedad actualizado correctamente.',
        ]);
    }

    public function destroy(Request $request, AssetNoveltyType $assetNoveltyType): JsonResponse
    {
        $this->authorize('delete', $assetNoveltyType);

        $companyId = $this->resolveCompanyId($request);
        abort_unless($assetNoveltyType->company_id === $companyId, 404);

        $hasNovelties = $assetNoveltyType->novelties()
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->exists();

        abort_if($hasNovelties, 422, 'No puedes eliminar un tipo que ya tiene novedades asociadas.');

        $assetNoveltyType->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        return response()->json([
            ...$this->typesPayload($companyId),
            'message' => 'Tipo de novedad eliminado correctamente.',
        ]);
    }

    protected function resolveCompanyId(Request $request): int
    {
        if (! $request->user()->isSuperAdmin()) {
            return (int) $request->user()->company_id;
        }

        $companyId = $request->integer('company_id');
        abort_unless($companyId, 422, 'Selecciona una empresa antes de administrar los tipos.');

        return $companyId;
    }

    protected function typesPayload(int $companyId): array
    {
        $types = AssetNoveltyType::query()
            ->where('company_id', $companyId)
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

        return ['types' => $types];
    }
}
