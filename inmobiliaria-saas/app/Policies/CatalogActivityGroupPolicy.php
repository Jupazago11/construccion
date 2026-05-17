<?php

namespace App\Policies;

use App\Models\CatalogActivityGroup;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class CatalogActivityGroupPolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'catalog.view');
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'catalog.manage');
    }

    public function update(User $user, CatalogActivityGroup $activityGroup): bool
    {
        return $this->hasTenantPermission($user, 'catalog.manage', $activityGroup) && $this->isActiveRecord($activityGroup);
    }

    public function delete(User $user, CatalogActivityGroup $activityGroup): bool
    {
        return $this->hasTenantPermission($user, 'catalog.manage', $activityGroup) && $this->isActiveRecord($activityGroup);
    }
}
