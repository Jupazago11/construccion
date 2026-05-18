<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\InvoiceStoreRequest;
use App\Models\CatalogActivity;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceAttachment;
use App\Models\Purchase;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rawType = (string) $request->query('type', '');
        $type = in_array($rawType, ['expense', 'purchase'], true) ? $rawType : 'expense';

        $this->authorize('viewAny', $type === 'purchase' ? Purchase::class : Expense::class);

        $authUser = $request->user();
        $companyId = $authUser->isSuperAdmin() ? null : $authUser->company_id;

        $invoices = Invoice::query()
            ->with(['provider', 'project'])
            ->where('type', $type)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'invoice_date' => optional($inv->invoice_date)->format('d/m/Y'),
                'provider_name' => $inv->provider?->name,
                'project_name' => $inv->project?->name,
                'total_amount' => (float) $inv->total_amount,
                'status' => $inv->status,
                'show_url' => route('invoices.show', $inv),
            ]);

        return response()->json(['invoices' => $invoices]);
    }

    public function create(Request $request)
    {
        $type = in_array($request->string('type')->toString(), ['expense', 'purchase'], true)
            ? $request->string('type')->toString()
            : 'expense';

        $this->authorize('viewAny', $type === 'purchase' ? Purchase::class : Expense::class);

        $authUser = $request->user();
        $projects = $this->availableProjects($authUser);
        $providers = $this->availableProviders($authUser);

        return view('invoices._create_modal', [
            'type' => $type,
            'projects' => $projects,
            'providers' => $providers,
            'storeUrl' => route('invoices.store', [], false),
            'fromIndex' => $request->boolean('from_index'),
        ])->render();
    }

    public function store(InvoiceStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $project = Project::query()->findOrFail($data['project_id']);

        $invoice = Invoice::query()->create([
            'company_id' => $project->company_id,
            'project_id' => $project->id,
            'provider_id' => $data['provider_id'],
            'created_by' => $request->user()->id,
            'type' => $data['type'],
            'item_mode' => 'product',
            'invoice_number' => $data['invoice_number'] ?? null,
            'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
            'description' => $data['description'] ?? null,
            'total_amount' => 0,
            'status' => 'open',
        ]);

        return response()->json([
            'invoice' => $this->serializeInvoice($invoice),
            'redirect_url' => route('invoices.show', $invoice),
            'message' => 'Factura creada correctamente.',
        ]);
    }

    public function show(Request $request, Invoice $invoice)
    {
        $this->authorize('viewAny', $invoice->type === 'purchase' ? Purchase::class : Expense::class);
        $this->guardCompany($request->user(), $invoice);

        $invoice->load([
            'project',
            'provider',
            'attachments' => fn ($query) => $query->where('status', '!=', 'deleted')->latest(),
        ]);

        $isPurchase = $invoice->type === 'purchase';

        $items = $isPurchase
            ? $invoice->purchases()->with(['product.subgroup', 'activity.subgroup'])->where('status', '!=', 'deleted')->orderBy('purchase_date')->orderBy('id')->get()
            : $invoice->expenses()->with(['product.subgroup', 'activity.subgroup'])->where('status', '!=', 'deleted')->orderBy('expense_date')->orderBy('id')->get();

        $products = \App\Models\Product::query()
            ->with('subgroup')
            ->where('company_id', $invoice->company_id)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'subgroup_name' => $p->subgroup?->name])
            ->values();

        $activities = CatalogActivity::query()
            ->with('subgroup')
            ->where('company_id', $invoice->company_id)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->orderBy('name')
            ->get()
            ->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'subgroup_name' => $a->subgroup?->name])
            ->values();

        return view('invoices.show', [
            'invoice' => $invoice,
            'items' => $items,
            'isPurchase' => $isPurchase,
            'typeLabel' => $isPurchase ? 'compras' : 'gastos',
            'backUrl' => $isPurchase ? route('purchases.index') : route('expenses.index'),
            'providers' => $this->availableProviders($request->user()),
            'projects' => $this->availableProjects($request->user()),
            'products' => $products,
            'activities' => $activities,
        ]);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('viewAny', $invoice->type === 'purchase' ? Purchase::class : Expense::class);
        $this->guardCompany($request->user(), $invoice);
        abort_if($invoice->status === EntityStatus::Deleted->value, 403);

        $data = $request->validate([
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_date'   => ['nullable', 'date'],
            'provider_id'    => ['nullable', 'integer', 'exists:providers2,id'],
            'project_id'     => ['nullable', 'integer', 'exists:projects,id'],
            'item_mode'      => ['nullable', 'in:product,activity'],
            'description'    => ['nullable', 'string', 'max:1000'],
        ]);

        $invoice->update($data);

        return response()->json(['message' => 'Factura actualizada correctamente.']);
    }

    public function createItem(Request $request, Invoice $invoice)
    {
        $this->authorize('viewAny', $invoice->type === 'purchase' ? Purchase::class : Expense::class);
        $this->guardCompany($request->user(), $invoice);

        abort_if($invoice->status !== 'open', 403, 'La factura está cerrada.');

        $invoice->load(['project', 'provider']);

        $isPurchase = $invoice->type === 'purchase';

        $products = \App\Models\Product::query()
            ->with('subgroup')
            ->where('company_id', $invoice->company_id)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->orderBy('name')
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'company_id' => $invoice->company_id,
                'subgroup_name' => $product->subgroup?->name,
            ])
            ->values();

        $activities = CatalogActivity::query()
            ->with('subgroup')
            ->where('company_id', $invoice->company_id)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->orderBy('name')
            ->get()
            ->map(fn ($activity) => [
                'id' => $activity->id,
                'name' => $activity->name,
                'company_id' => $invoice->company_id,
                'subgroup_name' => $activity->subgroup?->name,
            ])
            ->values();

        $payload = [
            'transactionType' => $isPurchase ? 'purchase' : 'expense',
            'products' => $products,
            'activities' => $activities,
            'projects' => [[
                'id' => $invoice->project_id,
                'name' => $invoice->project?->name,
                'company_id' => $invoice->company_id,
            ]],
            'providers' => [],
            'invoices' => [],
        ];

        $selected = [
            'project_id' => $invoice->project_id,
            'provider_id' => $invoice->provider_id,
            'invoice_id' => $invoice->id,
            'product_id' => null,
            'activity_id' => null,
            'is_activity' => false,
            'unit_price' => 0,
            'quantity' => null,
        ];

        return view('invoices._item_modal_form', [
            'invoice' => $invoice,
            'isPurchase' => $isPurchase,
            'payload' => $payload,
            'selected' => $selected,
            'action' => $isPurchase
                ? route('purchases.store')
                : route('expenses.store'),
            'method' => 'POST',
        ])->render();
    }

    public function storeAttachment(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('viewAny', $invoice->type === 'purchase' ? Purchase::class : Expense::class);
        $this->guardCompany($request->user(), $invoice);

        $data = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:204800', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf,video/mp4,video/quicktime,video/webm,video/x-msvideo,video/mpeg,video/3gpp,video/3gpp2'],
        ]);

        foreach ($data['files'] as $file) {
            $path = $file->store($this->storageDirectory($invoice), 'r2');

            InvoiceAttachment::query()->create([
                'invoice_id' => $invoice->id,
                'uploaded_by' => $request->user()->id,
                'disk' => 'r2',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'status' => 'active',
            ]);
        }

        return response()->json([
            'attachments_html' => $this->attachmentsHtml($invoice->fresh()),
            'message' => 'Archivos cargados correctamente.',
        ]);
    }

    public function destroyAttachment(Request $request, Invoice $invoice, InvoiceAttachment $attachment): JsonResponse
    {
        $this->authorize('viewAny', $invoice->type === 'purchase' ? Purchase::class : Expense::class);
        $this->guardCompany($request->user(), $invoice);
        abort_unless($attachment->invoice_id === $invoice->id, 404);

        $attachment->update(['status' => 'deleted']);

        return response()->json([
            'attachments_html' => $this->attachmentsHtml($invoice->fresh()),
            'message' => 'Archivo archivado correctamente.',
        ]);
    }

    public function previewAttachment(Request $request, Invoice $invoice, InvoiceAttachment $attachment)
    {
        $this->authorize('viewAny', $invoice->type === 'purchase' ? Purchase::class : Expense::class);
        $this->guardCompany($request->user(), $invoice);
        abort_unless($attachment->invoice_id === $invoice->id, 404);

        $disk = Storage::disk($attachment->disk);
        $path = $this->readableStoragePath($disk, $attachment->path);
        $stream = $disk->readStream($path);

        abort_unless($stream, 404);

        $fileName = $attachment->original_name ?: basename($attachment->path);
        $fallbackName = str_replace('%', '', Str::ascii($fileName)) ?: 'archivo';
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $fileName, $fallbackName);
        $headers = [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => $disposition,
        ];

        if ($attachment->size) {
            $headers['Content-Length'] = (string) $attachment->size;
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, $headers);
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $this->authorize('viewAny', $invoice->type === 'purchase' ? Purchase::class : Expense::class);
        $this->guardCompany($request->user(), $invoice);

        $data = $request->validate([
            'status' => ['required', 'in:open,closed'],
        ]);

        $invoice->update(['status' => $data['status']]);

        $message = $data['status'] === 'closed'
            ? 'Factura cerrada correctamente.'
            : 'Factura abierta correctamente.';

        if ($request->expectsJson()) {
            return response()->json(['id' => $invoice->id, 'message' => $message]);
        }

        return redirect()->back()->with('status', $message);
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        $this->authorize('viewAny', $invoice->type === 'purchase' ? Purchase::class : Expense::class);
        $this->guardCompany($request->user(), $invoice);

        $invoiceType = $invoice->type;
        $transactionModel = $invoiceType === 'purchase' ? Purchase::class : Expense::class;

        $transactionModel::query()
            ->where('invoice_id', $invoice->id)
            ->update(['status' => EntityStatus::Deleted->value]);

        $invoice->attachments()
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->update(['status' => EntityStatus::Deleted->value]);

        $invoice->update([
            'status' => EntityStatus::Deleted->value,
            'total_amount' => 0,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $invoice->id,
                'table_html' => $this->tableHtml($request, $invoiceType),
                'close_modal' => true,
                'message' => 'Factura archivada correctamente.',
            ]);
        }

        $indexRoute = $invoiceType === 'purchase' ? 'purchases.index' : 'expenses.index';

        return redirect()->route($indexRoute)->with('status', 'Factura archivada correctamente.');
    }

    public static function serializeInvoice(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'project_id' => $invoice->project_id,
            'provider_id' => $invoice->provider_id,
            'company_id' => $invoice->company_id,
            'type' => $invoice->type,
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => optional($invoice->invoice_date)->format('Y-m-d') ?: $invoice->invoice_date,
            'description' => $invoice->description,
            'total_amount' => (float) $invoice->total_amount,
            'label' => ($invoice->invoice_number ?: 'Factura sin número').' - '.(optional($invoice->invoice_date)->format('Y-m-d') ?: $invoice->invoice_date),
        ];
    }

    protected function attachmentsHtml(Invoice $invoice): string
    {
        $invoice->load(['attachments' => fn ($query) => $query->where('status', '!=', 'deleted')->latest()]);

        return view('invoices._attachments', compact('invoice'))->render();
    }

    protected function storageDirectory(Invoice $invoice): string
    {
        return collect([
            trim((string) config('filesystems.r2_root_prefix', env('R2_ROOT_PREFIX', 'inmobiliaria-saas')), '/'),
            'companies',
            $invoice->company_id,
            'projects',
            $invoice->project_id,
            'invoices',
            $invoice->id,
            'attachments',
        ])->filter(fn ($segment) => $segment !== null && $segment !== '')->implode('/');
    }

    protected function readableStoragePath($disk, string $path): string
    {
        if ($disk->exists($path)) {
            return $path;
        }

        $prefix = trim((string) config('filesystems.r2_root_prefix', env('R2_ROOT_PREFIX', 'inmobiliaria-saas')), '/');

        if ($prefix === '' || str_starts_with($path, $prefix.'/')) {
            return $path;
        }

        $prefixedPath = $prefix.'/'.$path;

        return $disk->exists($prefixedPath) ? $prefixedPath : $path;
    }

    protected function tableHtml(Request $request, string $type): string
    {
        $authUser = $request->user();
        $companyId = $authUser->isSuperAdmin() ? null : $authUser->company_id;

        if ($type === 'purchase') {
            $purchases = Purchase::query()
                ->with(['company', 'project', 'provider', 'invoice.project', 'invoice.provider', 'product.group', 'product.subgroup', 'activity.group', 'activity.subgroup'])
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
                ->latest('purchase_date')
                ->latest('id')
                ->paginate(10);

            return view('purchases._table_body', compact('purchases'))->render();
        }

        $expenses = Expense::query()
            ->with(['company', 'project', 'provider', 'invoice.project', 'invoice.provider', 'product.group', 'product.subgroup', 'activity.group', 'activity.subgroup'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->latest('expense_date')
            ->latest('id')
            ->paginate(10);

        return view('expenses._table_body', compact('expenses'))->render();
    }

    private function guardCompany(\App\Models\User $user, Invoice $invoice): void
    {
        abort_unless($user->isSuperAdmin() || $invoice->company_id === $user->company_id, 403);
    }

    protected function availableProjects($authUser)
    {
        return \App\Models\Project::query()
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('company_id', $authUser->company_id))
            ->whereNotIn('status', ['cancelled', EntityStatus::Deleted->value])
            ->orderBy('name')
            ->get();
    }

    protected function availableProviders($authUser)
    {
        return \App\Models\Provider2::query()
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('company_id', $authUser->company_id))
            ->where('status', EntityStatus::Active->value)
            ->orderBy('name')
            ->get();
    }
}
