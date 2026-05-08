<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $fillable = [
        'key',
        'name',
        'description',
        'status',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(CompanyModule::class);
    }
}
