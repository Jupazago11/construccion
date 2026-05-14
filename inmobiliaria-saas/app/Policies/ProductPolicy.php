<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class ProductPolicy
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

    public function update(User $user, Product $product): bool
    {
        return $this->hasTenantPermission($user, 'catalog.manage', $product) && $this->isActiveRecord($product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->hasTenantPermission($user, 'catalog.manage', $product) && $this->isActiveRecord($product);
    }
}
