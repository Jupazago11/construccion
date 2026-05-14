<?php

namespace App\Policies;

use App\Models\ProductGroup;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class ProductGroupPolicy
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

    public function update(User $user, ProductGroup $productGroup): bool
    {
        return $this->hasTenantPermission($user, 'catalog.manage', $productGroup) && $this->isActiveRecord($productGroup);
    }

    public function delete(User $user, ProductGroup $productGroup): bool
    {
        return $this->hasTenantPermission($user, 'catalog.manage', $productGroup) && $this->isActiveRecord($productGroup);
    }
}
