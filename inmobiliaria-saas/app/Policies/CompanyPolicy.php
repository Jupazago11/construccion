<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class CompanyPolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Company $company): bool
    {
        return $user->isSuperAdmin() && $this->isActiveRecord($company);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('companies.manage');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->isSuperAdmin() && $this->isActiveRecord($company);
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->isSuperAdmin()
            && $user->hasPermissionTo('companies.manage')
            && $this->isActiveRecord($company);
    }

    public function restore(User $user, Company $company): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('audit.revert');
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('companies.force-delete');
    }
}
