<?php

namespace App\Policies;

use App\Models\Asset2;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class Asset2Policy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'assets.view');
    }

    public function view(User $user, Asset2 $asset2): bool
    {
        return $this->hasTenantPermission($user, 'assets.view', $asset2) && $this->isActiveRecord($asset2);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage');
    }

    public function update(User $user, Asset2 $asset2): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $asset2) && $this->isActiveRecord($asset2);
    }

    public function delete(User $user, Asset2 $asset2): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $asset2) && $this->isActiveRecord($asset2);
    }

    public function restore(User $user, Asset2 $asset2): bool
    {
        return $this->hasTenantPermission($user, 'audit.revert', $asset2);
    }

    public function forceDelete(User $user, Asset2 $asset2): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('assets.force-delete');
    }
}
