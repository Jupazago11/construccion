<?php

namespace App\Traits;

use App\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait LogsAuditActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName($this->auditLogName())
            ->logOnly($this->auditLoggableAttributes())
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName) => $this->auditDescriptionForEvent($eventName));
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->company_id = $activity->company_id ?: $this->resolveAuditCompanyId();
        $activity->project_id = $activity->project_id ?: $this->resolveAuditProjectId();
        $activity->expires_at = $activity->expires_at ?: now()->addDays((int) config('activitylog.clean_after_days', 60));
    }

    protected function auditLogName(): string
    {
        return $this->getTable();
    }

    protected function auditLoggableAttributes(): array
    {
        return array_values(array_diff($this->getFillable(), $this->auditExcludedAttributes()));
    }

    protected function auditExcludedAttributes(): array
    {
        return property_exists($this, 'auditExcept')
            ? (array) $this->auditExcept
            : [];
    }

    protected function auditDescriptionForEvent(string $eventName): string
    {
        return $eventName;
    }

    protected function resolveAuditCompanyId(): ?int
    {
        return $this->company_id ?? null;
    }

    protected function resolveAuditProjectId(): ?int
    {
        return $this->project_id ?? null;
    }
}
