<?php

namespace App\Policies;

use App\Models\ProductSubgroup;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class ProductSubgroupPolicy
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

    public function update(User $user, ProductSubgroup $productSubgroup): bool
    {
        return $this->hasTenantPermission($user, 'catalog.manage', $productSubgroup) && $this->isActiveRecord($productSubgroup);
    }

    public function delete(User $user, ProductSubgroup $productSubgroup): bool
    {
        return $this->hasTenantPermission($user, 'catalog.manage', $productSubgroup) && $this->isActiveRecord($productSubgroup);
    }
}
