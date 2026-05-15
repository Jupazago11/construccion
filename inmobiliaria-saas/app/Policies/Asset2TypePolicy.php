<?php

namespace App\Policies;

use App\Models\Asset2Type;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class Asset2TypePolicy
{
    use ResolvesTenantOwnership;

    public function update(User $user, Asset2Type $asset2Type): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $asset2Type) && $this->isActiveRecord($asset2Type);
    }

    public function delete(User $user, Asset2Type $asset2Type): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $asset2Type) && $this->isActiveRecord($asset2Type);
    }
}
