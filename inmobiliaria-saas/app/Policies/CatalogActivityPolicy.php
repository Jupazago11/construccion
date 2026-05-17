<?php

namespace App\Policies;

use App\Models\CatalogActivity;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class CatalogActivityPolicy
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

    public function update(User $user, CatalogActivity $activity): bool
    {
        return $this->hasTenantPermission($user, 'catalog.manage', $activity) && $this->isActiveRecord($activity);
    }

    public function delete(User $user, CatalogActivity $activity): bool
    {
        return $this->hasTenantPermission($user, 'catalog.manage', $activity) && $this->isActiveRecord($activity);
    }
}
