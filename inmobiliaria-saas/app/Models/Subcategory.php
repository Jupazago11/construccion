<?php

namespace App\Models;

use App\Traits\LogsAuditActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    use HasFactory;
    use LogsAuditActivity;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'sort_order',
        'status',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function auxiliaries(): HasMany
    {
        return $this->hasMany(Auxiliary::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    protected function resolveAuditCompanyId(): ?int
    {
        return $this->category?->project?->company_id ?? $this->loadMissing('category.project')->category?->project?->company_id;
    }

    protected function resolveAuditProjectId(): ?int
    {
        return $this->category?->project_id ?? $this->loadMissing('category')->category?->project_id;
    }
}
