<?php

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Models\Asset;
use App\Models\AssetNovelty;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('nit', 'CAMILO-DEMO')->first();

        if (! $company) {
            return;
        }

        $creator = User::query()->where('username', 'camilomorales')->first();

        $assets = [
            [
                'name' => 'Mezcladora de concreto',
                'asset_type' => 'equipment',
                'asset_condition' => 'used',
                'purchase_value' => 4800000,
                'purchase_date' => today()->subMonths(14),
            ],
            [
                'name' => 'Taladro percutor industrial',
                'asset_type' => 'tool',
                'asset_condition' => 'new',
                'purchase_value' => 950000,
                'purchase_date' => today()->subMonths(6),
            ],
            [
                'name' => 'Andamio tubular modular',
                'asset_type' => 'equipment',
                'asset_condition' => 'used',
                'purchase_value' => 3200000,
                'purchase_date' => today()->subMonths(18),
            ],
            [
                'name' => 'Cortadora eléctrica de cerámica',
                'asset_type' => 'tool',
                'asset_condition' => 'new',
                'purchase_value' => 1250000,
                'purchase_date' => today()->subMonths(4),
            ],
        ];

        foreach ($assets as $index => $data) {
            $asset = Asset::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $data['name'],
                ],
                [
                    ...$data,
                    'status' => EntityStatus::Active->value,
                ]
            );

            $novelties = [
                [
                    'cost' => 180000 + ($index * 25000),
                    'description' => 'Mantenimiento preventivo y ajuste general del activo.',
                    'asset_status' => 'Operativo',
                    'novelty_date' => today()->subDays(25 - ($index * 3)),
                ],
                [
                    'cost' => 95000 + ($index * 20000),
                    'description' => 'Compra de repuesto menor y revisión de seguridad.',
                    'asset_status' => 'En observación',
                    'novelty_date' => today()->subDays(8 - min($index, 7)),
                ],
            ];

            foreach ($novelties as $novelty) {
                AssetNovelty::query()->updateOrCreate(
                    [
                        'asset_id' => $asset->id,
                        'novelty_date' => $novelty['novelty_date'],
                        'description' => $novelty['description'],
                    ],
                    [
                        ...$novelty,
                        'created_by' => $creator?->id,
                        'status' => EntityStatus::Active->value,
                    ]
                );
            }
        }
    }
}
