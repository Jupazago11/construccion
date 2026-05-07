<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class UserPolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'users.view');
    }

    public function view(User $user, User $managedUser): bool
    {
        return $this->hasTenantPermission($user, 'users.view', $managedUser) && $this->isActiveRecord($managedUser);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'users.manage');
    }

    public function update(User $user, User $managedUser): bool
    {
        return $this->hasTenantPermission($user, 'users.manage', $managedUser)
            && ! $managedUser->isSuperAdmin()
            && $this->isActiveRecord($managedUser);
    }

    public function delete(User $user, User $managedUser): bool
    {
        return $this->hasTenantPermission($user, 'users.manage', $managedUser)
            && ! $managedUser->isSuperAdmin()
            && $this->isActiveRecord($managedUser);
    }

    public function restore(User $user, User $managedUser): bool
    {
        return $this->hasTenantPermission($user, 'audit.revert', $managedUser) && ! $managedUser->isSuperAdmin();
    }

    public function forceDelete(User $user, User $managedUser): bool
    {
        return $user->isSuperAdmin()
            && $user->hasPermissionTo('users.manage')
            && ! $managedUser->isSuperAdmin();
    }
}
