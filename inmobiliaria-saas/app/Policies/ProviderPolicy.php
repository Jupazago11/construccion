<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class ProviderPolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'providers.view');
    }

    public function view(User $user, Provider $provider): bool
    {
        return $this->hasTenantPermission($user, 'providers.view', $provider) && $this->isActiveRecord($provider);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'providers.manage');
    }

    public function update(User $user, Provider $provider): bool
    {
        return $this->hasTenantPermission($user, 'providers.manage', $provider) && $this->isActiveRecord($provider);
    }

    public function delete(User $user, Provider $provider): bool
    {
        return $this->hasTenantPermission($user, 'providers.manage', $provider) && $this->isActiveRecord($provider);
    }

    public function restore(User $user, Provider $provider): bool
    {
        return $this->hasTenantPermission($user, 'audit.revert', $provider);
    }

    public function forceDelete(User $user, Provider $provider): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('providers.force-delete');
    }
}
