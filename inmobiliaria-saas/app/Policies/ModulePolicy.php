<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;

class ModulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('modules.view');
    }

    public function view(User $user, Module $module): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('modules.view') && $module->status !== 'deleted';
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('modules.manage');
    }

    public function update(User $user, Module $module): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('modules.manage') && $module->status !== 'deleted';
    }

    public function delete(User $user, Module $module): bool
    {
        return false;
    }

    public function restore(User $user, Module $module): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('audit.revert');
    }

    public function forceDelete(User $user, Module $module): bool
    {
        return false;
    }
}
