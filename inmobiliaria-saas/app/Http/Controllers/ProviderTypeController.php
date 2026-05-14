<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Models\Company;
use App\Models\ProviderType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProviderTypeController extends Controller
{
    public function index(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('viewAny', ProviderType::class);

        $companyId = $this->resolveCompanyId($request);

        if ($request->expectsJson()) {
            return response()->json($this->typesPayload($companyId));
        }

        return redirect()->route('providers.index');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', ProviderType::class);

        $data = $this->validatedData($request);
        $companyId = $request->user()->isSuperAdmin() ? $data['company_id'] : $request->user()->company_id;

        $providerType = ProviderType::query()->create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'status' => EntityStatus::Active->value,
        ]);

        $providerType->load('company');

        if ($request->expectsJson()) {
            return response()->json([
                ...$this->typesPayload($companyId),
                'message' => 'Tipo de proveedor creado correctamente.',
            ]);
        }

        return redirect()->route('provider-types.index')->with('status', 'Tipo de proveedor creado correctamente.');
    }

    public function update(Request $request, ProviderType $providerType): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $providerType);

        $data = $this->validatedData($request, $providerType);
        $providerType->update([
            'company_id' => $request->user()->isSuperAdmin() ? ($data['company_id'] ?? $providerType->company_id) : $providerType->company_id,
            'name' => $data['name'],
            'status' => $data['status'] ?? $providerType->status,
        ]);

        $companyId = $providerType->company_id;

        if ($request->expectsJson()) {
            return response()->json([
                ...$this->typesPayload($companyId),
                'message' => 'Tipo de proveedor actualizado correctamente.',
            ]);
        }

        return redirect()->route('provider-types.index')->with('status', 'Tipo de proveedor actualizado correctamente.');
    }

    public function updateStatus(Request $request, ProviderType $providerType): JsonResponse
    {
        $this->authorize('update', $providerType);

        $data = $request->validate([
            'status' => ['required', Rule::in([EntityStatus::Active->value, EntityStatus::Inactive->value])],
        ]);

        $providerType->update(['status' => $data['status']]);
        $companyId = $providerType->company_id;

        return response()->json([
            ...$this->typesPayload($companyId),
            'message' => 'Estado del tipo de proveedor actualizado correctamente.',
        ]);
    }

    public function destroy(Request $request, ProviderType $providerType): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $providerType);

        if ($providerType->providers()->where('status', '!=', EntityStatus::Deleted->value)->exists()) {
            $message = 'El tipo no puede archivarse porque tiene proveedores asociados.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->route('provider-types.index')->with('status', $message);
        }

        $providerType->update(['status' => EntityStatus::Deleted->value]);

        if ($request->expectsJson()) {
            return response()->json([
                ...$this->typesPayload($providerType->company_id),
                'message' => 'Tipo de proveedor archivado correctamente.',
            ]);
        }

        return redirect()->route('provider-types.index')->with('status', 'Tipo de proveedor archivado correctamente.');
    }

    protected function validatedData(Request $request, ?ProviderType $providerType = null): array
    {
        $companyId = $request->user()->isSuperAdmin()
            ? $request->integer('company_id')
            : $request->user()->company_id;

        return $request->validate([
            'company_id' => [
                Rule::requiredIf($request->user()->isSuperAdmin()),
                'nullable',
                'integer',
                Rule::exists(Company::class, 'id')->where(fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value)),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(ProviderType::class, 'name')
                    ->where(fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->where('status', '!=', EntityStatus::Deleted->value))
                    ->ignore($providerType),
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
        $types = ProviderType::query()
            ->where('company_id', $companyId)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->withCount([
                'providers as active_providers_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (ProviderType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'status' => $type->status,
                'can_delete' => ((int) $type->active_providers_count) === 0,
                'update_url' => route('provider-types.update', $type),
                'delete_url' => route('provider-types.destroy', $type),
            ])
            ->values();

        return ['types' => $types];
    }

}
