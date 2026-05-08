<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('audit.view');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('audit.view');
    }

    public function restore(User $user, Activity $activity): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('audit.revert');
    }
}
