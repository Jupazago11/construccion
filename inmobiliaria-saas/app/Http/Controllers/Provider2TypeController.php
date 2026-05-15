<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Models\Provider2;
use App\Models\Provider2Type;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class Provider2TypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Provider2::class);

        $companyId = $this->resolveCompanyId($request);

        return response()->json($this->typesPayload($companyId));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Provider2::class);

        $data = $this->validatedData($request);
        $companyId = $request->user()->isSuperAdmin() ? $data['company_id'] : $request->user()->company_id;

        $type = Provider2Type::query()->create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'status' => EntityStatus::Active->value,
        ]);

        return response()->json([
            ...$this->typesPayload($companyId),
            'selected_type_id' => $type->id,
            'message' => 'Tipo de Proveedor creado correctamente.',
        ]);
    }

    public function update(Request $request, Provider2Type $provider2Type): JsonResponse
    {
        $this->authorize('update', $provider2Type);

        $data = $this->validatedData($request, $provider2Type);
        $provider2Type->update([
            'name' => $data['name'],
            'status' => $data['status'] ?? $provider2Type->status,
        ]);

        return response()->json([
            ...$this->typesPayload($provider2Type->company_id),
            'message' => 'Tipo de Proveedor actualizado correctamente.',
        ]);
    }

    public function destroy(Request $request, Provider2Type $provider2Type): JsonResponse
    {
        $this->authorize('delete', $provider2Type);

        if ($provider2Type->providers()->where('status', '!=', EntityStatus::Deleted->value)->exists()) {
            return response()->json(['message' => 'El tipo no puede archivarse porque tiene proveedores 2 asociados.'], 422);
        }

        $companyId = $provider2Type->company_id;
        $provider2Type->update(['status' => EntityStatus::Deleted->value]);

        return response()->json([
            ...$this->typesPayload($companyId),
            'message' => 'Tipo de Proveedor archivado correctamente.',
        ]);
    }

    protected function validatedData(Request $request, ?Provider2Type $provider2Type = null): array
    {
        $companyId = $request->user()->isSuperAdmin()
            ? $request->integer('company_id')
            : $request->user()->company_id;

        return $request->validate([
            'company_id' => ['nullable', 'integer'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Provider2Type::class, 'name')
                    ->where(fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->where('status', '!=', EntityStatus::Deleted->value))
                    ->ignore($provider2Type),
            ],
            'status' => ['nullable', Rule::in([EntityStatus::Active->value, EntityStatus::Inactive->value])],
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
        $types = Provider2Type::query()
            ->where('company_id', $companyId)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->withCount([
                'providers as active_providers_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Provider2Type $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'status' => $type->status,
                'can_delete' => ((int) $type->active_providers_count) === 0,
                'update_url' => route('provider2-types.update', $type),
                'delete_url' => route('provider2-types.destroy', $type),
            ])
            ->values();

        return ['types' => $types];
    }
}
