<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Auxiliary extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $fillable = [
        'subcategory_id',
        'name',
        'description',
        'sort_order',
        'status',
    ];

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    protected function resolveAuditCompanyId(): ?int
    {
        return $this->subcategory?->category?->project?->company_id
            ?? $this->loadMissing('subcategory.category.project')->subcategory?->category?->project?->company_id;
    }

    protected function resolveAuditProjectId(): ?int
    {
        return $this->subcategory?->category?->project_id
            ?? $this->loadMissing('subcategory.category')->subcategory?->category?->project_id;
    }
}
