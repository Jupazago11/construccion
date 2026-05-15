<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\Asset2TypeStoreRequest;
use App\Models\Asset2;
use App\Models\Asset2Type;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Asset2TypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Asset2::class);

        $companyId = $this->resolveCompanyId($request);

        return response()->json($this->typesPayload($companyId));
    }

    public function store(Asset2TypeStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Asset2::class);

        $companyId = $this->resolveCompanyId($request);
        $data = $request->validated();

        $asset2Type = Asset2Type::query()->create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'status' => $data['status'],
        ]);

        return response()->json([
            ...$this->typesPayload($companyId),
            'selected_type_id' => $asset2Type->id,
            'message' => 'Tipo de activo 2 creado correctamente.',
        ]);
    }

    public function update(Asset2TypeStoreRequest $request, Asset2Type $asset2Type): JsonResponse
    {
        $this->authorize('update', $asset2Type);

        $companyId = $this->resolveCompanyId($request);
        abort_unless($asset2Type->company_id === $companyId, 404);

        $data = $request->validated();

        $asset2Type->update([
            'name' => $data['name'],
            'status' => $data['status'],
        ]);

        return response()->json([
            ...$this->typesPayload($companyId),
            'message' => 'Tipo de activo 2 actualizado correctamente.',
        ]);
    }

    public function destroy(Request $request, Asset2Type $asset2Type): JsonResponse
    {
        $this->authorize('delete', $asset2Type);

        $companyId = $this->resolveCompanyId($request);
        abort_unless($asset2Type->company_id === $companyId, 404);

        $hasAssets = $asset2Type->assets()
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->exists();

        abort_if($hasAssets, 422, 'No puedes eliminar un tipo que ya tiene activos 2 asociados.');

        $asset2Type->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        return response()->json([
            ...$this->typesPayload($companyId),
            'message' => 'Tipo de activo 2 eliminado correctamente.',
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
        $types = Asset2Type::query()
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

        return [
            'types' => $types,
            'options_html' => view('assets2._type_options', [
                'asset2Types' => $types,
                'selectedAsset2TypeId' => null,
            ])->render(),
        ];
    }
}
