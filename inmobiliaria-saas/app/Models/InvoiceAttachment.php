<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceAttachment extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $fillable = [
        'invoice_id',
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
        return ['size' => 'integer'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected function resolveAuditCompanyId(): ?int
    {
        return $this->invoice?->company_id ?? $this->loadMissing('invoice')->invoice?->company_id;
    }

    protected function resolveAuditProjectId(): ?int
    {
        return $this->invoice?->project_id ?? $this->loadMissing('invoice')->invoice?->project_id;
    }
}
