<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset2 extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $table = 'assets2';

    protected $fillable = [
        'company_id',
        'name',
        'asset2_type',
        'asset2_type_id',
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

    public function type(): BelongsTo
    {
        return $this->belongsTo(Asset2Type::class, 'asset2_type_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Asset2Media::class, 'asset2_id');
    }

    public function novelties(): HasMany
    {
        return $this->hasMany(Asset2Novelty::class, 'asset2_id');
    }
}
