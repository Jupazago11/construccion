<?php

namespace App\Models;

use App\Enums\EntityStatus;
use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $fillable = [
        'name',
        'legal_name',
        'nit',
        'email',
        'phone',
        'logo_path',
        'primary_color',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CompanyModule::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }

    public function isActive(): bool
    {
        return $this->status === EntityStatus::Active->value;
    }
}
