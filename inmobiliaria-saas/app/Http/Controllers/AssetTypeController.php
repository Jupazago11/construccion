<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\AssetTypeStoreRequest;
use App\Models\Asset;
use App\Models\AssetType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetTypeController extends Controller
{
    // Lista los tipos de activos activos de la empresa para tablas y selects en AJAX.
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Asset::class);

        $companyId = $this->resolveCompanyId($request);

        return response()->json($this->typesPayload($companyId));
    }

    // Crea un tipo de activo y devuelve el catálogo actualizado para refrescar la UI.
    public function store(AssetTypeStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Asset::class);

        $companyId = $this->resolveCompanyId($request);
        $data = $request->validated();

        $assetType = AssetType::query()->create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'status' => $data['status'],
        ]);

        return response()->json([
            ...$this->typesPayload($companyId),
            'selected_type_id' => $assetType->id,
            'message' => 'Tipo de activo creado correctamente.',
        ]);
    }

    // Actualiza un tipo existente validando pertenencia tenant antes de guardar.
    public function update(AssetTypeStoreRequest $request, AssetType $assetType): JsonResponse
    {
        $this->authorize('update', $assetType);

        $companyId = $this->resolveCompanyId($request);
        abort_unless($assetType->company_id === $companyId, 404);

        $data = $request->validated();

        $assetType->update([
            'name' => $data['name'],
            'status' => $data['status'],
        ]);

        return response()->json([
            ...$this->typesPayload($companyId),
            'message' => 'Tipo de activo actualizado correctamente.',
        ]);
    }

    // Archiva el tipo solo si no tiene activos activos relacionados.
    public function destroy(Request $request, AssetType $assetType): JsonResponse
    {
        $this->authorize('delete', $assetType);

        $companyId = $this->resolveCompanyId($request);
        abort_unless($assetType->company_id === $companyId, 404);

        $hasAssets = $assetType->assets()
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->exists();

        abort_if($hasAssets, 422, 'No puedes eliminar un tipo que ya tiene activos asociados.');

        $assetType->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        return response()->json([
            ...$this->typesPayload($companyId),
            'message' => 'Tipo de activo eliminado correctamente.',
        ]);
    }

    // Resuelve la empresa objetivo; el superadmin debe seleccionarla y el resto usa su tenant.
    protected function resolveCompanyId(Request $request): int
    {
        if (! $request->user()->isSuperAdmin()) {
            return (int) $request->user()->company_id;
        }

        $companyId = $request->integer('company_id');
        abort_unless($companyId, 422, 'Selecciona una empresa antes de administrar los tipos.');

        return $companyId;
    }

    // Genera la respuesta común para la tabla de tipos y las opciones del modal de activos.
    protected function typesPayload(int $companyId): array
    {
        $types = AssetType::query()
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

        return [
            'types' => $types,
            'options_html' => view('assets._type_options', [
                'assetTypes' => $types,
                'selectedAssetTypeId' => null,
            ])->render(),
        ];
    }
}
