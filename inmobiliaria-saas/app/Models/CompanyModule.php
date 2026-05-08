<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class CompanyModule extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $fillable = [
        'company_id',
        'module_id',
        'status',
        'enabled_at',
        'disabled_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
