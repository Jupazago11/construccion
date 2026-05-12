<?php

namespace App\Policies;

use App\Models\AssetType;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class AssetTypePolicy
{
    use ResolvesTenantOwnership;

    public function update(User $user, AssetType $assetType): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $assetType) && $this->isActiveRecord($assetType);
    }

    public function delete(User $user, AssetType $assetType): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $assetType) && $this->isActiveRecord($assetType);
    }
}
