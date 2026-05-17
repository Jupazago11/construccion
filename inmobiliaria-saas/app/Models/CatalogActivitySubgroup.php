<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogActivitySubgroup extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $table = 'activity_subgroups';

    protected $fillable = [
        'company_id',
        'activity_group_id',
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

    public function activities(): HasMany
    {
        return $this->hasMany(CatalogActivity::class, 'activity_subgroup_id');
    }
}
