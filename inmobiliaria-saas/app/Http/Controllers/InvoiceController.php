<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\InvoiceStoreRequest;
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
            'invoice_number' => $data['invoice_number'] ?? null,
            'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
            'description' => $data['description'] ?? null,
            'total_amount' => 0,
            'status' => 'open',
        ]);

        return response()->json([
            'invoice' => $this->serializeInvoice($invoice),
            'message' => 'Factura creada correctamente.',
        ]);
    }

    public function show(Request $request, Invoice $invoice)
    {
        $this->authorize('viewAny', $invoice->type === 'purchase' ? Purchase::class : Expense::class);

        $invoice->load([
            'project',
            'provider',
            'attachments' => fn ($query) => $query->where('status', '!=', 'deleted')->latest(),
        ]);

        if ($invoice->type === 'purchase') {
            $items = $invoice->purchases()
                ->with(['product.subgroup'])
                ->where('status', '!=', 'deleted')
                ->latest('purchase_date')
                ->latest('id')
                ->get();

            return view('invoices._detail_modal', [
                'invoice' => $invoice,
                'items' => $items,
                'typeLabel' => 'compras',
            ])->render();
        }

        $items = $invoice->expenses()
            ->with(['product.subgroup'])
            ->where('status', '!=', 'deleted')
            ->latest('expense_date')
            ->latest('id')
            ->get();

        return view('invoices._detail_modal', [
            'invoice' => $invoice,
            'items' => $items,
            'typeLabel' => 'gastos',
        ])->render();
    }

    public function storeAttachment(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('viewAny', $invoice->type === 'purchase' ? Purchase::class : Expense::class);

        $data = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:20480'],
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

    public function destroyAttachment(Invoice $invoice, InvoiceAttachment $attachment): JsonResponse
    {
        $this->authorize('viewAny', $invoice->type === 'purchase' ? Purchase::class : Expense::class);
        abort_unless($attachment->invoice_id === $invoice->id, 404);

        $attachment->update(['status' => 'deleted']);

        return response()->json([
            'attachments_html' => $this->attachmentsHtml($invoice->fresh()),
            'message' => 'Archivo archivado correctamente.',
        ]);
    }

    public function previewAttachment(Invoice $invoice, InvoiceAttachment $attachment)
    {
        $this->authorize('viewAny', $invoice->type === 'purchase' ? Purchase::class : Expense::class);
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

    public function updateStatus(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('viewAny', $invoice->type === 'purchase' ? Purchase::class : Expense::class);

        $data = $request->validate([
            'status' => ['required', 'in:open,closed'],
        ]);

        $invoice->update(['status' => $data['status']]);

        return response()->json([
            'id' => $invoice->id,
            'message' => $data['status'] === 'closed'
                ? 'Factura cerrada correctamente.'
                : 'Factura abierta correctamente.',
        ]);
    }

    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('viewAny', $invoice->type === 'purchase' ? Purchase::class : Expense::class);

        $transactionModel = $invoice->type === 'purchase' ? Purchase::class : Expense::class;

        $transactionModel::query()
            ->where('invoice_id', $invoice->id)
            ->update(['invoice_id' => null]);

        $invoice->attachments()
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->update(['status' => EntityStatus::Deleted->value]);

        $invoice->update([
            'status' => EntityStatus::Deleted->value,
            'total_amount' => 0,
        ]);

        return response()->json([
            'id' => $invoice->id,
            'table_html' => $this->tableHtml($request, $invoice->type),
            'close_modal' => true,
            'message' => 'Factura archivada correctamente.',
        ]);
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
                ->with(['company', 'project', 'provider', 'invoice.project', 'invoice.provider', 'product.group', 'product.subgroup'])
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
                ->latest('purchase_date')
                ->latest('id')
                ->paginate(10);

            return view('purchases._table_body', compact('purchases'))->render();
        }

        $expenses = Expense::query()
            ->with(['company', 'project', 'provider', 'invoice.project', 'invoice.provider', 'product.group', 'product.subgroup'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->latest('expense_date')
            ->latest('id')
            ->paginate(10);

        return view('expenses._table_body', compact('expenses'))->render();
    }
}
