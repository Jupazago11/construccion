<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class PurchasePolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'purchases.view');
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'purchases.manage');
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $this->hasTenantPermission($user, 'purchases.manage', $purchase) && $this->isActiveRecord($purchase);
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return $this->hasTenantPermission($user, 'purchases.manage', $purchase) && $this->isActiveRecord($purchase);
    }
}
