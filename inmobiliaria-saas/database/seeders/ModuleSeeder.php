<?php

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'key' => 'construction_finance',
                'name' => 'Construction Finance',
                'description' => 'Project construction finance control and reporting.',
            ],
            [
                'key' => 'property_sales',
                'name' => 'Property Sales',
                'description' => 'Future property sales lifecycle module.',
            ],
            [
                'key' => 'customer_portal',
                'name' => 'Customer Portal',
                'description' => 'Future buyer-facing portal module.',
            ],
            [
                'key' => 'advanced_reports',
                'name' => 'Advanced Reports',
                'description' => 'Extended reporting and analytics module.',
            ],
        ];

        foreach ($modules as $module) {
            Module::query()->updateOrCreate(
                ['key' => $module['key']],
                [...$module, 'status' => EntityStatus::Active->value]
            );
        }
    }
}
