<?php

namespace App\Policies;

use App\Models\AssetNovelty;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class AssetNoveltyPolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'assets.view');
    }

    public function view(User $user, AssetNovelty $assetNovelty): bool
    {
        return $this->hasTenantPermission($user, 'assets.view', $assetNovelty) && $this->isActiveRecord($assetNovelty);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage');
    }

    public function update(User $user, AssetNovelty $assetNovelty): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $assetNovelty) && $this->isActiveRecord($assetNovelty);
    }

    public function delete(User $user, AssetNovelty $assetNovelty): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $assetNovelty) && $this->isActiveRecord($assetNovelty);
    }

    public function restore(User $user, AssetNovelty $assetNovelty): bool
    {
        return $this->hasTenantPermission($user, 'audit.revert', $assetNovelty);
    }
}
