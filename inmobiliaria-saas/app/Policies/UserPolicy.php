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
        if ($user->is($managedUser)) {
            return $this->hasTenantPermission($user, 'users.manage', $managedUser)
                && $this->isActiveRecord($managedUser);
        }

        return $this->canManageUser($user, $managedUser);
    }

    public function delete(User $user, User $managedUser): bool
    {
        return $this->canManageUser($user, $managedUser);
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

    protected function canManageUser(User $user, User $managedUser): bool
    {
        if (! $this->hasTenantPermission($user, 'users.manage', $managedUser)
            || ! $this->isActiveRecord($managedUser)
            || $managedUser->isSuperAdmin()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $managedUser->hasAnyRole(['Operator', 'Viewer']);
    }
}
