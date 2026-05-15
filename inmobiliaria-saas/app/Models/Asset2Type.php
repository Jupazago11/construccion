<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset2Type extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $table = 'asset2_types';

    protected $fillable = [
        'company_id',
        'name',
        'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset2::class, 'asset2_type_id');
    }

    protected function resolveAuditCompanyId(): ?int
    {
        return $this->company_id;
    }
}
