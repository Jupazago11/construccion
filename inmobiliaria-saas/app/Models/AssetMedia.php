<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMedia extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $table = 'asset_media';

    protected $fillable = [
        'asset_id',
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

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
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
        return $this->asset?->company_id ?? $this->loadMissing('asset')->asset?->company_id;
    }
}
