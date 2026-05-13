<?php

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $username = env('SUPERADMIN_USERNAME', 'superadmin');
        $password = env('SUPERADMIN_PASSWORD');
        $user = User::query()->where('username', $username)->first();

        if (! $user && app()->isProduction() && blank($password)) {
            throw new RuntimeException(
                'Configure SUPERADMIN_PASSWORD before running the production superadmin seeder.'
            );
        }

        $attributes = [
            'username' => $username,
            'name' => env('SUPERADMIN_NAME', 'Super Admin'),
            'email' => env('SUPERADMIN_EMAIL', 'superadmin@example.com'),
            'status' => EntityStatus::Active->value,
            'company_id' => null,
            'email_verified_at' => now(),
        ];

        if ((! $user || env('SUPERADMIN_RESET_PASSWORD', false)) && filled($password)) {
            $attributes['password'] = Hash::make($password);
        }

        if (! $user && ! array_key_exists('password', $attributes)) {
            $attributes['password'] = Hash::make('123456');
        }

        $user = User::query()->updateOrCreate(
            ['username' => $username],
            $attributes
        );

        $user->syncRoles([SystemRole::SuperAdmin->value]);
    }
}
