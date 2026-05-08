<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\ProviderStoreRequest;
use App\Http\Requests\ProviderUpdateRequest;
use App\Models\Company;
use App\Models\Provider;
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
            ->with('company')
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
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
            'name' => $data['name'],
            'document_number' => $data['document_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => $data['status'],
        ]);

        $provider->load('company');

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
            'name' => $data['name'],
            'document_number' => $data['document_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => $data['status'],
        ]);

        $provider->load('company');

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

        $provider->load('company');

        return response()->json([
            'id' => $provider->id,
            'row_html' => view('providers._row', compact('provider'))->render(),
            'message' => 'Estado del proveedor actualizado correctamente.',
        ]);
    }

    public function destroy(Request $request, Provider $provider): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $provider);

        if ($provider->expenses()->exists()) {
            $message = 'El proveedor no puede archivarse porque tiene gastos registrados.';

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
}
