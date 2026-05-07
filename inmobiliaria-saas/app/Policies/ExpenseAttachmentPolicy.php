<?php

namespace App\Policies;

use App\Models\ExpenseAttachment;
use App\Models\User;
use App\Policies\Concerns\ResolvesTenantOwnership;

class ExpenseAttachmentPolicy
{
    use ResolvesTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $this->hasTenantPermission($user, 'attachments.view');
    }

    public function view(User $user, ExpenseAttachment $expenseAttachment): bool
    {
        return $this->hasTenantPermission($user, 'attachments.view', $expenseAttachment) && $this->isActiveRecord($expenseAttachment);
    }

    public function create(User $user): bool
    {
        return $this->hasTenantPermission($user, 'attachments.manage');
    }

    public function update(User $user, ExpenseAttachment $expenseAttachment): bool
    {
        return $this->hasTenantPermission($user, 'attachments.manage', $expenseAttachment) && $this->isActiveRecord($expenseAttachment);
    }

    public function delete(User $user, ExpenseAttachment $expenseAttachment): bool
    {
        return $this->hasTenantPermission($user, 'attachments.manage', $expenseAttachment) && $this->isActiveRecord($expenseAttachment);
    }

    public function restore(User $user, ExpenseAttachment $expenseAttachment): bool
    {
        return $this->hasTenantPermission($user, 'audit.revert', $expenseAttachment);
    }

    public function forceDelete(User $user, ExpenseAttachment $expenseAttachment): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('attachments.force-delete');
    }
}
