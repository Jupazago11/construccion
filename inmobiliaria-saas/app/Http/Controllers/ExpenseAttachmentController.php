<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\ExpenseAttachmentStoreRequest;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class ExpenseAttachmentController extends Controller
{
    // Muestra el repositorio de adjuntos del gasto con sus relaciones principales cargadas.
    public function index(Expense $expense): View
    {
        $this->authorize('view', $expense);

        return view('expense-attachments.index', [
            'expense' => $this->loadExpenseAttachments($expense),
        ]);
    }

    // Carga uno o varios archivos al gasto y devuelve la lista actualizada de adjuntos.
    public function store(ExpenseAttachmentStoreRequest $request, Expense $expense): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $expense);
        $this->authorize('create', ExpenseAttachment::class);

        $this->guardProjectState($expense);

        $uploadedFiles = $request->file('files', []);

        foreach ($uploadedFiles as $uploadedFile) {
            $path = $uploadedFile->store(
                $this->storageDirectory($expense),
                'r2'
            );

            abort_unless($path, 500, 'No fue posible cargar el archivo.');

            ExpenseAttachment::query()->create([
                'expense_id' => $expense->id,
                'uploaded_by' => $request->user()->id,
                'disk' => 'r2',
                'path' => $path,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getMimeType(),
                'size' => $uploadedFile->getSize(),
                'status' => EntityStatus::Active->value,
            ]);
        }

        activity('attachments')
            ->causedBy($request->user())
            ->performedOn($expense)
            ->event('upload')
            ->withProperties([
                'files_count' => count($uploadedFiles),
                'files' => collect($uploadedFiles)->map(fn ($file) => $file->getClientOriginalName())->values()->all(),
            ])
            ->log('upload');

        return $this->attachmentResponse($expense, 'Archivos cargados correctamente.');
    }

    // Descarga un adjunto validando que pertenezca al gasto solicitado.
    public function download(Expense $expense, ExpenseAttachment $attachment)
    {
        $this->authorize('view', $expense);
        $this->guardAttachmentBelongsToExpense($expense, $attachment);
        $this->authorize('view', $attachment);

        return Storage::disk($attachment->disk)->download(
            $this->readableStoragePath(Storage::disk($attachment->disk), $attachment->path),
            $attachment->original_name ?: basename($attachment->path)
        );
    }

    // Sirve una vista previa inline del adjunto cuando el navegador puede renderizarlo.
    public function preview(Expense $expense, ExpenseAttachment $attachment)
    {
        $this->authorize('view', $expense);
        $this->guardAttachmentBelongsToExpense($expense, $attachment);
        $this->authorize('view', $attachment);

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

    // Archiva un adjunto del gasto y recompone el listado parcial renderizado.
    public function destroy(Expense $expense, ExpenseAttachment $attachment): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $expense);
        $this->guardAttachmentBelongsToExpense($expense, $attachment);
        $this->authorize('delete', $attachment);

        $attachment->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        activity('attachments')
            ->causedBy(auth()->user())
            ->performedOn($expense)
            ->event('delete')
            ->withProperties([
                'attachment_id' => $attachment->id,
                'original_name' => $attachment->original_name,
            ])
            ->log('delete');

        return $this->attachmentResponse($expense, 'Archivo archivado correctamente.');
    }

    // Construye la respuesta AJAX estándar con el HTML actualizado del listado de adjuntos.
    protected function attachmentResponse(Expense $expense, string $message): JsonResponse
    {
        $loadedExpense = $this->loadExpenseAttachments($expense->fresh());

        return response()->json([
            'attachments_html' => view('expense-attachments._list', [
                'expense' => $loadedExpense,
            ])->render(),
            'message' => $message,
        ]);
    }

    // Carga las relaciones necesarias para la vista de adjuntos del gasto.
    protected function loadExpenseAttachments(Expense $expense): Expense
    {
        return $expense->load([
            'company',
            'project',
            'category',
            'subcategory',
            'auxiliary',
            'provider',
            'attachments' => fn ($query) => $query
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->with('uploader')
                ->latest(),
        ]);
    }

    // Impide operar sobre archivos que pertenecen a otro gasto.
    protected function guardAttachmentBelongsToExpense(Expense $expense, ExpenseAttachment $attachment): void
    {
        abort_unless($attachment->expense_id === $expense->id, 404);
    }

    // Define la carpeta de almacenamiento de adjuntos segmentada por empresa, proyecto y gasto.
    protected function storageDirectory(Expense $expense): string
    {
        return collect([
            trim((string) config('filesystems.r2_root_prefix', env('R2_ROOT_PREFIX', 'inmobiliaria-saas')), '/'),
            'companies',
            $expense->company_id,
            'projects',
            $expense->project_id,
            'expenses',
            $expense->id,
            'attachments',
        ])->filter(fn ($segment) => $segment !== null && $segment !== '')->implode('/');
    }

    // Normaliza rutas con y sin prefijo para mantener legibilidad sobre R2.
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

    // Bloquea la carga de adjuntos cuando el proyecto del gasto ya no está en ejecución editable.
    protected function guardProjectState(Expense $expense): void
    {
        if (in_array($expense->project?->status, ['planning', 'active'], true)) {
            return;
        }

        abort(422, 'No puedes cargar archivos en un gasto cuyo proyecto está pausado, completado, cancelado o archivado.');
    }
}
