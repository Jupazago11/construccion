<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\AuxiliaryStoreRequest;
use App\Http\Requests\AuxiliaryUpdateRequest;
use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Http\Requests\ProjectCategoryCopyRequest;
use App\Http\Requests\SubcategoryStoreRequest;
use App\Http\Requests\SubcategoryUpdateRequest;
use App\Models\Auxiliary;
use App\Models\Category;
use App\Models\Project;
use App\Models\Subcategory;
use App\Services\ProjectCategoryReplicator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectCategoryController extends Controller
{
    public function index(Project $project): View
    {
        $this->authorize('view', $project);

        return view('project-categories.index', [
            'project' => $this->loadProjectStructure($project),
            'summary' => $this->summaryData($project),
            'projectAllowsNewRecords' => $this->projectAllowsNewRecords($project),
            'availableSourceProjects' => $this->availableSourceProjects($project),
        ]);
    }

    public function createCopy(Request $request, Project $project): View|string
    {
        $this->authorize('view', $project);
        $this->authorize('create', Category::class);
        $this->ensureProjectAllowsNewRecords($project);

        return view('project-categories._copy_modal_form', [
            'project' => $project,
            'sourceProjects' => $this->availableSourceProjects($project),
            'action' => route('projects.categories.copy.store', $project),
            'method' => 'POST',
        ])->render();
    }

    public function storeCopy(
        ProjectCategoryCopyRequest $request,
        Project $project,
        ProjectCategoryReplicator $replicator
    ): JsonResponse {
        $this->authorize('view', $project);
        $this->authorize('create', Category::class);
        $this->ensureProjectAllowsNewRecords($project);

        $sourceProject = Project::query()
            ->whereKey($request->validated('source_project_id'))
            ->where('company_id', $project->company_id)
            ->firstOrFail();

        $this->authorize('view', $sourceProject);

        if (! $sourceProject->categories()->where('status', '!=', EntityStatus::Deleted->value)->exists()) {
            return response()->json([
                'message' => 'El proyecto origen no tiene categorías disponibles para copiar.',
            ], 422);
        }

        $counts = $replicator->copy($sourceProject, $project);

        activity('categories')
            ->causedBy($request->user())
            ->performedOn($project)
            ->event('copy')
            ->withProperties([
                'source_project_id' => $sourceProject->id,
                'source_project_name' => $sourceProject->name,
                'copied' => $counts,
            ])
            ->log('copy');

        return $this->structureResponse(
            $project,
            "Estructura copiada correctamente. {$counts['categories']} categorías, {$counts['subcategories']} subcategorías y {$counts['auxiliaries']} auxiliares agregados."
        );
    }

    public function createCategory(Request $request, Project $project): View|string
    {
        $this->authorize('view', $project);
        $this->authorize('create', Category::class);
        $this->ensureProjectAllowsNewRecords($project);

        return view('project-categories._category_modal_form', [
            'project' => $project,
            'category' => new Category(),
            'action' => route('projects.categories.store', $project),
            'method' => 'POST',
        ])->render();
    }

    public function storeCategory(CategoryStoreRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('create', Category::class);
        $this->ensureProjectAllowsNewRecords($project);

        $category = Category::query()->create([
            'project_id' => $project->id,
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'sort_order' => $this->nextCategorySortOrder($project),
            'status' => EntityStatus::Active->value,
        ]);

        return $this->structureResponse($project, 'Categoría creada correctamente.', [
            'type' => 'category',
            'id' => $category->id,
        ]);
    }

    public function editCategory(Request $request, Project $project, Category $category): View|string
    {
        $this->authorize('view', $project);
        $this->guardCategoryBelongsToProject($project, $category);
        $this->authorize('update', $category);

        return view('project-categories._category_modal_form', [
            'project' => $project,
            'category' => $category,
            'action' => route('projects.categories.update', [$project, $category]),
            'method' => 'PATCH',
        ])->render();
    }

    public function updateCategory(CategoryUpdateRequest $request, Project $project, Category $category): JsonResponse
    {
        $this->authorize('view', $project);
        $this->guardCategoryBelongsToProject($project, $category);
        $this->authorize('update', $category);

        $category->update([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
        ]);

        return $this->structureResponse($project, 'Categoría actualizada correctamente.');
    }

    public function updateCategoryStatus(Request $request, Project $project, Category $category): JsonResponse
    {
        $this->authorize('view', $project);
        $this->guardCategoryBelongsToProject($project, $category);
        $this->authorize('update', $category);

        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $category->update(['status' => $data['status']]);

        return $this->structureResponse($project, 'Estado de la categoría actualizado correctamente.');
    }

    public function destroyCategory(Project $project, Category $category): JsonResponse
    {
        $this->authorize('view', $project);
        $this->guardCategoryBelongsToProject($project, $category);
        $this->authorize('delete', $category);

        if ($this->categoryHasExpenseRecords($category)) {
            return response()->json([
                'message' => 'La categoría no puede archivarse porque ya tiene gastos vigentes registrados.',
            ], 422);
        }

        DB::transaction(function () use ($category) {
            Auxiliary::query()
                ->whereIn('subcategory_id', Subcategory::query()
                    ->where('category_id', $category->id)
                    ->select('id'))
                ->update(['status' => EntityStatus::Deleted->value]);

            Subcategory::query()
                ->where('category_id', $category->id)
                ->update(['status' => EntityStatus::Deleted->value]);

            $category->update(['status' => EntityStatus::Deleted->value]);
        });

        return $this->structureResponse($project, 'Categoría archivada correctamente junto con su estructura vacía.');
    }

    public function reorderCategories(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('create', Category::class);
        $this->ensureProjectAllowsNewRecords($project);

        $ids = $this->validatedOrderIds($request);
        $categories = Category::query()
            ->where('project_id', $project->id)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        if ($categories->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'order' => 'El orden enviado contiene categorías que no pertenecen al proyecto.',
            ]);
        }

        foreach ($ids as $index => $id) {
            $categories[$id]->update(['sort_order' => $index + 1]);
        }

        return response()->json(['message' => 'Orden de categorías actualizado correctamente.']);
    }

    public function createSubcategory(Request $request, Project $project): View|string
    {
        $this->authorize('view', $project);
        $this->authorize('create', Subcategory::class);
        $this->ensureProjectAllowsNewRecords($project);

        $selectedCategoryId = $request->integer('category_id') ?: null;
        $categories = $this->availableCategories($project);
        $selectedCategory = $selectedCategoryId
            ? $categories->firstWhere('id', $selectedCategoryId)
            : null;

        return view('project-categories._subcategory_modal_form', [
            'project' => $project,
            'subcategory' => new Subcategory(['category_id' => $selectedCategoryId]),
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'lockCategory' => $selectedCategory !== null,
            'action' => route('projects.subcategories.store', $project),
            'method' => 'POST',
        ])->render();
    }

    public function storeSubcategory(SubcategoryStoreRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('create', Subcategory::class);
        $this->ensureProjectAllowsNewRecords($project);

        $subcategory = Subcategory::query()->create([
            'category_id' => $request->validated('category_id'),
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'sort_order' => $this->nextSubcategorySortOrder((int) $request->validated('category_id')),
            'status' => EntityStatus::Active->value,
        ]);

        return $this->structureResponse($project, 'Subcategoría creada correctamente.', [
            'type' => 'subcategory',
            'id' => $subcategory->id,
        ]);
    }

    public function editSubcategory(Project $project, Subcategory $subcategory): View|string
    {
        $this->authorize('view', $project);
        $this->guardSubcategoryBelongsToProject($project, $subcategory);
        $this->authorize('update', $subcategory);

        $categories = $this->availableCategories($project);

        return view('project-categories._subcategory_modal_form', [
            'project' => $project,
            'subcategory' => $subcategory,
            'categories' => $categories,
            'selectedCategory' => $categories->firstWhere('id', $subcategory->category_id),
            'lockCategory' => false,
            'action' => route('projects.subcategories.update', [$project, $subcategory]),
            'method' => 'PATCH',
        ])->render();
    }

    public function updateSubcategory(SubcategoryUpdateRequest $request, Project $project, Subcategory $subcategory): JsonResponse
    {
        $this->authorize('view', $project);
        $this->guardSubcategoryBelongsToProject($project, $subcategory);
        $this->authorize('update', $subcategory);

        $subcategory->update([
            'category_id' => $request->validated('category_id'),
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
        ]);

        return $this->structureResponse($project, 'Subcategoría actualizada correctamente.');
    }

    public function updateSubcategoryStatus(Request $request, Project $project, Subcategory $subcategory): JsonResponse
    {
        $this->authorize('view', $project);
        $this->guardSubcategoryBelongsToProject($project, $subcategory);
        $this->authorize('update', $subcategory);

        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $subcategory->update(['status' => $data['status']]);

        return $this->structureResponse($project, 'Estado de la subcategoría actualizado correctamente.');
    }

    public function destroySubcategory(Project $project, Subcategory $subcategory): JsonResponse
    {
        $this->authorize('view', $project);
        $this->guardSubcategoryBelongsToProject($project, $subcategory);
        $this->authorize('delete', $subcategory);

        if ($this->subcategoryHasExpenseRecords($subcategory)) {
            return response()->json([
                'message' => 'La subcategoría no puede archivarse porque ya tiene gastos vigentes registrados.',
            ], 422);
        }

        DB::transaction(function () use ($subcategory) {
            $subcategory->auxiliaries()->update(['status' => EntityStatus::Deleted->value]);
            $subcategory->update(['status' => EntityStatus::Deleted->value]);
        });

        return $this->structureResponse($project, 'Subcategoría archivada correctamente junto con sus auxiliares vacíos.');
    }

    public function reorderSubcategories(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('create', Subcategory::class);
        $this->ensureProjectAllowsNewRecords($project);

        $data = $request->validate([
            'category_id' => ['required', 'integer'],
        ]);
        $ids = $this->validatedOrderIds($request);
        $category = Category::query()
            ->whereKey($data['category_id'])
            ->where('project_id', $project->id)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->firstOrFail();
        $subcategories = Subcategory::query()
            ->where('category_id', $category->id)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        if ($subcategories->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'order' => 'El orden enviado contiene subcategorías que no pertenecen a la categoría.',
            ]);
        }

        foreach ($ids as $index => $id) {
            $subcategories[$id]->update(['sort_order' => $index + 1]);
        }

        return response()->json(['message' => 'Orden de subcategorías actualizado correctamente.']);
    }

    public function createAuxiliary(Request $request, Project $project): View|string
    {
        $this->authorize('view', $project);
        $this->authorize('create', Auxiliary::class);
        $this->ensureProjectAllowsNewRecords($project);

        $selectedSubcategoryId = $request->integer('subcategory_id');

        abort_unless($selectedSubcategoryId, 404);

        $subcategory = Subcategory::query()
            ->whereKey($selectedSubcategoryId)
            ->whereHas('category', fn ($query) => $query->where('project_id', $project->id))
            ->firstOrFail();

        return view('project-categories._auxiliary_modal_form', [
            'project' => $project,
            'auxiliary' => new Auxiliary([
                'subcategory_id' => $subcategory->id,
                'status' => EntityStatus::Active->value,
            ]),
            'subcategory' => $subcategory,
            'subcategories' => collect([$subcategory]),
            'action' => route('projects.auxiliaries.store', $project),
            'method' => 'POST',
            'isCreating' => true,
        ])->render();
    }

    public function storeAuxiliary(AuxiliaryStoreRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('create', Auxiliary::class);
        $this->ensureProjectAllowsNewRecords($project);

        $auxiliary = Auxiliary::query()->create([
            'subcategory_id' => $request->validated('subcategory_id'),
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'sort_order' => $this->nextAuxiliarySortOrder((int) $request->validated('subcategory_id')),
            'status' => EntityStatus::Active->value,
        ]);

        return $this->structureResponse($project, 'Auxiliar creado correctamente.', [
            'type' => 'auxiliary',
            'id' => $auxiliary->id,
        ]);
    }

    public function editAuxiliary(Project $project, Auxiliary $auxiliary): View|string
    {
        $this->authorize('view', $project);
        $this->guardAuxiliaryBelongsToProject($project, $auxiliary);
        $this->authorize('update', $auxiliary);

        return view('project-categories._auxiliary_modal_form', [
            'project' => $project,
            'auxiliary' => $auxiliary,
            'subcategories' => $this->availableSubcategories($project),
            'action' => route('projects.auxiliaries.update', [$project, $auxiliary]),
            'method' => 'PATCH',
            'isCreating' => false,
        ])->render();
    }

    public function updateAuxiliary(AuxiliaryUpdateRequest $request, Project $project, Auxiliary $auxiliary): JsonResponse
    {
        $this->authorize('view', $project);
        $this->guardAuxiliaryBelongsToProject($project, $auxiliary);
        $this->authorize('update', $auxiliary);

        $auxiliary->update([
            'subcategory_id' => $request->validated('subcategory_id'),
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
        ]);

        return $this->structureResponse($project, 'Auxiliar actualizado correctamente.');
    }

    public function updateAuxiliaryStatus(Request $request, Project $project, Auxiliary $auxiliary): JsonResponse
    {
        $this->authorize('view', $project);
        $this->guardAuxiliaryBelongsToProject($project, $auxiliary);
        $this->authorize('update', $auxiliary);

        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $auxiliary->update(['status' => $data['status']]);

        return $this->structureResponse($project, 'Estado del auxiliar actualizado correctamente.');
    }

    public function destroyAuxiliary(Project $project, Auxiliary $auxiliary): JsonResponse
    {
        $this->authorize('view', $project);
        $this->guardAuxiliaryBelongsToProject($project, $auxiliary);
        $this->authorize('delete', $auxiliary);

        if ($auxiliary->expenses()->where('status', '!=', EntityStatus::Deleted->value)->exists()) {
            return response()->json([
                'message' => 'El auxiliar no puede archivarse porque ya tiene gastos vigentes registrados.',
            ], 422);
        }

        $auxiliary->update(['status' => EntityStatus::Deleted->value]);

        return $this->structureResponse($project, 'Auxiliar archivado correctamente.');
    }

    public function reorderAuxiliaries(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('create', Auxiliary::class);
        $this->ensureProjectAllowsNewRecords($project);

        $data = $request->validate([
            'subcategory_id' => ['required', 'integer'],
        ]);
        $ids = $this->validatedOrderIds($request);
        $subcategory = Subcategory::query()
            ->whereKey($data['subcategory_id'])
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->whereHas('category', fn ($query) => $query->where('project_id', $project->id))
            ->firstOrFail();
        $auxiliaries = Auxiliary::query()
            ->where('subcategory_id', $subcategory->id)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        if ($auxiliaries->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'order' => 'El orden enviado contiene auxiliares que no pertenecen a la subcategoría.',
            ]);
        }

        foreach ($ids as $index => $id) {
            $auxiliaries[$id]->update(['sort_order' => $index + 1]);
        }

        return response()->json(['message' => 'Orden de auxiliares actualizado correctamente.']);
    }

    protected function structureResponse(Project $project, string $message, ?array $created = null): JsonResponse
    {
        $loadedProject = $this->loadProjectStructure($project->fresh());

        return response()->json([
            'summary_html' => view('project-categories._summary', [
                'project' => $loadedProject,
                'summary' => $this->summaryData($loadedProject),
            ])->render(),
            'structure_html' => view('project-categories._structure', [
                'project' => $loadedProject,
                'projectAllowsNewRecords' => $this->projectAllowsNewRecords($loadedProject),
            ])->render(),
            'expense_structure' => $this->expenseStructureData($loadedProject),
            'created' => $created,
            'message' => $message,
        ]);
    }

    protected function expenseStructureData(Project $project): array
    {
        $categories = [];
        $subcategories = [];
        $auxiliaries = [];

        foreach ($project->categories as $category) {
            $categories[] = [
                'id' => $category->id,
                'project_id' => $project->id,
                'name' => $category->name,
            ];

            foreach ($category->subcategories as $subcategory) {
                $subcategories[] = [
                    'id' => $subcategory->id,
                    'project_id' => $project->id,
                    'category_id' => $category->id,
                    'name' => $subcategory->name,
                ];

                foreach ($subcategory->auxiliaries as $auxiliary) {
                    $auxiliaries[] = [
                        'id' => $auxiliary->id,
                        'project_id' => $project->id,
                        'category_id' => $category->id,
                        'subcategory_id' => $subcategory->id,
                        'name' => $auxiliary->name,
                    ];
                }
            }
        }

        return [
            'categories' => $categories,
            'subcategories' => $subcategories,
            'auxiliaries' => $auxiliaries,
        ];
    }

    protected function loadProjectStructure(Project $project): Project
    {
        return $project->load([
            'company',
            'categories' => fn ($categoryQuery) => $categoryQuery
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->withExists([
                    'expenses as has_active_expenses' => fn ($expenseQuery) => $expenseQuery
                        ->where('status', '!=', EntityStatus::Deleted->value),
                ])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->with([
                    'subcategories' => fn ($subcategoryQuery) => $subcategoryQuery
                        ->where('status', '!=', EntityStatus::Deleted->value)
                        ->withExists([
                            'expenses as has_active_expenses' => fn ($expenseQuery) => $expenseQuery
                                ->where('status', '!=', EntityStatus::Deleted->value),
                        ])
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->with([
                            'auxiliaries' => fn ($auxiliaryQuery) => $auxiliaryQuery
                                ->where('status', '!=', EntityStatus::Deleted->value)
                                ->withExists([
                                    'expenses as has_active_expenses' => fn ($expenseQuery) => $expenseQuery
                                        ->where('status', '!=', EntityStatus::Deleted->value),
                                ])
                                ->orderBy('sort_order')
                                ->orderBy('name'),
                        ]),
                ]),
        ]);
    }

    protected function summaryData(Project $project): array
    {
        return [
            'categories' => Category::query()
                ->where('project_id', $project->id)
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->count(),
            'subcategories' => Subcategory::query()
                ->whereHas('category', fn ($query) => $query->where('project_id', $project->id))
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->count(),
            'auxiliaries' => Auxiliary::query()
                ->whereHas('subcategory.category', fn ($query) => $query->where('project_id', $project->id))
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->count(),
        ];
    }

    protected function availableCategories(Project $project)
    {
        return Category::query()
            ->where('project_id', $project->id)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function availableSubcategories(Project $project)
    {
        return Subcategory::query()
            ->whereHas('category', fn ($query) => $query->where('project_id', $project->id))
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function nextCategorySortOrder(Project $project): int
    {
        return ((int) Category::query()->where('project_id', $project->id)->max('sort_order')) + 1;
    }

    protected function nextSubcategorySortOrder(int $categoryId): int
    {
        return ((int) Subcategory::query()->where('category_id', $categoryId)->max('sort_order')) + 1;
    }

    protected function nextAuxiliarySortOrder(int $subcategoryId): int
    {
        return ((int) Auxiliary::query()->where('subcategory_id', $subcategoryId)->max('sort_order')) + 1;
    }

    protected function categoryHasExpenseRecords(Category $category): bool
    {
        return $category->expenses()
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->exists()
            || Subcategory::query()
                ->where('category_id', $category->id)
                ->whereHas('expenses', fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
                ->exists()
            || Auxiliary::query()
                ->whereIn('subcategory_id', Subcategory::query()
                    ->where('category_id', $category->id)
                    ->select('id'))
                ->whereHas('expenses', fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
                ->exists();
    }

    protected function subcategoryHasExpenseRecords(Subcategory $subcategory): bool
    {
        return $subcategory->expenses()
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->exists()
            || $subcategory->auxiliaries()
                ->whereHas('expenses', fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
                ->exists();
    }

    protected function validatedOrderIds(Request $request): array
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'integer'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['order'])));

        if (count($ids) !== count($data['order'])) {
            throw ValidationException::withMessages([
                'order' => 'El orden enviado contiene registros repetidos.',
            ]);
        }

        return $ids;
    }

    protected function availableSourceProjects(Project $project)
    {
        return Project::query()
            ->where('company_id', $project->company_id)
            ->where('id', '!=', $project->id)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->whereHas('categories', fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->orderBy('name')
            ->get();
    }

    protected function projectAllowsNewRecords(Project $project): bool
    {
        return in_array($project->status, ['planning', 'active'], true);
    }

    protected function ensureProjectAllowsNewRecords(Project $project): void
    {
        if ($this->projectAllowsNewRecords($project)) {
            return;
        }

        throw ValidationException::withMessages([
            'project' => 'Este proyecto no permite nuevos registros mientras esté pausado, completado, cancelado o archivado.',
        ]);
    }

    protected function guardCategoryBelongsToProject(Project $project, Category $category): void
    {
        abort_unless($category->project_id === $project->id, 404);
    }

    protected function guardSubcategoryBelongsToProject(Project $project, Subcategory $subcategory): void
    {
        abort_unless($subcategory->category()->where('project_id', $project->id)->exists(), 404);
    }

    protected function guardAuxiliaryBelongsToProject(Project $project, Auxiliary $auxiliary): void
    {
        abort_unless(
            $auxiliary->subcategory()
                ->whereHas('category', fn ($query) => $query->where('project_id', $project->id))
                ->exists(),
            404
        );
    }
}
