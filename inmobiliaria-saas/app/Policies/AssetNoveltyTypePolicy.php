<?php

namespace App\Policies;

use App\Models\AssetNoveltyType;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class AssetNoveltyTypePolicy
{
    use ResolvesTenantOwnership;

    public function update(User $user, AssetNoveltyType $assetNoveltyType): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $assetNoveltyType) && $this->isActiveRecord($assetNoveltyType);
    }

    public function delete(User $user, AssetNoveltyType $assetNoveltyType): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $assetNoveltyType) && $this->isActiveRecord($assetNoveltyType);
    }
}
