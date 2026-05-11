<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class AssetPolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'assets.view');
    }

    public function view(User $user, Asset $asset): bool
    {
        return $this->hasTenantPermission($user, 'assets.view', $asset) && $this->isActiveRecord($asset);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage');
    }

    public function update(User $user, Asset $asset): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $asset) && $this->isActiveRecord($asset);
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $asset) && $this->isActiveRecord($asset);
    }

    public function restore(User $user, Asset $asset): bool
    {
        return $this->hasTenantPermission($user, 'audit.revert', $asset);
    }

    public function forceDelete(User $user, Asset $asset): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('assets.force-delete');
    }
}
