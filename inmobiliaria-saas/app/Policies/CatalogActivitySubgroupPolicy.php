<?php

namespace App\Policies;

use App\Models\CatalogActivitySubgroup;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class CatalogActivitySubgroupPolicy
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

    public function update(User $user, CatalogActivitySubgroup $activitySubgroup): bool
    {
        return $this->hasTenantPermission($user, 'catalog.manage', $activitySubgroup) && $this->isActiveRecord($activitySubgroup);
    }

    public function delete(User $user, CatalogActivitySubgroup $activitySubgroup): bool
    {
        return $this->hasTenantPermission($user, 'catalog.manage', $activitySubgroup) && $this->isActiveRecord($activitySubgroup);
    }
}
