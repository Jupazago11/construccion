<?php

namespace App\Policies\Concerns;

use App\Enums\EntityStatus;
use App\Models\Asset;
use App\Models\AssetMedia;
use App\Models\AssetNovelty;
use App\Models\AssetNoveltyType;
use App\Models\AssetType;
use App\Models\Auxiliary;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subcategory;
use App\Models\User;

trait ResolvesTenantOwnership
{
    protected function hasTenantPermission(User $user, string $permission, mixed $record = null): bool
    {
        if (! $user->hasPermissionTo($permission)) {
            return false;
        }

        if ($record === null) {
            return $user->isSuperAdmin() || $user->company_id !== null;
        }

        return $user->isSuperAdmin() || $this->resolveCompanyId($record) === $user->company_id;
    }

    protected function canManageOwnCompany(User $user, Company $company): bool
    {
        return $user->isSuperAdmin()
            || ($user->hasPermissionTo('companies.manage') && $user->company_id === $company->id);
    }

    protected function resolveCompanyId(mixed $record): ?int
    {
        return match (true) {
            $record instanceof Company => $record->id,
            $record instanceof CompanyModule => $record->company_id,
            $record instanceof Project => $record->company_id,
            $record instanceof Provider => $record->company_id,
            $record instanceof Asset => $record->company_id,
            $record instanceof AssetMedia => $record->loadMissing('asset')->asset?->company_id,
            $record instanceof AssetType => $record->company_id,
            $record instanceof AssetNoveltyType => $record->company_id,
            $record instanceof Expense => $record->company_id,
            $record instanceof User => $record->company_id,
            $record instanceof AssetNovelty => $record->loadMissing('asset')->asset?->company_id,
            $record instanceof Category => $record->loadMissing('project')->project?->company_id,
            $record instanceof Subcategory => $record->loadMissing('category.project')->category?->project?->company_id,
            $record instanceof Auxiliary => $record->loadMissing('subcategory.category.project')->subcategory?->category?->project?->company_id,
            $record instanceof ExpenseAttachment => $record->loadMissing('expense')->expense?->company_id,
            default => $record?->company_id,
        };
    }

    protected function isActiveRecord(mixed $record): bool
    {
        $status = $record?->status;

        return $status === null || $status !== EntityStatus::Deleted->value;
    }
}
