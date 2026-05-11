<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $fillable = [
        'company_id',
        'name',
        'asset_type',
        'asset_condition',
        'purchase_value',
        'purchase_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'purchase_value' => 'decimal:2',
            'purchase_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function novelties(): HasMany
    {
        return $this->hasMany(AssetNovelty::class)->latest('novelty_date')->latest('id');
    }
}
