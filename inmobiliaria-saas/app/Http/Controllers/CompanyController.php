<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\CompanyStoreRequest;
use App\Http\Requests\CompanyUpdateRequest;
use App\Models\Company;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Company::class);

        $authUser = $request->user();
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();

        $companies = Company::query()
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->whereKey($authUser->company_id))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('legal_name', 'like', "%{$search}%")
                        ->orWhere('nit', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->withCount(['users', 'projects'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('companies.index', [
            'companies' => $companies,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(Request $request): View|string
    {
        $this->authorize('create', Company::class);

        if ($request->ajax()) {
            return view('companies._modal_form', [
                'company' => new Company(),
                'action' => route('companies.store'),
                'method' => 'POST',
            ])->render();
        }

        return view('companies.create', [
            'company' => new Company(),
        ]);
    }

    public function store(CompanyStoreRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Company::class);

        $company = Company::query()->create($request->validated());

        $company->loadCount(['users', 'projects']);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $company->id,
                'row_html' => view('companies._row', compact('company'))->render(),
                'message' => 'Empresa creada correctamente.',
            ]);
        }

        return redirect()
            ->route('companies.index')
            ->with('status', 'Empresa creada correctamente.');
    }

    public function show(Company $company): View
    {
        $this->authorize('view', $company);

        $company->loadCount(['users', 'projects', 'providers']);
        $company->load([
            'modules.module',
            'users' => fn ($query) => $query->latest()->limit(5),
            'projects' => fn ($query) => $query->latest()->limit(5),
        ]);

        $availableModules = Module::query()
            ->where('status', '!=', 'deleted')
            ->orderBy('name')
            ->get();

        return view('companies.show', [
            'company' => $company,
            'availableModules' => $availableModules,
        ]);
    }

    public function edit(Request $request, Company $company): View|string
    {
        $this->authorize('update', $company);

        if ($request->ajax()) {
            return view('companies._modal_form', [
                'company' => $company,
                'action' => route('companies.update', $company),
                'method' => 'PATCH',
            ])->render();
        }

        return view('companies.edit', [
            'company' => $company,
        ]);
    }

    public function update(CompanyUpdateRequest $request, Company $company): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $company);

        $company->update($request->validated());
        $company->loadCount(['users', 'projects']);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $company->id,
                'row_html' => view('companies._row', compact('company'))->render(),
                'message' => 'Empresa actualizada correctamente.',
            ]);
        }

        return redirect()
            ->route('companies.index')
            ->with('status', 'Empresa actualizada correctamente.');
    }

    public function updateStatus(Request $request, Company $company): JsonResponse
    {
        $this->authorize('update', $company);
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $company->update([
            'status' => $data['status'],
        ]);

        $company->loadCount(['users', 'projects']);

        return response()->json([
            'id' => $company->id,
            'row_html' => view('companies._row', compact('company'))->render(),
            'message' => 'Estado de la empresa actualizado correctamente.',
        ]);
    }

    public function destroy(Request $request, Company $company): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $company);

        if ($this->companyHasDependencies($company)) {
            $message = 'La empresa no puede eliminarse porque tiene dependencias registradas.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->route('companies.index')->with('status', $message);
        }

        $company->update([
            'status' => 'deleted',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $company->id,
                'message' => 'Empresa archivada correctamente.',
            ]);
        }

        return redirect()->route('companies.index')->with('status', 'Empresa archivada correctamente.');
    }

    protected function companyHasDependencies(Company $company): bool
    {
        return $company->users()->where('status', '!=', EntityStatus::Deleted->value)->exists()
            || $company->projects()->where('status', '!=', EntityStatus::Deleted->value)->exists()
            || $company->providers()->where('status', '!=', EntityStatus::Deleted->value)->exists()
            || $company->modules()->where('status', '!=', EntityStatus::Deleted->value)->exists();
    }
}
