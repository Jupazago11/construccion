<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\PurchaseStoreRequest;
use App\Http\Requests\PurchaseUpdateRequest;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Purchase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Purchase::class);

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

        $purchases = Purchase::query()
            ->with(['company', 'project', 'provider', 'invoice.project', 'invoice.provider', 'product.group', 'product.subgroup'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
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
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('quantity', 'like', "%{$search}%")
                        ->orWhereHas('provider', fn ($providerQuery) => $providerQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->where('invoice_number', 'like', "%{$search}%"))
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('purchase_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('purchase_date', '<=', $dateTo))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('purchases.index', [
            'purchases' => $purchases,
            'companies' => $authUser->isSuperAdmin()
                ? Company::query()->where('status', '!=', EntityStatus::Deleted->value)->orderBy('name')->get()
                : collect(),
            'projects' => $this->availableProjectsForTransactions($authUser, $companyId),
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

    public function create(Request $request): View|string|RedirectResponse
    {
        $this->authorize('create', Purchase::class);

        if ($request->ajax()) {
            return view('purchases._modal_form', [
                'purchase' => new Purchase([
                    'project_id' => $request->integer('project_id') ?: null,
                    'purchase_date' => now()->toDateString(),
                    'status' => EntityStatus::Active->value,
                ]),
                'payload' => $this->formPayload($request->user(), null, $request->integer('project_id') ?: null),
                'action' => route('purchases.store'),
                'method' => 'POST',
            ])->render();
        }

        return redirect()->route('purchases.index');
    }

    public function store(PurchaseStoreRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Purchase::class);
        $data = $request->validated();
        $project = Project::query()->with('company')->findOrFail($data['project_id']);

        $this->guardProjectStateForMutations($project);
        $this->guardTransactionCatalog($data, $project);

        $purchase = Purchase::query()->create([
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'provider_id' => $data['provider_id'],
            'invoice_id' => $data['invoice_id'] ?? null,
            'product_id' => $data['product_id'],
            'created_by' => $request->user()->id,
            'purchase_date' => $data['purchase_date'],
            'description' => $data['description'] ?? null,
            'subtotal_amount' => $data['subtotal_amount'],
            'quantity' => $data['quantity'] ?? null,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => round((float) $data['subtotal_amount'], 2),
            'status' => EntityStatus::Active->value,
        ]);

        $this->refreshInvoiceTotal($purchase->invoice_id);
        $purchase->load(['company', 'project', 'provider', 'invoice.project', 'invoice.provider', 'product.group', 'product.subgroup']);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $purchase->id,
                'row_html' => view('purchases._row', compact('purchase'))->render(),
                'table_html' => $this->tableHtml($request),
                'message' => 'Compra creada correctamente.',
            ]);
        }

        return redirect()->route('purchases.index')->with('status', 'Compra creada correctamente.');
    }

    public function edit(Request $request, Purchase $purchase): View|string|RedirectResponse
    {
        $this->authorize('update', $purchase);

        if ($request->ajax()) {
            return view('purchases._modal_form', [
                'purchase' => $purchase,
                'payload' => $this->formPayload($request->user(), $purchase),
                'action' => route('purchases.update', $purchase),
                'method' => 'PATCH',
            ])->render();
        }

        return redirect()->route('purchases.index');
    }

    public function update(PurchaseUpdateRequest $request, Purchase $purchase): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $purchase);
        $data = $request->validated();
        $project = Project::query()->with('company')->findOrFail($data['project_id']);

        $this->guardProjectStateForMutations($project, $purchase);
        $this->guardTransactionCatalog($data, $project);
        $previousInvoiceId = $purchase->invoice_id;

        $purchase->update([
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'provider_id' => $data['provider_id'],
            'invoice_id' => $data['invoice_id'] ?? null,
            'product_id' => $data['product_id'],
            'purchase_date' => $data['purchase_date'],
            'description' => $data['description'] ?? null,
            'subtotal_amount' => $data['subtotal_amount'],
            'quantity' => $data['quantity'] ?? null,
            'total_amount' => round((float) $data['subtotal_amount'], 2),
        ]);

        $this->refreshInvoiceTotal($previousInvoiceId);
        $this->refreshInvoiceTotal($purchase->invoice_id);
        $purchase->load(['company', 'project', 'provider', 'invoice.project', 'invoice.provider', 'product.group', 'product.subgroup']);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $purchase->id,
                'row_html' => view('purchases._row', compact('purchase'))->render(),
                'table_html' => $this->tableHtml($request),
                'invoice_detail_html' => $this->invoiceDetailHtml($request->integer('invoice_detail_id') ?: null),
                'message' => 'Compra actualizada correctamente.',
            ]);
        }

        return redirect()->route('purchases.index')->with('status', 'Compra actualizada correctamente.');
    }

    public function updateStatus(Request $request, Purchase $purchase): JsonResponse
    {
        $this->authorize('update', $purchase);
        $data = $request->validate(['status' => ['required', 'in:active,inactive']]);

        $purchase->update(['status' => $data['status']]);
        $this->refreshInvoiceTotal($purchase->invoice_id);
        $purchase->load(['company', 'project', 'provider', 'invoice.project', 'invoice.provider', 'product.group', 'product.subgroup']);

        return response()->json([
            'id' => $purchase->id,
            'row_html' => view('purchases._row', compact('purchase'))->render(),
            'table_html' => $this->tableHtml($request),
            'invoice_detail_html' => $this->invoiceDetailHtml($request->integer('invoice_detail_id') ?: null),
            'message' => 'Estado de la compra actualizado correctamente.',
        ]);
    }

    public function destroy(Request $request, Purchase $purchase): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $purchase);
        $purchase->update(['status' => EntityStatus::Deleted->value]);
        $this->refreshInvoiceTotal($purchase->invoice_id);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $purchase->id,
                'table_html' => $this->tableHtml($request),
                'invoice_detail_html' => $this->invoiceDetailHtml($request->integer('invoice_detail_id') ?: null),
                'message' => 'Compra archivada correctamente.',
            ]);
        }

        return redirect()->route('purchases.index')->with('status', 'Compra archivada correctamente.');
    }

    protected function formPayload($authUser, ?Purchase $purchase = null, ?int $preferredProjectId = null): array
    {
        $currentProjectId = $purchase?->project_id ?? $preferredProjectId;
        $projectsCollection = $this->availableProjectsForTransactions($authUser, null, $currentProjectId);

        if ($preferredProjectId) {
            $projectsCollection = $projectsCollection->where('id', $preferredProjectId)->values();
        }

        $projectsCollection->load('company');

        return [
            'projects' => $projectsCollection->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'company_id' => $project->company_id,
                'company_name' => $project->company?->name,
                'status' => $project->status,
            ])->values()->all(),
            'providers' => $this->availableProviders($authUser)->map(fn ($provider) => [
                'id' => $provider->id,
                'name' => $provider->name,
                'company_id' => $provider->company_id,
            ])->values()->all(),
            'products' => $this->availableProducts($authUser)->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'company_id' => $product->company_id,
                'subgroup_name' => $product->subgroup?->name,
            ])->values()->all(),
            'invoices' => $this->availableInvoices($authUser, 'purchase')->map(fn ($invoice) => InvoiceController::serializeInvoice($invoice))->values()->all(),
            'invoiceStoreUrl' => route('invoices.store', [], false),
            'transactionType' => 'purchase',
        ];
    }

    protected function tableHtml(Request $request): string
    {
        $authUser = $request->user();
        $companyId = $authUser->isSuperAdmin() ? null : $authUser->company_id;

        $purchases = Purchase::query()
            ->with(['company', 'project', 'provider', 'invoice.project', 'invoice.provider', 'product.group', 'product.subgroup'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(10);

        return view('purchases._table_body', compact('purchases'))->render();
    }

    protected function availableProjectsForTransactions($authUser, ?int $companyId = null, ?int $currentProjectId = null)
    {
        return Project::query()
            ->when($authUser->isSuperAdmin(), function ($query) use ($companyId) {
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
            }, fn ($query) => $query->where('company_id', $authUser->company_id))
            ->where(function ($query) use ($currentProjectId) {
                $query->whereNotIn('status', ['cancelled', EntityStatus::Deleted->value]);

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
            ->where('status', EntityStatus::Active->value)
            ->orderBy('name')
            ->get();
    }

    protected function availableProducts($authUser)
    {
        return Product::query()
            ->with(['group', 'subgroup'])
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('company_id', $authUser->company_id))
            ->where('status', EntityStatus::Active->value)
            ->orderBy('name')
            ->get();
    }

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

    protected function guardProjectStateForMutations(Project $project, ?Purchase $purchase = null): void
    {
        if (in_array($project->status, ['planning', 'active'], true)) {
            return;
        }

        if ($purchase && $purchase->project_id === $project->id) {
            throw ValidationException::withMessages(['project_id' => 'No puedes modificar esta compra porque el proyecto no permite movimientos.']);
        }

        throw ValidationException::withMessages(['project_id' => 'No puedes registrar compras en un proyecto pausado, completado, cancelado o archivado.']);
    }

    protected function guardTransactionCatalog(array $data, Project $project): void
    {
        if (! $project->company->providers()->whereKey($data['provider_id'])->where('status', EntityStatus::Active->value)->exists()) {
            throw ValidationException::withMessages(['provider_id' => 'El proveedor seleccionado no está activo o no pertenece a la empresa del proyecto.']);
        }

        if (! Product::query()->whereKey($data['product_id'])->where('company_id', $project->company_id)->where('status', EntityStatus::Active->value)->exists()) {
            throw ValidationException::withMessages(['product_id' => 'El producto seleccionado no está activo o no pertenece a la empresa del proyecto.']);
        }

        if (! empty($data['invoice_id']) && ! Invoice::query()
            ->whereKey($data['invoice_id'])
            ->where('company_id', $project->company_id)
            ->where('project_id', $project->id)
            ->where('provider_id', $data['provider_id'])
            ->where('type', 'purchase')
            ->where('status', 'open')
            ->exists()) {
            throw ValidationException::withMessages([
                'invoice_id' => 'La factura seleccionada no está abierta o no corresponde al proyecto y proveedor.',
            ]);
        }
    }

    protected function refreshInvoiceTotal(?int $invoiceId): void
    {
        if (! $invoiceId) {
            return;
        }

        Invoice::query()
            ->whereKey($invoiceId)
            ->update([
                'total_amount' => Purchase::query()
                    ->where('invoice_id', $invoiceId)
                    ->where('status', EntityStatus::Active->value)
                    ->sum('total_amount'),
            ]);
    }

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

        $items = $invoice->purchases()
            ->with(['product.subgroup'])
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->latest('purchase_date')
            ->latest('id')
            ->get();

        return view('invoices._detail_modal', [
            'invoice' => $invoice,
            'items' => $items,
            'typeLabel' => 'compras',
        ])->render();
    }
}
