<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider2Type extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $table = 'provider2_types';

    protected $fillable = [
        'company_id',
        'name',
        'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider2::class, 'provider2_type_id');
    }

    protected function resolveAuditCompanyId(): ?int
    {
        return $this->company_id;
    }
}
