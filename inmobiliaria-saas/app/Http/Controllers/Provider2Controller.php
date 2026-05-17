<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\Provider2StoreRequest;
use App\Http\Requests\Provider2UpdateRequest;
use App\Models\Company;
use App\Models\Provider2;
use App\Models\Provider2Type;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;

class Provider2Controller extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Provider2::class);

        $authUser = $request->user();
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();

        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;

        $providers2 = Provider2::query()
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

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('providers2._table_body', compact('providers2'))->render(),
                'pagination_html' => ViewFacade::make('pagination::tailwind', ['paginator' => $providers2])->render(),
            ]);
        }

        return view('providers2.index', [
            'providers2' => $providers2,
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
        $this->authorize('create', Provider2::class);

        $authUser = $request->user();

        if ($request->ajax()) {
            $companyId = $authUser->isSuperAdmin()
                ? $request->integer('company_id') ?: null
                : $authUser->company_id;

            return view('providers2._modal_form', [
                'provider2' => new Provider2([
                    'company_id' => $companyId,
                    'status' => EntityStatus::Active->value,
                ]),
                'companies' => $this->companiesForForm($authUser),
                'provider2Types' => $this->provider2TypesForForm($companyId),
                'action' => route('providers2.store'),
                'method' => 'POST',
            ])->render();
        }

        return redirect()
            ->route('providers2.index')
            ->with('status', 'La creación de proveedores 2 se realiza desde la vista principal.');
    }

    public function store(Provider2StoreRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Provider2::class);

        $authUser = $request->user();
        $data = $request->validated();

        $companyId = $authUser->isSuperAdmin()
            ? $data['company_id']
            : $authUser->company_id;

        $provider2 = Provider2::query()->create([
            'company_id' => $companyId,
            'provider2_type_id' => $data['provider2_type_id'] ?? null,
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => EntityStatus::Active->value,
        ]);

        $provider2->load(['company', 'type']);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $provider2->id,
                'row_html' => view('providers2._row', compact('provider2'))->render(),
                'message' => 'Proveedor creado correctamente.',
            ]);
        }

        return redirect()
            ->route('providers2.index')
            ->with('status', 'Proveedor creado correctamente.');
    }

    public function edit(Request $request, Provider2 $provider2): View|string|RedirectResponse
    {
        $this->authorize('update', $provider2);

        $authUser = $request->user();

        if ($request->ajax()) {
            return view('providers2._modal_form', [
                'provider2' => $provider2,
                'companies' => $this->companiesForForm($authUser),
                'provider2Types' => $this->provider2TypesForForm($provider2->company_id, $provider2),
                'action' => route('providers2.update', $provider2),
                'method' => 'PATCH',
            ])->render();
        }

        return redirect()
            ->route('providers2.index')
            ->with('status', 'La edición de proveedores 2 se realiza desde la vista principal.');
    }

    public function update(Provider2UpdateRequest $request, Provider2 $provider2): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $provider2);

        $authUser = $request->user();
        $data = $request->validated();

        $companyId = $authUser->isSuperAdmin()
            ? ($data['company_id'] ?? $provider2->company_id)
            : $provider2->company_id;

        $provider2->update([
            'company_id' => $companyId,
            'provider2_type_id' => $data['provider2_type_id'] ?? null,
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
        ]);

        $provider2->load(['company', 'type']);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $provider2->id,
                'row_html' => view('providers2._row', compact('provider2'))->render(),
                'message' => 'Proveedor actualizado correctamente.',
            ]);
        }

        return redirect()
            ->route('providers2.index')
            ->with('status', 'Proveedor actualizado correctamente.');
    }

    public function updateStatus(Request $request, Provider2 $provider2): JsonResponse
    {
        $this->authorize('update', $provider2);

        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $provider2->update([
            'status' => $data['status'],
        ]);

        $provider2->load(['company', 'type']);

        return response()->json([
            'id' => $provider2->id,
            'row_html' => view('providers2._row', compact('provider2'))->render(),
            'message' => 'Estado del Proveedor actualizado correctamente.',
        ]);
    }

    public function destroy(Request $request, Provider2 $provider2): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $provider2);

        $provider2->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $provider2->id,
                'message' => 'Proveedor archivado correctamente.',
            ]);
        }

        return redirect()
            ->route('providers2.index')
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

    protected function provider2TypesForForm(?int $companyId, ?Provider2 $provider2 = null)
    {
        if (! $companyId) {
            return collect();
        }

        return Provider2Type::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($provider2) {
                $query->where('status', EntityStatus::Active->value);

                if ($provider2?->provider2_type_id) {
                    $query->orWhere('id', $provider2->provider2_type_id);
                }
            })
            ->withCount([
                'providers as active_providers_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            ])
            ->orderBy('name')
            ->get();
    }
}
