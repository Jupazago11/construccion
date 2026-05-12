<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetNoveltyType extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $fillable = [
        'company_id',
        'name',
        'adds_value',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'adds_value' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function novelties(): HasMany
    {
        return $this->hasMany(AssetNovelty::class);
    }

    protected function resolveAuditCompanyId(): ?int
    {
        return $this->company_id;
    }
}
