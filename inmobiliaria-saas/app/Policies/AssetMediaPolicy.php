<?php

namespace App\Policies;

use App\Models\AssetMedia;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class AssetMediaPolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'assets.view');
    }

    public function view(User $user, AssetMedia $assetMedia): bool
    {
        return $this->hasTenantPermission($user, 'assets.view', $assetMedia) && $this->isActiveRecord($assetMedia);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage');
    }

    public function delete(User $user, AssetMedia $assetMedia): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $assetMedia) && $this->isActiveRecord($assetMedia);
    }
}
