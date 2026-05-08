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

class ExpenseAttachmentController extends Controller
{
    public function index(Expense $expense): View
    {
        $this->authorize('view', $expense);

        return view('expense-attachments.index', [
            'expense' => $this->loadExpenseAttachments($expense),
            'summary' => $this->summaryData($expense),
        ]);
    }

    public function store(ExpenseAttachmentStoreRequest $request, Expense $expense): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $expense);
        $this->authorize('create', ExpenseAttachment::class);

        $this->guardProjectState($expense);

        $uploadedFiles = $request->file('files', []);

        foreach ($uploadedFiles as $uploadedFile) {
            $path = $uploadedFile->store(
                sprintf('companies/%d/projects/%d/expenses/%d', $expense->company_id, $expense->project_id, $expense->id),
                'r2'
            );

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

    public function download(Expense $expense, ExpenseAttachment $attachment)
    {
        $this->authorize('view', $expense);
        $this->guardAttachmentBelongsToExpense($expense, $attachment);
        $this->authorize('view', $attachment);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name ?: basename($attachment->path)
        );
    }

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

    protected function attachmentResponse(Expense $expense, string $message): JsonResponse
    {
        $loadedExpense = $this->loadExpenseAttachments($expense->fresh());

        return response()->json([
            'summary_html' => view('expense-attachments._summary', [
                'expense' => $loadedExpense,
                'summary' => $this->summaryData($loadedExpense),
            ])->render(),
            'attachments_html' => view('expense-attachments._list', [
                'expense' => $loadedExpense,
            ])->render(),
            'message' => $message,
        ]);
    }

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

    protected function summaryData(Expense $expense): array
    {
        return [
            'attachments' => ExpenseAttachment::query()
                ->where('expense_id', $expense->id)
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->count(),
            'size' => ExpenseAttachment::query()
                ->where('expense_id', $expense->id)
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->sum('size'),
        ];
    }

    protected function guardAttachmentBelongsToExpense(Expense $expense, ExpenseAttachment $attachment): void
    {
        abort_unless($attachment->expense_id === $expense->id, 404);
    }

    protected function guardProjectState(Expense $expense): void
    {
        if (in_array($expense->project?->status, ['planning', 'active'], true)) {
            return;
        }

        abort(422, 'No puedes cargar archivos en un gasto cuyo proyecto está pausado, completado, cancelado o archivado.');
    }
}
