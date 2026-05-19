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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View as ViewFacade;

class CompanyController extends Controller
{
    // Lista empresas con filtros y puede responder la tabla/paginación parcial por AJAX.
    public function index(Request $request): View|JsonResponse
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

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('companies._table_body', compact('companies'))->render(),
                'pagination_html' => ViewFacade::make('pagination::tailwind', ['paginator' => $companies])->render(),
            ]);
        }

        return view('companies.index', [
            'companies' => $companies,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    // Renderiza el formulario de creación de empresa en modal AJAX o página completa.
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

    // Crea una empresa y devuelve la fila parcial cuando el flujo es AJAX.
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

    // Muestra el detalle de la empresa, sus módulos y un resumen de relaciones principales.
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

    // Renderiza el formulario de edición de empresa en modal AJAX o página completa.
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

    // Actualiza los datos básicos de la empresa sin tocar sus relaciones.
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

    // Cambia el estado de la empresa y protege que no se deje activa si hay reglas que lo impidan.
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

    // Archiva la empresa solo si no conserva dependencias activas que comprometan integridad operativa.
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

    // Entrega el logo de la empresa desde storage o redirige al fallback si no existe.
    public function logo(Request $request, Company $company): \Illuminate\Http\RedirectResponse
    {
        abort_if(! $company->logo_path, 404);

        try {
            $path = $company->logo_path;

            abort_if(! Storage::disk('r2')->exists($path), 404);

            $url = Storage::disk('r2')->temporaryUrl($path, now()->addDay());

            return redirect($url)->header('Cache-Control', 'public, max-age=3600');
        } catch (\Throwable) {
            abort(404);
        }
    }

    // Guarda o reemplaza el logo de la empresa para refrescar branding en navegación y perfil tenant.
    public function storeLogo(Request $request, Company $company): JsonResponse
    {
        $this->authorize('update', $company);
        abort_unless($request->user()->isSuperAdmin(), 403);

        $request->validate([
            'logo' => ['required', 'image', 'max:4096', 'mimes:png,jpg,jpeg,webp,svg'],
        ]);

        if ($company->logo_path) {
            Storage::disk('r2')->delete($company->logo_path);
        }

        $prefix = trim((string) config('filesystems.r2_root_prefix', env('R2_ROOT_PREFIX', 'inmobiliaria-saas')), '/');
        $directory = collect([$prefix, 'companies', $company->id, 'logo'])->filter()->implode('/');
        $path = $request->file('logo')->store($directory, 'r2');

        $company->update(['logo_path' => $path]);

        return response()->json([
            'message' => 'Logo actualizado correctamente.',
            'logo_url' => route('companies.logo', $company),
        ]);
    }

    // Determina si la empresa tiene relaciones activas que impiden archivarla con seguridad.
    protected function companyHasDependencies(Company $company): bool
    {
        return $company->users()->where('status', '!=', EntityStatus::Deleted->value)->exists()
            || $company->projects()->where('status', '!=', EntityStatus::Deleted->value)->exists()
            || $company->providers()->where('status', '!=', EntityStatus::Deleted->value)->exists()
            || $company->modules()->where('status', '!=', EntityStatus::Deleted->value)->exists();
    }
}
