<?php

namespace App\Policies;

use App\Models\Subcategory;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class SubcategoryPolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'categories.view');
    }

    public function view(User $user, Subcategory $subcategory): bool
    {
        return $this->hasTenantPermission($user, 'categories.view', $subcategory) && $this->isActiveRecord($subcategory);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'categories.manage');
    }

    public function update(User $user, Subcategory $subcategory): bool
    {
        return $this->hasTenantPermission($user, 'categories.manage', $subcategory) && $this->isActiveRecord($subcategory);
    }

    public function delete(User $user, Subcategory $subcategory): bool
    {
        return $this->hasTenantPermission($user, 'categories.manage', $subcategory) && $this->isActiveRecord($subcategory);
    }

    public function restore(User $user, Subcategory $subcategory): bool
    {
        return $this->hasTenantPermission($user, 'audit.revert', $subcategory);
    }

    public function forceDelete(User $user, Subcategory $subcategory): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('categories.force-delete');
    }
}
