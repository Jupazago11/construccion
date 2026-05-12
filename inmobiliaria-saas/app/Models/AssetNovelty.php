<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetNovelty extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $fillable = [
        'asset_id',
        'created_by',
        'asset_novelty_type_id',
        'cost',
        'description',
        'asset_status',
        'novelty_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'novelty_date' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AssetNoveltyType::class, 'asset_novelty_type_id');
    }

    protected function resolveAuditCompanyId(): ?int
    {
        return $this->asset?->company_id ?? $this->loadMissing('asset')->asset?->company_id;
    }
}
