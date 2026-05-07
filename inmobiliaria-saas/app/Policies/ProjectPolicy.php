<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class ProjectPolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->hasTenantPermission($user, 'projects.view', $project) && $this->isActiveRecord($project);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'projects.manage');
    }

    public function update(User $user, Project $project): bool
    {
        return $this->hasTenantPermission($user, 'projects.manage', $project) && $this->isActiveRecord($project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->hasTenantPermission($user, 'projects.manage', $project) && $this->isActiveRecord($project);
    }

    public function restore(User $user, Project $project): bool
    {
        return $this->hasTenantPermission($user, 'audit.revert', $project);
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('projects.force-delete');
    }
}
