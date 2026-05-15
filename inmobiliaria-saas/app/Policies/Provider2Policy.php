<?php

namespace App\Policies;

use App\Models\Provider2;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class Provider2Policy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'providers.view');
    }

    public function view(User $user, Provider2 $provider2): bool
    {
        return $this->hasTenantPermission($user, 'providers.view', $provider2) && $this->isActiveRecord($provider2);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'providers.manage');
    }

    public function update(User $user, Provider2 $provider2): bool
    {
        return $this->hasTenantPermission($user, 'providers.manage', $provider2) && $this->isActiveRecord($provider2);
    }

    public function delete(User $user, Provider2 $provider2): bool
    {
        return $this->hasTenantPermission($user, 'providers.manage', $provider2) && $this->isActiveRecord($provider2);
    }

    public function restore(User $user, Provider2 $provider2): bool
    {
        return $this->hasTenantPermission($user, 'audit.revert', $provider2);
    }

    public function forceDelete(User $user, Provider2 $provider2): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('providers.force-delete');
    }
}
