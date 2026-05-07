<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class CategoryPolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $this->hasTenantPermission($user, 'categories.view', $category) && $this->isActiveRecord($category);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'categories.manage');
    }

    public function update(User $user, Category $category): bool
    {
        return $this->hasTenantPermission($user, 'categories.manage', $category) && $this->isActiveRecord($category);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->hasTenantPermission($user, 'categories.manage', $category) && $this->isActiveRecord($category);
    }

    public function restore(User $user, Category $category): bool
    {
        return $this->hasTenantPermission($user, 'audit.revert', $category);
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('categories.force-delete');
    }
}
