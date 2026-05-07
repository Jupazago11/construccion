<?php

namespace App\Policies;

use App\Models\CompanyModule;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class CompanyModulePolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('modules.view');
    }

    public function view(User $user, CompanyModule $companyModule): bool
    {
        return $user->isSuperAdmin()
            && $user->hasPermissionTo('modules.view')
            && $this->isActiveRecord($companyModule);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('modules.manage');
    }

    public function update(User $user, CompanyModule $companyModule): bool
    {
        return $user->isSuperAdmin()
            && $user->hasPermissionTo('modules.manage')
            && $this->isActiveRecord($companyModule);
    }

    public function delete(User $user, CompanyModule $companyModule): bool
    {
        return $user->isSuperAdmin()
            && $user->hasPermissionTo('modules.manage')
            && $this->isActiveRecord($companyModule);
    }

    public function restore(User $user, CompanyModule $companyModule): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('audit.revert');
    }

    public function forceDelete(User $user, CompanyModule $companyModule): bool
    {
        return false;
    }
}
