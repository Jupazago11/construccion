<?php

namespace App\Policies;

use App\Models\Provider2Type;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class Provider2TypePolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'providers.view');
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'providers.manage');
    }

    public function update(User $user, Provider2Type $provider2Type): bool
    {
        return $this->hasTenantPermission($user, 'providers.manage', $provider2Type) && $this->isActiveRecord($provider2Type);
    }

    public function delete(User $user, Provider2Type $provider2Type): bool
    {
        return $this->hasTenantPermission($user, 'providers.manage', $provider2Type) && $this->isActiveRecord($provider2Type);
    }
}
