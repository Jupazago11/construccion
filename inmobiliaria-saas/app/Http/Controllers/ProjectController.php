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

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Project::class);

        $authUser = $request->user();
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;

        $projects = Project::query()
            ->with('company')
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
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

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

    public function create(Request $request): View|string
    {
        $this->authorize('create', Project::class);

        $authUser = $request->user();

        if ($request->ajax()) {
            return view('projects._modal_form', [
                'project' => new Project(),
                'companies' => $this->companiesForForm($authUser),
                'action' => route('projects.store'),
                'method' => 'POST',
            ])->render();
        }

        return view('projects.create', [
            'project' => new Project(),
            'companies' => $this->companiesForForm($authUser),
        ]);
    }

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
            'start_date' => $data['start_date'] ?? null,
            'status' => $data['status'],
        ]);

        $project->load('company');

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

    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        $project->load(['company']);
        $project->loadCount(['categories', 'expenses']);

        return view('projects.show', [
            'project' => $project,
        ]);
    }

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
            'status' => $data['status'],
        ]);

        $project->load('company');

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

    public function updateStatus(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'status' => ['required', 'in:planning,active,paused,completed,cancelled,deleted'],
        ]);

        $project->update([
            'status' => $data['status'],
        ]);

        $project->load('company');

        return response()->json([
            'id' => $project->id,
            'row_html' => view($this->projectListPartial($request), compact('project'))->render(),
            'message' => 'Estado del proyecto actualizado correctamente.',
        ]);
    }

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

    protected function projectListPartial(Request $request): string
    {
        return $request->user()?->isSuperAdmin()
            ? self::PROJECT_ROW_VIEW
            : self::PROJECT_CARD_VIEW;
    }

    protected function projectHasDependencies(Project $project): bool
    {
        return $project->categories()->count() > 0
            || $project->expenses()->count() > 0;
    }
}
