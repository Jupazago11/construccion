<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class ExpensePolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'expenses.view');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $this->hasTenantPermission($user, 'expenses.view', $expense) && $this->isActiveRecord($expense);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'expenses.manage');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $this->hasTenantPermission($user, 'expenses.manage', $expense) && $this->isActiveRecord($expense);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->hasTenantPermission($user, 'expenses.manage', $expense) && $this->isActiveRecord($expense);
    }

    public function restore(User $user, Expense $expense): bool
    {
        return $this->hasTenantPermission($user, 'audit.revert', $expense);
    }

    public function forceDelete(User $user, Expense $expense): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('expenses.force-delete');
    }
}
