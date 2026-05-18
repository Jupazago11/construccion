<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\CatalogActivityGroupStoreRequest;
use App\Http\Requests\CatalogActivityGroupUpdateRequest;
use App\Http\Requests\CatalogActivityStoreRequest;
use App\Http\Requests\CatalogActivitySubgroupStoreRequest;
use App\Http\Requests\CatalogActivitySubgroupUpdateRequest;
use App\Http\Requests\CatalogActivityUpdateRequest;
use App\Models\CatalogActivity;
use App\Models\CatalogActivityGroup;
use App\Models\CatalogActivitySubgroup;
use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityCatalogController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', CatalogActivityGroup::class);

        $payload = $this->viewPayload($request);

        if ($request->ajax() && $request->boolean('table_only')) {
            return response()->json(array_merge([
                'table_html' => view('activity-catalog._activities_table', [
                    'activities' => $payload['activities'],
                ])->render(),
                'activities_table_html' => view('activity-catalog._activities_table', [
                    'activities' => $payload['activities'],
                ])->render(),
                'groups_table_html' => view('activity-catalog._groups_table', [
                    'groups' => $payload['groups'],
                ])->render(),
                'subgroups_table_html' => view('activity-catalog._subgroups_table', [
                    'subgroups' => $payload['subgroups'],
                ])->render(),
            ], $this->catalogCollections($payload)));
        }

        return view('activity-catalog.index', $payload);
    }

    public function storeGroup(CatalogActivityGroupStoreRequest $request): JsonResponse
    {
        $this->authorize('create', CatalogActivityGroup::class);

        CatalogActivityGroup::query()->create([
            'company_id' => $request->resolvedCompanyId(),
            'name' => $request->validated('name'),
            'status' => EntityStatus::Active->value,
        ]);

        return $this->catalogResponse('Grupo creado correctamente.');
    }

    public function updateGroup(CatalogActivityGroupUpdateRequest $request, CatalogActivityGroup $activityGroup): JsonResponse
    {
        $this->authorize('update', $activityGroup);

        $activityGroup->update(['name' => $request->validated('name')]);

        return $this->catalogResponse('Grupo actualizado correctamente.');
    }

    public function statusGroup(Request $request, CatalogActivityGroup $activityGroup): JsonResponse
    {
        $this->authorize('update', $activityGroup);
        $data = $request->validate(['status' => ['required', 'in:active,inactive']]);

        $activityGroup->update(['status' => $data['status']]);

        return $this->catalogResponse('Estado del grupo actualizado correctamente.');
    }

    public function destroyGroup(CatalogActivityGroup $activityGroup): JsonResponse
    {
        $this->authorize('delete', $activityGroup);

        if ($activityGroup->activities()->where(fn ($query) => $query->whereHas('expenses')->orWhereHas('purchases'))->exists()) {
            return response()->json(['message' => 'El grupo no puede archivarse porque tiene actividades usadas en movimientos.'], 422);
        }

        DB::transaction(function () use ($activityGroup) {
            $activityGroup->activities()->update(['status' => EntityStatus::Deleted->value]);
            $activityGroup->subgroups()->update(['status' => EntityStatus::Deleted->value]);
            $activityGroup->update(['status' => EntityStatus::Deleted->value]);
        });

        return $this->catalogResponse('Grupo archivado correctamente.');
    }

    public function storeSubgroup(CatalogActivitySubgroupStoreRequest $request): JsonResponse
    {
        $this->authorize('create', CatalogActivitySubgroup::class);

        CatalogActivitySubgroup::query()->create([
            'company_id' => $request->resolvedCompanyId(),
            'activity_group_id' => $request->validated('activity_group_id'),
            'name' => $request->validated('name'),
            'status' => EntityStatus::Active->value,
        ]);

        return $this->catalogResponse('Subgrupo creado correctamente.');
    }

    public function updateSubgroup(CatalogActivitySubgroupUpdateRequest $request, CatalogActivitySubgroup $activitySubgroup): JsonResponse
    {
        $this->authorize('update', $activitySubgroup);

        $activitySubgroup->update($request->validated());

        return $this->catalogResponse('Subgrupo actualizado correctamente.');
    }

    public function statusSubgroup(Request $request, CatalogActivitySubgroup $activitySubgroup): JsonResponse
    {
        $this->authorize('update', $activitySubgroup);
        $data = $request->validate(['status' => ['required', 'in:active,inactive']]);

        DB::transaction(function () use ($activitySubgroup, $data) {
            $activitySubgroup->update(['status' => $data['status']]);

            if ($data['status'] === EntityStatus::Inactive->value) {
                $activitySubgroup->activities()
                    ->where('status', '!=', EntityStatus::Deleted->value)
                    ->update(['status' => EntityStatus::Inactive->value]);
            }
        });

        return $this->catalogResponse(
            $data['status'] === EntityStatus::Inactive->value
                ? 'Subgrupo inactivado correctamente. Sus actividades también quedaron inactivas.'
                : 'Estado del subgrupo actualizado correctamente.'
        );
    }

    public function destroySubgroup(CatalogActivitySubgroup $activitySubgroup): JsonResponse
    {
        $this->authorize('delete', $activitySubgroup);

        if ($activitySubgroup->activities()->where(fn ($query) => $query->whereHas('expenses')->orWhereHas('purchases'))->exists()) {
            return response()->json(['message' => 'El subgrupo no puede archivarse porque tiene actividades usadas en movimientos.'], 422);
        }

        DB::transaction(function () use ($activitySubgroup) {
            $activitySubgroup->activities()->update(['status' => EntityStatus::Deleted->value]);
            $activitySubgroup->update(['status' => EntityStatus::Deleted->value]);
        });

        return $this->catalogResponse('Subgrupo archivado correctamente. Sus actividades también fueron archivadas.');
    }

    public function storeActivity(CatalogActivityStoreRequest $request): JsonResponse
    {
        $this->authorize('create', CatalogActivity::class);

        CatalogActivity::query()->create([
            'company_id' => $request->resolvedCompanyId(),
            'activity_group_id' => $request->validated('activity_group_id'),
            'activity_subgroup_id' => $request->validated('activity_subgroup_id'),
            'name' => $request->validated('name'),
            'status' => EntityStatus::Active->value,
        ]);

        return $this->catalogResponse('Actividad creada correctamente.');
    }

    public function updateActivity(CatalogActivityUpdateRequest $request, CatalogActivity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $activity->update($request->validated());

        return $this->catalogResponse('Actividad actualizada correctamente.');
    }

    public function statusActivity(Request $request, CatalogActivity $activity): JsonResponse
    {
        $this->authorize('update', $activity);
        $data = $request->validate(['status' => ['required', 'in:active,inactive']]);

        $activity->update(['status' => $data['status']]);

        return $this->catalogResponse('Estado de la actividad actualizado correctamente.');
    }

    public function destroyActivity(CatalogActivity $activity): JsonResponse
    {
        $this->authorize('delete', $activity);

        if ($activity->expenses()->where('status', '!=', EntityStatus::Deleted->value)->exists()
            || $activity->purchases()->where('status', '!=', EntityStatus::Deleted->value)->exists()) {
            return response()->json(['message' => 'La actividad no puede archivarse porque ya tiene gastos o compras.'], 422);
        }

        $activity->update(['status' => EntityStatus::Deleted->value]);

        return $this->catalogResponse('Actividad archivada correctamente.');
    }

    protected function viewPayload(Request $request): array
    {
        $authUser = $request->user();
        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;

        $filters = [
            'company_id' => $companyId,
            'search' => trim($request->input('search', '')),
            'group_id' => $request->integer('group_id') ?: null,
            'subgroup_id' => $request->integer('subgroup_id') ?: null,
        ];

        $groups = CatalogActivityGroup::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->withCount(['subgroups', 'activities'])
            ->orderBy('name')
            ->get();

        $subgroups = CatalogActivitySubgroup::query()
            ->with('group')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->orderBy('name')
            ->get();

        $activities = CatalogActivity::query()
            ->with(['group', 'subgroup'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when(! $authUser->isSuperAdmin(), fn ($q) => $q->where('status', '!=', EntityStatus::Deleted->value))
            ->when($filters['search'], fn ($q) => $q->where('name', 'ilike', '%' . $filters['search'] . '%'))
            ->when($filters['group_id'], fn ($q) => $q->where('activity_group_id', $filters['group_id']))
            ->when($filters['subgroup_id'], fn ($q) => $q->where('activity_subgroup_id', $filters['subgroup_id']))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return [
            'companies' => $authUser->isSuperAdmin()
                ? Company::query()->where('status', '!=', EntityStatus::Deleted->value)->orderBy('name')->get()
                : collect(),
            'filters' => $filters,
            'groups' => $groups,
            'subgroups' => $subgroups,
            'activities' => $activities,
        ];
    }

    protected function catalogResponse(string $message): JsonResponse
    {
        return response()->json(['message' => $message]);
    }

    protected function catalogCollections(array $payload): array
    {
        return [
            'groups' => $payload['groups']
                ->where('status', EntityStatus::Active->value)
                ->values()
                ->map(fn ($group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'company_id' => $group->company_id,
                ]),
            'subgroups' => $payload['subgroups']
                ->where('status', EntityStatus::Active->value)
                ->values()
                ->map(fn ($subgroup) => [
                    'id' => $subgroup->id,
                    'name' => $subgroup->name,
                    'company_id' => $subgroup->company_id,
                    'activity_group_id' => $subgroup->activity_group_id,
                ]),
        ];
    }
}
