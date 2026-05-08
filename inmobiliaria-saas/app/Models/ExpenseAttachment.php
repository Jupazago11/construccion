<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ExpenseAttachment extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $fillable = [
        'expense_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected function resolveAuditCompanyId(): ?int
    {
        return $this->expense?->company_id ?? $this->loadMissing('expense')->expense?->company_id;
    }

    protected function resolveAuditProjectId(): ?int
    {
        return $this->expense?->project_id ?? $this->loadMissing('expense')->expense?->project_id;
    }
}
