<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderType extends Model
{
    use HasFactory;
    use LogsAuditActivity;

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
        return $this->hasMany(Provider::class);
    }

    protected function resolveAuditCompanyId(): ?int
    {
        return $this->company_id;
    }
}
