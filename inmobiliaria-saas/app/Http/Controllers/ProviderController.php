<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\ProviderStoreRequest;
use App\Http\Requests\ProviderUpdateRequest;
use App\Models\Company;
use App\Models\Provider;
use App\Models\ProviderType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Provider::class);

        $authUser = $request->user();
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;

        $providers = Provider::query()
            ->with(['company', 'type'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('type', fn ($typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('providers.index', [
            'providers' => $providers,
            'companies' => $authUser->isSuperAdmin()
                ? Company::query()->where('status', '!=', EntityStatus::Deleted->value)->orderBy('name')->get()
                : collect(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'company_id' => $companyId,
            ],
        ]);
    }

    public function create(Request $request): View|string|RedirectResponse
    {
        $this->authorize('create', Provider::class);

        $authUser = $request->user();

        if ($request->ajax()) {
            return view('providers._modal_form', [
                'provider' => new Provider(),
                'companies' => $this->companiesForForm($authUser),
                'providerTypes' => $this->providerTypesForForm($authUser),
                'action' => route('providers.store'),
                'method' => 'POST',
            ])->render();
        }

        return redirect()
            ->route('providers.index')
            ->with('status', 'La creación de proveedores se realiza desde la vista principal.');
    }

    public function store(ProviderStoreRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Provider::class);

        $authUser = $request->user();
        $data = $request->validated();

        $provider = Provider::query()->create([
            'company_id' => $authUser->isSuperAdmin() ? $data['company_id'] : $authUser->company_id,
            'provider_type_id' => $data['provider_type_id'] ?? null,
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => EntityStatus::Active->value,
        ]);

        $provider->load(['company', 'type']);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $provider->id,
                'row_html' => view('providers._row', compact('provider'))->render(),
                'message' => 'Proveedor creado correctamente.',
            ]);
        }

        return redirect()
            ->route('providers.index')
            ->with('status', 'Proveedor creado correctamente.');
    }

    public function edit(Request $request, Provider $provider): View|string|RedirectResponse
    {
        $this->authorize('update', $provider);

        $authUser = $request->user();

        if ($request->ajax()) {
            return view('providers._modal_form', [
                'provider' => $provider,
                'companies' => $this->companiesForForm($authUser),
                'providerTypes' => $this->providerTypesForForm($authUser, $provider),
                'action' => route('providers.update', $provider),
                'method' => 'PATCH',
            ])->render();
        }

        return redirect()
            ->route('providers.index')
            ->with('status', 'La edición de proveedores se realiza desde la vista principal.');
    }

    public function update(ProviderUpdateRequest $request, Provider $provider): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $provider);

        $authUser = $request->user();
        $data = $request->validated();

        $provider->update([
            'company_id' => $authUser->isSuperAdmin() ? ($data['company_id'] ?? $provider->company_id) : $provider->company_id,
            'provider_type_id' => $data['provider_type_id'] ?? null,
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => $provider->status,
        ]);

        $provider->load(['company', 'type']);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $provider->id,
                'row_html' => view('providers._row', compact('provider'))->render(),
                'message' => 'Proveedor actualizado correctamente.',
            ]);
        }

        return redirect()
            ->route('providers.index')
            ->with('status', 'Proveedor actualizado correctamente.');
    }

    public function updateStatus(Request $request, Provider $provider): JsonResponse
    {
        $this->authorize('update', $provider);

        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $provider->update([
            'status' => $data['status'],
        ]);

        $provider->load(['company', 'type']);

        return response()->json([
            'id' => $provider->id,
            'row_html' => view('providers._row', compact('provider'))->render(),
            'message' => 'Estado del proveedor actualizado correctamente.',
        ]);
    }

    public function destroy(Request $request, Provider $provider): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $provider);

        if ($provider->expenses()->where('status', '!=', EntityStatus::Deleted->value)->exists()
            || $provider->purchases()->where('status', '!=', EntityStatus::Deleted->value)->exists()) {
            $message = 'El proveedor no puede archivarse porque tiene gastos o compras registrados.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->route('providers.index')->with('status', $message);
        }

        $provider->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $provider->id,
                'message' => 'Proveedor archivado correctamente.',
            ]);
        }

        return redirect()
            ->route('providers.index')
            ->with('status', 'Proveedor archivado correctamente.');
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

    protected function providerTypesForForm($authUser, ?Provider $provider = null)
    {
        return ProviderType::query()
            ->with('company')
            ->withCount([
                'providers as active_providers_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            ])
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('company_id', $authUser->company_id))
            ->where(function ($query) use ($provider) {
                $query->where('status', EntityStatus::Active->value);

                if ($provider?->provider_type_id) {
                    $query->orWhereKey($provider->provider_type_id);
                }
            })
            ->orderBy('name')
            ->get();
    }
}
