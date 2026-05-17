<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset2Media extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $table = 'asset2_media';

    protected $fillable = [
        'asset2_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'media_type',
        'size',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function asset2(): BelongsTo
    {
        return $this->belongsTo(Asset2::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }

    protected function resolveAuditCompanyId(): ?int
    {
        return $this->asset2?->company_id ?? $this->loadMissing('asset2')->asset2?->company_id;
    }
}
