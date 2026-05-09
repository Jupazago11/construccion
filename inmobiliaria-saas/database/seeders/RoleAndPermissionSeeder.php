<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'companies.view',
            'companies.manage',
            'companies.force-delete',
            'modules.view',
            'modules.manage',
            'users.view',
            'users.manage',
            'projects.view',
            'projects.manage',
            'projects.force-delete',
            'categories.view',
            'categories.manage',
            'categories.force-delete',
            'providers.view',
            'providers.manage',
            'providers.force-delete',
            'expenses.view',
            'expenses.manage',
            'expenses.force-delete',
            'attachments.view',
            'attachments.manage',
            'attachments.force-delete',
            'reports.view',
            'audit.view',
            'audit.revert',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $rolePermissions = [
            SystemRole::SuperAdmin->value => $permissions,

            SystemRole::CompanyAdmin->value => [
                'dashboard.view',
                'companies.view',
                'companies.manage',
                'users.view',
                'users.manage',
                'projects.view',
                'projects.manage',
                'categories.view',
                'categories.manage',
                'providers.view',
                'providers.manage',
                'expenses.view',
                'expenses.manage',
                'attachments.view',
                'attachments.manage',
                'reports.view',
                'audit.view',
                'audit.revert',
            ],

            SystemRole::Operator->value => [
                'dashboard.view',
                'projects.view',
                'categories.view',
                'providers.view',
                'expenses.view',
                'expenses.manage',
                'attachments.view',
                'attachments.manage',
            ],

            SystemRole::Viewer->value => [
                'dashboard.view',
                'projects.view',
                'categories.view',
                'providers.view',
                'expenses.view',
                'attachments.view',
                'reports.view',
            ],

            SystemRole::BuyerUser->value => [],
        ];

        foreach ($rolePermissions as $roleName => $assignedPermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $permissionModels = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $assignedPermissions)
                ->get();

            $role->syncPermissions($permissionModels);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}