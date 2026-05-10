<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\ExpenseStoreRequest;
use App\Http\Requests\ExpenseUpdateRequest;
use App\Models\Project;
use App\Models\Expense;
use App\Models\Provider;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);

        $authUser = $request->user();
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $projectId = $request->integer('project_id') ?: null;
        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $expenses = Expense::query()
            ->with(['company', 'project', 'category', 'subcategory', 'auxiliary', 'provider'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('expense_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('provider', fn ($providerQuery) => $providerQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('expense_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('expense_date', '<=', $dateTo))
            ->latest('expense_date')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('expenses.index', [
            'expenses' => $expenses,
            'companies' => $authUser->isSuperAdmin()
                ? \App\Models\Company::query()->where('status', '!=', EntityStatus::Deleted->value)->orderBy('name')->get()
                : collect(),
            'projects' => $this->availableProjectsForExpenses($authUser, $companyId),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'company_id' => $companyId,
                'project_id' => $projectId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function create(Request $request): View|string|RedirectResponse
    {
        $this->authorize('create', Expense::class);

        if ($request->ajax()) {
            return view('expenses._modal_form', [
                'expense' => new Expense([
                    'expense_date' => now()->toDateString(),
                    'status' => 'active',
                    'payment_method' => 'cash',
                ]),
                'payload' => $this->formPayload($request->user()),
                'action' => route('expenses.store'),
                'method' => 'POST',
            ])->render();
        }

        return redirect()
            ->route('expenses.index')
            ->with('status', 'La creación de gastos se realiza desde la vista principal.');
    }

    public function store(ExpenseStoreRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Expense::class);

        $data = $request->validated();
        $project = Project::query()->with('company')->findOrFail($data['project_id']);

        $this->guardProjectStateForMutations($project);
        $this->guardExpenseHierarchy($data, $project);

        $expense = Expense::query()->create([
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'category_id' => $data['category_id'],
            'subcategory_id' => $data['subcategory_id'],
            'auxiliary_id' => $data['auxiliary_id'] ?? null,
            'provider_id' => $data['provider_id'] ?? null,
            'created_by' => $request->user()->id,
            'expense_number' => $data['expense_number'] ?? null,
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['payment_method'] ?? null,
            'description' => $data['description'],
            'subtotal_amount' => $data['subtotal_amount'],
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $this->calculateTotal((float) $data['subtotal_amount'], 0, 0),
            'status' => EntityStatus::Active->value,
        ]);

        $expense->load(['company', 'project', 'category', 'subcategory', 'auxiliary', 'provider']);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $expense->id,
                'row_html' => view('expenses._row', compact('expense'))->render(),
                'message' => 'Gasto creado correctamente.',
            ]);
        }

        return redirect()
            ->route('expenses.index')
            ->with('status', 'Gasto creado correctamente.');
    }

    public function edit(Request $request, Expense $expense): View|string|RedirectResponse
    {
        $this->authorize('update', $expense);

        if ($request->ajax()) {
            return view('expenses._modal_form', [
                'expense' => $expense,
                'payload' => $this->formPayload($request->user(), $expense),
                'action' => route('expenses.update', $expense),
                'method' => 'PATCH',
            ])->render();
        }

        return redirect()
            ->route('expenses.index')
            ->with('status', 'La edición de gastos se realiza desde la vista principal.');
    }

    public function update(ExpenseUpdateRequest $request, Expense $expense): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $expense);

        $data = $request->validated();
        $project = Project::query()->with('company')->findOrFail($data['project_id']);

        $this->guardProjectStateForMutations($project, $expense);
        $this->guardExpenseHierarchy($data, $project);

        $expense->update([
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'category_id' => $data['category_id'],
            'subcategory_id' => $data['subcategory_id'],
            'auxiliary_id' => $data['auxiliary_id'] ?? null,
            'provider_id' => $data['provider_id'] ?? null,
            'expense_number' => $data['expense_number'] ?? null,
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['payment_method'] ?? null,
            'description' => $data['description'],
            'subtotal_amount' => $data['subtotal_amount'],
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $this->calculateTotal((float) $data['subtotal_amount'], 0, 0),
        ]);

        $expense->load(['company', 'project', 'category', 'subcategory', 'auxiliary', 'provider']);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $expense->id,
                'row_html' => view('expenses._row', compact('expense'))->render(),
                'message' => 'Gasto actualizado correctamente.',
            ]);
        }

        return redirect()
            ->route('expenses.index')
            ->with('status', 'Gasto actualizado correctamente.');
    }

    public function updateStatus(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('update', $expense);

        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $expense->update([
            'status' => $data['status'],
        ]);

        $expense->load(['company', 'project', 'category', 'subcategory', 'auxiliary', 'provider']);

        return response()->json([
            'id' => $expense->id,
            'row_html' => view('expenses._row', compact('expense'))->render(),
            'message' => 'Estado del gasto actualizado correctamente.',
        ]);
    }

    public function destroy(Request $request, Expense $expense): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $expense);

        if ($expense->attachments()->where('status', '!=', EntityStatus::Deleted->value)->exists()) {
            $message = 'El gasto no puede archivarse porque tiene archivos adjuntos registrados.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->route('expenses.index')->with('status', $message);
        }

        $expense->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $expense->id,
                'message' => 'Gasto archivado correctamente.',
            ]);
        }

        return redirect()
            ->route('expenses.index')
            ->with('status', 'Gasto archivado correctamente.');
    }

    protected function formPayload($authUser, ?Expense $expense = null): array
    {
        $projectsCollection = $this->availableProjectsForExpenses($authUser, null, $expense?->project_id);
        $projectsCollection->load([
            'company',
            'categories' => fn ($categoryQuery) => $categoryQuery
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->with([
                    'subcategories' => fn ($subcategoryQuery) => $subcategoryQuery
                        ->where('status', '!=', EntityStatus::Deleted->value)
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->with([
                            'auxiliaries' => fn ($auxiliaryQuery) => $auxiliaryQuery
                                ->where('status', '!=', EntityStatus::Deleted->value)
                                ->orderBy('sort_order')
                                ->orderBy('name'),
                        ]),
                ]),
        ]);

        $projects = $projectsCollection->map(fn ($project) => [
            'id' => $project->id,
            'name' => $project->name,
            'company_id' => $project->company_id,
            'company_name' => $project->company?->name,
            'status' => $project->status,
        ])->values()->all();

        $categories = [];
        $subcategories = [];
        $auxiliaries = [];

        foreach ($projectsCollection as $project) {
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
        }

        $providers = $this->availableProviders($authUser)->map(fn ($provider) => [
            'id' => $provider->id,
            'name' => $provider->name,
            'company_id' => $provider->company_id,
        ])->values()->all();

        return [
            'projects' => $projects,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'auxiliaries' => $auxiliaries,
            'providers' => $providers,
            'paymentMethods' => [
                'cash' => 'Efectivo',
                'bank_transfer' => 'Transferencia bancaria',
                'credit_card' => 'Tarjeta de crédito',
                'debit_card' => 'Tarjeta débito',
                'other' => 'Otro',
            ],
        ];
    }

    protected function availableProjects($authUser, ?int $companyId = null)
    {
        return Project::query()
            ->when($authUser->isSuperAdmin(), function ($query) use ($companyId) {
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
            }, fn ($query) => $query->where('company_id', $authUser->company_id))
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->orderBy('name')
            ->get();
    }

    protected function availableProjectsForExpenses($authUser, ?int $companyId = null, ?int $currentProjectId = null)
    {
        return Project::query()
            ->when($authUser->isSuperAdmin(), function ($query) use ($companyId) {
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
            }, fn ($query) => $query->where('company_id', $authUser->company_id))
            ->where(function ($query) use ($currentProjectId) {
                $query
                    ->whereNotIn('status', ['cancelled', EntityStatus::Deleted->value]);

                if ($currentProjectId) {
                    $query->orWhere('id', $currentProjectId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    protected function availableProviders($authUser)
    {
        return Provider::query()
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('company_id', $authUser->company_id))
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->orderBy('name')
            ->get();
    }

    protected function guardProjectStateForMutations(Project $project, ?Expense $expense = null): void
    {
        if (in_array($project->status, ['planning', 'active'], true)) {
            return;
        }

        if ($expense && $expense->project_id === $project->id) {
            throw ValidationException::withMessages([
                'project_id' => 'No puedes modificar este gasto porque el proyecto está pausado, completado, cancelado o archivado.',
            ]);
        }

        throw ValidationException::withMessages([
            'project_id' => 'No puedes registrar gastos en un proyecto pausado, completado, cancelado o archivado.',
        ]);
    }

    protected function guardExpenseHierarchy(array $data, Project $project): void
    {
        $category = $project->categories()->whereKey($data['category_id'])->first();

        if (! $category) {
            throw ValidationException::withMessages([
                'category_id' => 'La categoría seleccionada no pertenece al proyecto.',
            ]);
        }

        $subcategory = $category->subcategories()->whereKey($data['subcategory_id'])->first();

        if (! $subcategory) {
            throw ValidationException::withMessages([
                'subcategory_id' => 'La subcategoría seleccionada no pertenece a la categoría indicada.',
            ]);
        }

        if (! empty($data['auxiliary_id'])) {
            $auxiliary = $subcategory->auxiliaries()->whereKey($data['auxiliary_id'])->first();

            if (! $auxiliary) {
                throw ValidationException::withMessages([
                    'auxiliary_id' => 'El auxiliar seleccionado no pertenece a la subcategoría indicada.',
                ]);
            }
        }

        if (! empty($data['provider_id']) && ! $project->company->providers()->whereKey($data['provider_id'])->exists()) {
            throw ValidationException::withMessages([
                'provider_id' => 'El proveedor seleccionado no pertenece a la empresa del proyecto.',
            ]);
        }
    }

    protected function calculateTotal(float $subtotal, float $tax, float $discount): float
    {
        $total = $subtotal + $tax - $discount;

        if ($total < 0) {
            throw ValidationException::withMessages([
                'discount_amount' => 'El descuento no puede dejar el total del gasto en un valor negativo.',
            ]);
        }

        return round($total, 2);
    }
}
