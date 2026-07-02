<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\ExpenseStoreRequest;
use App\Http\Requests\ExpenseUpdateRequest;
use App\Models\CatalogActivity;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Models\Provider2;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    // Lista gastos con filtros tenant y puede responder tabla/paginación parcial vía AJAX.
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Expense::class);

        $authUser = $request->user();
        $search = trim((string) $request->string('search'));
        $projectId = $request->integer('project_id') ?: null;
        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();
        $transactionView = in_array($request->string('transaction_view')->toString(), ['individual', 'invoice'], true)
            ? $request->string('transaction_view')->toString()
            : '';

        $expenses = $this->buildIndexQuery($request, $companyId, $projectId, $search, $dateFrom, $dateTo, $transactionView)
            ->latest('expense_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('expenses._table_body', compact('expenses'))->render(),
                'pagination_html' => $expenses->links('pagination::tailwind')->toHtml(),
            ]);
        }

        return view('expenses.index', [
            'expenses' => $expenses,
            'companies' => $authUser->isSuperAdmin()
                ? \App\Models\Company::query()->where('status', '!=', EntityStatus::Deleted->value)->orderBy('name')->get()
                : collect(),
            'projects' => $this->availableProjectsForExpenses($authUser, $companyId),
            'filters' => [
                'search' => $search,
                'company_id' => $companyId,
                'project_id' => $projectId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'transaction_view' => $transactionView,
            ],
        ]);
    }

    // Renderiza el modal de creación de gasto desde la vista principal.
    public function create(Request $request): View|string|RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $selectedProjectId = $request->integer('project_id') ?: null;

        if ($request->ajax()) {
            return view('expenses._modal_form', [
                'expense' => new Expense([
                    'project_id' => $selectedProjectId,
                    'invoice_id' => $request->integer('invoice_id') ?: null,
                    'expense_date' => now()->toDateString(),
                    'status' => 'active',
                ]),
                'payload' => $this->formPayload($request->user(), null, $selectedProjectId),
                'action' => route('expenses.store'),
                'method' => 'POST',
            ])->render();
        }

        return redirect()
            ->route('expenses.index')
            ->with('status', 'La creación de gastos se realiza desde la vista principal.');
    }

    // Crea un gasto, valida consistencia del proyecto y recalcula la factura asociada si existe.
    public function store(ExpenseStoreRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Expense::class);

        $data = $request->validated();
        $project = Project::query()->with('company')->findOrFail($data['project_id']);

        $this->guardProjectStateForMutations($project);
        $this->guardTransactionCatalog($data, $project);

        $unitPrice = (float) $data['unit_price'];
        $quantity = isset($data['quantity']) && $data['quantity'] !== '' ? (float) $data['quantity'] : null;
        $subtotal = round($unitPrice * ($quantity ?? 1), 2);

        $expense = Expense::query()->create([
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'category_id' => null,
            'subcategory_id' => null,
            'auxiliary_id' => null,
            'provider_id' => $data['provider_id'],
            'invoice_id' => $data['invoice_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'activity_id' => $data['activity_id'] ?? null,
            'created_by' => $request->user()->id,
            'expense_number' => $data['expense_number'] ?? null,
            'expense_date' => $data['expense_date'],
            'payment_method' => null,
            'description' => $data['description'] ?? null,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal_amount' => $subtotal,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $this->calculateTotal($subtotal, 0, 0),
            'status' => EntityStatus::Active->value,
        ]);

        $this->refreshInvoiceTotal($expense->invoice_id);
        $expense->load(['company', 'project', 'provider', 'invoice.project', 'invoice.provider', 'product.group', 'product.subgroup', 'activity.group', 'activity.subgroup']);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $expense->id,
                'row_html' => view('expenses._row', compact('expense'))->render(),
                'table_html' => $this->tableHtml($request),
                'message' => 'Gasto creado correctamente.',
            ]);
        }

        return redirect()
            ->route('expenses.index')
            ->with('status', 'Gasto creado correctamente.');
    }

    // Renderiza el modal de edición de un gasto existente.
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

    // Actualiza un gasto y sincroniza el total de la factura anterior o actual cuando cambia el enlace.
    public function update(ExpenseUpdateRequest $request, Expense $expense): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $expense);

        $data = $request->validated();
        $project = Project::query()->with('company')->findOrFail($data['project_id']);

        $this->guardProjectStateForMutations($project, $expense);
        $this->guardTransactionCatalog($data, $project);
        $previousInvoiceId = $expense->invoice_id;

        $unitPrice = (float) $data['unit_price'];
        $quantity = isset($data['quantity']) && $data['quantity'] !== '' ? (float) $data['quantity'] : null;
        $subtotal = round($unitPrice * ($quantity ?? 1), 2);

        $expense->update([
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'category_id' => null,
            'subcategory_id' => null,
            'auxiliary_id' => null,
            'provider_id' => $data['provider_id'],
            'invoice_id' => $data['invoice_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'activity_id' => $data['activity_id'] ?? null,
            'expense_number' => $data['expense_number'] ?? null,
            'expense_date' => $data['expense_date'],
            'payment_method' => null,
            'description' => $data['description'] ?? null,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal_amount' => $subtotal,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $this->calculateTotal($subtotal, 0, 0),
        ]);

        $this->refreshInvoiceTotal($previousInvoiceId);
        $this->refreshInvoiceTotal($expense->invoice_id);
        $expense->load(['company', 'project', 'provider', 'invoice.project', 'invoice.provider', 'product.group', 'product.subgroup', 'activity.group', 'activity.subgroup']);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $expense->id,
                'row_html' => view('expenses._row', compact('expense'))->render(),
                'table_html' => $this->tableHtml($request),
                'invoice_detail_html' => $this->invoiceDetailHtml($request->integer('invoice_detail_id') ?: null),
                'message' => 'Gasto actualizado correctamente.',
            ]);
        }

        return redirect()
            ->route('expenses.index')
            ->with('status', 'Gasto actualizado correctamente.');
    }

    // Cambia el estado del gasto y refresca el total de la factura asociada.
    public function updateStatus(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('update', $expense);

        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        DB::transaction(function () use ($expense, $data) {
            $expense->update(['status' => $data['status']]);
            $this->refreshInvoiceTotal($expense->invoice_id);
        });

        $expense->load(['company', 'project', 'provider', 'invoice.project', 'invoice.provider', 'product.group', 'product.subgroup', 'activity.group', 'activity.subgroup']);

        return response()->json([
            'id' => $expense->id,
            'row_html' => view('expenses._row', compact('expense'))->render(),
            'table_html' => $this->tableHtml($request),
            'invoice_detail_html' => $this->invoiceDetailHtml($request->integer('invoice_detail_id') ?: null),
            'message' => 'Estado del gasto actualizado correctamente.',
        ]);
    }

    // Archiva el gasto y limpia su impacto sobre la factura relacionada.
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
        $this->refreshInvoiceTotal($expense->invoice_id);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $expense->id,
                'table_html' => $this->tableHtml($request),
                'invoice_detail_html' => $this->invoiceDetailHtml($request->integer('invoice_detail_id') ?: null),
                'message' => 'Gasto archivado correctamente.',
            ]);
        }

        return redirect()
            ->route('expenses.index')
            ->with('status', 'Gasto archivado correctamente.');
    }

    // Arma las colecciones necesarias para los formularios de gasto en modales AJAX.
    protected function formPayload($authUser, ?Expense $expense = null, ?int $preferredProjectId = null): array
    {
        $currentProjectId = $expense?->project_id ?? $preferredProjectId;

        $projectsCollection = $this->availableProjectsForExpenses($authUser, null, $currentProjectId);

        if ($preferredProjectId) {
            $projectsCollection = $projectsCollection->where('id', $preferredProjectId)->values();
        }

        $projectsCollection->load('company');

        $projects = $projectsCollection->map(fn ($project) => [
            'id' => $project->id,
            'name' => $project->name,
            'company_id' => $project->company_id,
            'company_name' => $project->company?->name,
            'status' => $project->status,
        ])->values()->all();

        $providers = $this->availableProviders($authUser)->map(fn ($provider) => [
            'id' => $provider->id,
            'name' => $provider->name,
            'company_id' => $provider->company_id,
        ])->values()->all();

        $products = $this->availableProducts($authUser)->map(fn ($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'company_id' => $product->company_id,
            'subgroup_name' => $product->subgroup?->name,
        ])->values()->all();

        $activities = $this->availableActivities($authUser)->map(fn ($activity) => [
            'id' => $activity->id,
            'name' => $activity->name,
            'company_id' => $activity->company_id,
            'subgroup_name' => $activity->subgroup?->name,
        ])->values()->all();

        return [
            'projects' => $projects,
            'providers' => $providers,
            'products' => $products,
            'activities' => $activities,
            'invoices' => $this->availableInvoices($authUser, 'expense')->map(fn ($invoice) => InvoiceController::serializeInvoice($invoice))->values()->all(),
            'invoiceStoreUrl' => route('invoices.store', [], false),
            'transactionType' => 'expense',
        ];
    }

    // Recompone la tabla principal de gastos usando los filtros actuales del request.
    protected function tableHtml(Request $request): string
    {
        $authUser = $request->user();
        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;
        $expenses = $this->buildIndexQuery(
            $request,
            $companyId,
            $request->integer('project_id') ?: null,
            trim((string) $request->string('search')),
            $request->string('date_from')->toString(),
            $request->string('date_to')->toString(),
            in_array($request->string('transaction_view')->toString(), ['individual', 'invoice'], true)
                ? $request->string('transaction_view')->toString()
                : ''
        )
            ->latest('expense_date')
            ->latest('id')
            ->paginate(10);

        return view('expenses._table_body', compact('expenses'))->render();
    }

    // Construye la query base del índice con relaciones y filtros de búsqueda.
    protected function buildIndexQuery(
        Request $request,
        ?int $companyId,
        ?int $projectId,
        string $search,
        string $dateFrom,
        string $dateTo,
        string $transactionView
    ) {
        return Expense::query()
            ->with(['company', 'project', 'provider', 'invoice.project', 'invoice.provider', 'product.group', 'product.subgroup', 'activity.group', 'activity.subgroup'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $request->user()?->isSuperAdmin(), fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->when($transactionView === 'individual', function ($query) {
                $query->where(function ($nested) {
                    $nested
                        ->whereNull('invoice_id')
                        ->orWhereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->where('status', EntityStatus::Deleted->value));
                });
            })
            ->when($transactionView === 'invoice', function ($query) {
                $query
                    ->whereNotNull('invoice_id')
                    ->whereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->where('status', '!=', EntityStatus::Deleted->value));
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('expense_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('quantity', 'like', "%{$search}%")
                        ->orWhereHas('provider', fn ($providerQuery) => $providerQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->where('invoice_number', 'like', "%{$search}%"))
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('activity', fn ($activityQuery) => $activityQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('expense_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('expense_date', '<=', $dateTo));
    }

    // Devuelve proyectos visibles para el usuario y filtros generales de la pantalla.
    protected function availableProjects($authUser, ?int $companyId = null)
    {
        return Project::query()
            ->when($authUser->isSuperAdmin(), function ($query) use ($companyId) {
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
            }, fn ($query) => $query->where('company_id', $authUser->company_id))
            ->where('status', EntityStatus::Active->value)
            ->orderBy('name')
            ->get();
    }

    // Devuelve proyectos habilitados para crear o editar gastos, incluso manteniendo el actual si está bloqueado.
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

    // Devuelve proveedores disponibles para el contexto del usuario actual.
    protected function availableProviders($authUser)
    {
        return Provider2::query()
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('company_id', $authUser->company_id))
            ->where('status', EntityStatus::Active->value)
            ->orderBy('name')
            ->get();
    }

    // Devuelve productos activos disponibles para asociar al gasto.
    protected function availableProducts($authUser)
    {
        return Product::query()
            ->with(['group', 'subgroup'])
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('company_id', $authUser->company_id))
            ->where('status', EntityStatus::Active->value)
            ->orderBy('name')
            ->get();
    }

    // Devuelve actividades activas disponibles para asociar al gasto.
    protected function availableActivities($authUser)
    {
        return CatalogActivity::query()
            ->with(['group', 'subgroup'])
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('company_id', $authUser->company_id))
            ->where('status', EntityStatus::Active->value)
            ->orderBy('name')
            ->get();
    }

    // Devuelve facturas abiertas o relacionadas con el registro actual para selección en formularios.
    protected function availableInvoices($authUser, string $type)
    {
        return Invoice::query()
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('company_id', $authUser->company_id))
            ->where('type', $type)
            ->where('status', 'open')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get();
    }

    // Impide crear o editar movimientos sobre proyectos cerrados o no editables.
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

    // Garantiza que producto o actividad pertenezcan a la misma empresa del proyecto.
    protected function guardTransactionCatalog(array $data, Project $project): void
    {
        if (! $project->company->providers2()
            ->whereKey($data['provider_id'])
            ->where('status', EntityStatus::Active->value)
            ->exists()) {
            throw ValidationException::withMessages([
                'provider_id' => 'El proveedor seleccionado no está activo o no pertenece a la empresa del proyecto.',
            ]);
        }

        if (! empty($data['activity_id'])) {
            if (! CatalogActivity::query()
                ->whereKey($data['activity_id'])
                ->where('company_id', $project->company_id)
                ->where('status', EntityStatus::Active->value)
                ->exists()) {
                throw ValidationException::withMessages([
                    'activity_id' => 'La actividad seleccionada no está activa o no pertenece a la empresa del proyecto.',
                ]);
            }
        } elseif (! Product::query()
            ->whereKey($data['product_id'])
            ->where('company_id', $project->company_id)
            ->where('status', EntityStatus::Active->value)
            ->exists()) {
            throw ValidationException::withMessages([
                'product_id' => 'El producto seleccionado no está activo o no pertenece a la empresa del proyecto.',
            ]);
        }

        if (! empty($data['invoice_id']) && ! Invoice::query()
            ->whereKey($data['invoice_id'])
            ->where('company_id', $project->company_id)
            ->where('project_id', $project->id)
            ->where('type', 'expense')
            ->where('status', 'open')
            ->exists()) {
            throw ValidationException::withMessages([
                'invoice_id' => 'La factura seleccionada no está abierta o no corresponde al proyecto.',
            ]);
        }
    }

    // Recalcula el total acumulado de una factura a partir de sus movimientos activos.
    protected function refreshInvoiceTotal(?int $invoiceId): void
    {
        if (! $invoiceId) {
            return;
        }

        Invoice::query()
            ->whereKey($invoiceId)
            ->update([
                'total_amount' => Expense::query()
                    ->where('invoice_id', $invoiceId)
                    ->where('status', EntityStatus::Active->value)
                    ->sum('total_amount'),
            ]);
    }

    // Renderiza el detalle HTML de la factura para refrescos parciales desde gastos.
    protected function invoiceDetailHtml(?int $invoiceId): ?string
    {
        if (! $invoiceId) {
            return null;
        }

        $invoice = Invoice::query()
            ->with([
                'project',
                'provider',
                'attachments' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value)->latest(),
            ])
            ->find($invoiceId);

        if (! $invoice) {
            return null;
        }

        $items = $invoice->expenses()
            ->with(['product.subgroup', 'activity.subgroup'])
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->latest('expense_date')
            ->latest('id')
            ->get();

        return view('invoices._detail_modal', [
            'invoice' => $invoice,
            'items' => $items,
            'typeLabel' => 'gastos',
        ])->render();
    }

    // Centraliza el cálculo de total para mantener consistencia si cambian impuestos o descuentos.
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
