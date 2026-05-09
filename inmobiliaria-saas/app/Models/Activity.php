<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected function casts(): array
    {
        return [
            'attribute_changes' => 'collection',
            'properties' => 'collection',
            'reverted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Activity $activity): void {
            $activity->expires_at ??= now()->addDays((int) config('activitylog.clean_after_days', 60));
            $activity->company_id ??= $activity->resolveCompanyId();
            $activity->project_id ??= $activity->resolveProjectId();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reverter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reverted_by');
    }

    protected function descriptionLabel(): Attribute
    {
        return Attribute::get(fn () => ucfirst(str_replace('_', ' ', (string) $this->description)));
    }

    protected function resolveCompanyId(): ?int
    {
        return $this->resolveCompanyIdFromModel($this->subject) ?? $this->resolveCompanyIdFromModel($this->causer);
    }

    protected function resolveProjectId(): ?int
    {
        return $this->resolveProjectIdFromModel($this->subject) ?? $this->resolveProjectIdFromModel($this->causer);
    }

    protected function resolveCompanyIdFromModel(mixed $model): ?int
    {
        return match (true) {
            $model instanceof Company => $model->id,
            $model instanceof CompanyModule => $model->company_id,
            $model instanceof Project => $model->company_id,
            $model instanceof Provider => $model->company_id,
            $model instanceof Expense => $model->company_id,
            $model instanceof User => $model->company_id,
            $model instanceof Category => $model->project?->company_id ?? $model->loadMissing('project')->project?->company_id,
            $model instanceof Subcategory => $model->category?->project?->company_id ?? $model->loadMissing('category.project')->category?->project?->company_id,
            $model instanceof Auxiliary => $model->subcategory?->category?->project?->company_id ?? $model->loadMissing('subcategory.category.project')->subcategory?->category?->project?->company_id,
            $model instanceof ExpenseAttachment => $model->expense?->company_id ?? $model->loadMissing('expense')->expense?->company_id,
            default => $model?->company_id,
        };
    }

    protected function resolveProjectIdFromModel(mixed $model): ?int
    {
        return match (true) {
            $model instanceof Project => $model->id,
            $model instanceof Category => $model->project_id,
            $model instanceof Expense => $model->project_id,
            $model instanceof Subcategory => $model->category?->project_id ?? $model->loadMissing('category')->category?->project_id,
            $model instanceof Auxiliary => $model->subcategory?->category?->project_id ?? $model->loadMissing('subcategory.category')->subcategory?->category?->project_id,
            $model instanceof ExpenseAttachment => $model->expense?->project_id ?? $model->loadMissing('expense')->expense?->project_id,
            default => $model?->project_id,
        };
    }
}
