<?php

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Models\Asset;
use App\Models\AssetNovelty;
use App\Models\AssetNoveltyType;
use App\Models\AssetType;
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
        $typeNames = [
            'Maquinaria pesada',
            'Maquinaria liviana',
            'Equipo eléctrico',
            'Equipo de medición',
            'Equipo de seguridad colectiva',
            'Herramienta eléctrica',
            'Herramienta manual',
            'Herramienta de corte',
            'Herramienta de elevación',
            'Equipo de soldadura',
            'Equipo de compactación',
            'Equipo de bombeo',
            'Equipo de comunicación',
            'Equipo de cómputo',
            'Equipo de topografía',
            'Vehículo de carga',
            'Vehículo liviano',
            'Moto de obra',
            'Andamio y cimbra',
            'Formaleta y encofrado',
            'Estructura temporal',
            'Bodega y almacén',
            'Oficina de obra',
            'Baño portátil',
            'Cerramiento y señalización',
            'Planta eléctrica',
            'Tablero eléctrico temporal',
            'Extintor',
            'Botiquín y primeros auxilios',
            'Arnés y EPP colectivo',
            'Carretilla y transporte manual',
            'Compresor de aire',
            'Generador eléctrico',
            'Equipo de pintura',
            'Mobiliario de obra',
        ];

        $assetTypes = [];
        foreach ($typeNames as $typeName) {
            $assetTypes[$typeName] = AssetType::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => $typeName],
                ['status' => EntityStatus::Active->value]
            );
        }

        $assetTypes['tool'] = $assetTypes['Herramienta manual'];
        $assetTypes['equipment'] = $assetTypes['Maquinaria liviana'];
        $maintenanceType = AssetNoveltyType::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Mantenimiento'],
            ['adds_value' => false, 'status' => EntityStatus::Active->value]
        );

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
                    'asset_type_id' => $assetTypes[$data['asset_type']]?->id,
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
                        'asset_novelty_type_id' => $maintenanceType->id,
                        'status' => EntityStatus::Active->value,
                    ]
                );
            }
        }
    }
}
