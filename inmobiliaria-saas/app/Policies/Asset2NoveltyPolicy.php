<?php

namespace App\Policies;

use App\Models\Asset2Novelty;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class Asset2NoveltyPolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'assets.view');
    }

    public function view(User $user, Asset2Novelty $asset2Novelty): bool
    {
        return $this->hasTenantPermission($user, 'assets.view', $asset2Novelty) && $this->isActiveRecord($asset2Novelty);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage');
    }

    public function update(User $user, Asset2Novelty $asset2Novelty): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $asset2Novelty) && $this->isActiveRecord($asset2Novelty);
    }

    public function delete(User $user, Asset2Novelty $asset2Novelty): bool
    {
        return $this->hasTenantPermission($user, 'assets.manage', $asset2Novelty) && $this->isActiveRecord($asset2Novelty);
    }

    public function restore(User $user, Asset2Novelty $asset2Novelty): bool
    {
        return $this->hasTenantPermission($user, 'audit.revert', $asset2Novelty);
    }
}
