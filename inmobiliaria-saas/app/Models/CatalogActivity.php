<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogActivity extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $table = 'activities';

    protected $fillable = [
        'company_id',
        'activity_group_id',
        'activity_subgroup_id',
        'name',
        'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CatalogActivityGroup::class, 'activity_group_id');
    }

    public function subgroup(): BelongsTo
    {
        return $this->belongsTo(CatalogActivitySubgroup::class, 'activity_subgroup_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'activity_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'activity_id');
    }
}
