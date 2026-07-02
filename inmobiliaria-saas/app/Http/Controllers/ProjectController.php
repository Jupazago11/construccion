<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\ProjectStoreRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    protected const PROJECT_CARD_VIEW = 'projects._card';

    protected const PROJECT_ROW_VIEW = 'projects._row';

    protected const PROJECT_CARD_LIST_VIEW = 'projects._cards_body';

    protected const PROJECT_TABLE_LIST_VIEW = 'projects._table_body';

    // Lista proyectos con filtros tenant y soporta vista parcial AJAX en modo tabla o tarjetas.
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $authUser = $request->user();
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;

        $projects = $this->buildIndexQuery($request, $companyId, $search, $status)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view($this->projectListContainerPartial($request), compact('projects'))->render(),
                'pagination_html' => $projects->links('pagination::tailwind')->toHtml(),
            ]);
        }

        return view('projects.index', [
            'projects' => $projects,
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

    // Renderiza el formulario de creación de proyecto en modal AJAX o página completa.
    public function create(Request $request): View|string
    {
        $this->authorize('create', Project::class);

        $authUser = $request->user();

        if ($request->ajax()) {
            return view('projects._modal_form', [
                'project' => new Project(['start_date' => today()]),
                'companies' => $this->companiesForForm($authUser),
                'action' => route('projects.store'),
                'method' => 'POST',
            ])->render();
        }

        return view('projects.create', [
            'project' => new Project(['start_date' => today()]),
            'companies' => $this->companiesForForm($authUser),
        ]);
    }

    // Crea un proyecto y devuelve la representación parcial adecuada según el modo de listado.
    public function store(ProjectStoreRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Project::class);

        $authUser = $request->user();
        $data = $request->validated();

        $project = Project::query()->create([
            'company_id' => $authUser->isSuperAdmin() ? $data['company_id'] : $authUser->company_id,
            'name' => $data['name'],
            'project_type' => $data['project_type'] ?? null,
            'description' => $data['description'] ?? null,
            'country' => $data['country'] ?? 'Colombia',
            'state' => $data['state'] ?? null,
            'city' => $data['city'] ?? null,
            'address' => $data['address'] ?? null,
            'location_reference' => $data['location_reference'] ?? null,
            'start_date' => $data['start_date'] ?? today(),
            'status' => $data['status'],
        ]);

        $this->loadProjectListRelations($project);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $project->id,
                'row_html' => view($this->projectListPartial($request), compact('project'))->render(),
                'message' => 'Proyecto creado correctamente.',
            ]);
        }

        return redirect()
            ->route('projects.index')
            ->with('status', 'Proyecto creado correctamente.');
    }

    // Muestra el detalle del proyecto y sirve de puerta de entrada a su estructura.
    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        $project->load(['company']);
        $project->loadCount(['categories', 'expenses']);

        return view('projects.show', [
            'project' => $project,
        ]);
    }

    // Renderiza el formulario de edición de proyecto en modal AJAX o página completa.
    public function edit(Request $request, Project $project): View|string
    {
        $this->authorize('update', $project);

        $authUser = $request->user();

        if ($request->ajax()) {
            return view('projects._modal_form', [
                'project' => $project,
                'companies' => $this->companiesForForm($authUser),
                'action' => route('projects.update', $project),
                'method' => 'PATCH',
            ])->render();
        }

        return view('projects.edit', [
            'project' => $project,
            'companies' => $this->companiesForForm($authUser),
        ]);
    }

    // Actualiza los datos básicos del proyecto sin tocar su estructura presupuestal.
    public function update(ProjectUpdateRequest $request, Project $project): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $project);

        $authUser = $request->user();
        $data = $request->validated();

        $project->update([
            'company_id' => $authUser->isSuperAdmin() ? ($data['company_id'] ?? $project->company_id) : $project->company_id,
            'name' => $data['name'],
            'project_type' => $data['project_type'] ?? null,
            'description' => $data['description'] ?? null,
            'country' => $data['country'] ?? 'Colombia',
            'state' => $data['state'] ?? null,
            'city' => $data['city'] ?? null,
            'address' => $data['address'] ?? null,
            'location_reference' => $data['location_reference'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'status' => $project->status,
        ]);

        $this->loadProjectListRelations($project);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $project->id,
                'row_html' => view($this->projectListPartial($request), compact('project'))->render(),
                'message' => 'Proyecto actualizado correctamente.',
            ]);
        }

        return redirect()
            ->route('projects.index')
            ->with('status', 'Proyecto actualizado correctamente.');
    }

    // Renderiza un formulario liviano para editar solo fechas del proyecto.
    public function editDate(Request $request, Project $project): string
    {
        $this->authorize('update', $project);

        return view('projects._modal_date_form', [
            'project' => $project,
            'action' => route('projects.date', $project),
        ])->render();
    }

    // Actualiza la fecha del proyecto sin pasar por el formulario completo.
    public function updateDate(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
        ]);

        $project->update([
            'start_date' => $data['start_date'] ?? null,
        ]);

        $this->loadProjectListRelations($project);

        return response()->json([
            'id' => $project->id,
            'row_html' => view($this->projectListPartial($request), compact('project'))->render(),
            'message' => 'Fecha de inicio actualizada correctamente.',
        ]);
    }

    // Cambia el estado operativo del proyecto, lo que impacta flujos de estructura y transacciones.
    public function updateStatus(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);
        $statuses = ['planning', 'active', 'paused', 'completed', 'cancelled'];

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', $statuses)],
        ]);

        $project->update([
            'status' => $data['status'],
        ]);

        $this->loadProjectListRelations($project);

        return response()->json([
            'id' => $project->id,
            'row_html' => view($this->projectListPartial($request), compact('project'))->render(),
            'message' => 'Estado del proyecto actualizado correctamente.',
        ]);
    }

    // Archiva el proyecto solo si no tiene dependencias activas incompatibles.
    public function destroy(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $project);

        if ($this->projectHasDependencies($project)) {
            $message = 'El proyecto no puede eliminarse porque tiene dependencias registradas.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->route('projects.index')->with('status', $message);
        }

        $project->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $project->id,
                'message' => 'Proyecto archivado correctamente.',
            ]);
        }

        return redirect()
            ->route('projects.index')
            ->with('status', 'Proyecto archivado correctamente.');
    }

    // Devuelve empresas disponibles para formularios según el alcance del usuario autenticado.
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

    // Resuelve la vista parcial de una sola fila o tarjeta según el modo de listado actual.
    protected function projectListPartial(Request $request): string
    {
        return $request->user()?->isSuperAdmin()
            ? self::PROJECT_ROW_VIEW
            : self::PROJECT_CARD_VIEW;
    }

    // Resuelve el contenedor parcial completo para refrescar la lista de proyectos por AJAX.
    protected function projectListContainerPartial(Request $request): string
    {
        return $request->user()?->isSuperAdmin()
            ? self::PROJECT_TABLE_LIST_VIEW
            : self::PROJECT_CARD_LIST_VIEW;
    }

    // Construye la query base del índice con filtros de búsqueda, estado y tenant.
    protected function buildIndexQuery(Request $request, ?int $companyId, string $search, string $status)
    {
        return Project::query()
            ->with('company')
            ->withCount([
                'categories as active_categories_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
                'expenses as active_expenses_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            ])
            ->when(! $request->user()?->isSuperAdmin(), fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status));
    }

    // Determina si el proyecto conserva relaciones activas que impiden archivarlo.
    protected function projectHasDependencies(Project $project): bool
    {
        return $project->categories()->where('status', '!=', EntityStatus::Deleted->value)->exists()
            || $project->expenses()->where('status', '!=', EntityStatus::Deleted->value)->exists();
    }

    // Precarga relaciones usadas por los parciales de listado para evitar consultas repetidas.
    protected function loadProjectListRelations(Project $project): void
    {
        $project->load('company');
        $project->loadCount([
            'categories as active_categories_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            'expenses as active_expenses_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
        ]);
    }
}
