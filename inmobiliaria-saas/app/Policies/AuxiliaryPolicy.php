<?php

namespace App\Policies;

use App\Models\Auxiliary;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class AuxiliaryPolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'categories.view');
    }

    public function view(User $user, Auxiliary $auxiliary): bool
    {
        return $this->hasTenantPermission($user, 'categories.view', $auxiliary) && $this->isActiveRecord($auxiliary);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'categories.manage');
    }

    public function update(User $user, Auxiliary $auxiliary): bool
    {
        return $this->hasTenantPermission($user, 'categories.manage', $auxiliary) && $this->isActiveRecord($auxiliary);
    }

    public function delete(User $user, Auxiliary $auxiliary): bool
    {
        return $this->hasTenantPermission($user, 'categories.manage', $auxiliary) && $this->isActiveRecord($auxiliary);
    }

    public function restore(User $user, Auxiliary $auxiliary): bool
    {
        return $this->hasTenantPermission($user, 'audit.revert', $auxiliary);
    }

    public function forceDelete(User $user, Auxiliary $auxiliary): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('categories.force-delete');
    }
}
