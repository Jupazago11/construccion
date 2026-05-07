<?php

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['username' => env('SUPERADMIN_USERNAME', 'superadmin')],
            [
                'username' => env('SUPERADMIN_USERNAME', 'superadmin'),
                'name' => env('SUPERADMIN_NAME', 'Super Admin'),
                'email' => env('SUPERADMIN_EMAIL', 'superadmin@example.com'),
                'password' => Hash::make(env('SUPERADMIN_PASSWORD', 'ChangeMe123!')),
                'status' => EntityStatus::Active->value,
                'company_id' => null,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles([SystemRole::SuperAdmin->value]);
    }
}
