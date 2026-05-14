<?php

namespace App\Policies;

use App\Models\ProviderType;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class ProviderTypePolicy
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

    public function update(User $user, ProviderType $providerType): bool
    {
        return $this->hasTenantPermission($user, 'providers.manage', $providerType) && $this->isActiveRecord($providerType);
    }

    public function delete(User $user, ProviderType $providerType): bool
    {
        return $this->hasTenantPermission($user, 'providers.manage', $providerType) && $this->isActiveRecord($providerType);
    }
}
