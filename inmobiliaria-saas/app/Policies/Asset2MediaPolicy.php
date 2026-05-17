<?php

namespace App\Policies;

use App\Models\Asset2Media;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class Asset2MediaPolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'assets.view');
    }

    public function view(User $user, Asset2Media $asset2Media): bool
    {
        return $this->hasTenantPermission($user, 'assets.view', $asset2Media) && $this->isActiveRecord($asset2Media);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage');
    }

    public function delete(User $user, Asset2Media $asset2Media): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $asset2Media) && $this->isActiveRecord($asset2Media);
    }
}
