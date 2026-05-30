<?php

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Models\Asset2;
use App\Models\Asset2Novelty;
use App\Models\Asset2Type;
use App\Models\AssetNoveltyType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class Asset2DemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('nit', 'CAMILO-DEMO')->first();

        if (! $company) {
            return;
        }

        $creator = User::query()->where('username', 'camilomorales')->first();

        // Dejar solo 5 tipos relevantes, archivar el resto
        $keepNames = ['Material de construcción', 'Herramienta', 'Dotación y EPP', 'Consumible', 'Equipo'];

        Asset2Type::query()
            ->where('company_id', $company->id)
            ->whereNotIn('name', $keepNames)
            ->update(['status' => EntityStatus::Deleted->value]);

        $types = [];
        foreach ($keepNames as $name) {
            $types[$name] = Asset2Type::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => $name],
                ['status' => EntityStatus::Active->value]
            );
        }

        $mat  = $types['Material de construcción'];
        $tool = $types['Herramienta'];
        $ppe  = $types['Dotación y EPP'];
        $con  = $types['Consumible'];
        $eq   = $types['Equipo'];

        $reposicion = AssetNoveltyType::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Reposición de stock'],
            ['adds_value' => false, 'status' => EntityStatus::Active->value]
        );
        $mantenimiento = AssetNoveltyType::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Mantenimiento'],
            ['adds_value' => false, 'status' => EntityStatus::Active->value]
        );

        $items = [
            // Materiales de construcción
            ['name' => 'Cemento Portland tipo I (bulto 50kg)',     'type' => $mat,  'condition' => 'new',  'qty' => 80,  'price' => 32000,  'date' => '-20 days'],
            ['name' => 'Varilla corrugada 1/2" (6m)',              'type' => $mat,  'condition' => 'new',  'qty' => 150, 'price' => 28500,  'date' => '-15 days'],
            ['name' => 'Bloque de concreto 15x20x40',              'type' => $mat,  'condition' => 'new',  'qty' => 500, 'price' => 2800,   'date' => '-10 days'],
            ['name' => 'Ladrillo de obra',                         'type' => $mat,  'condition' => 'new',  'qty' => 800, 'price' => 1200,   'date' => '-8 days'],
            ['name' => 'Arena de río lavada (m³)',                  'type' => $mat,  'condition' => 'new',  'qty' => 12,  'price' => 85000,  'date' => '-12 days'],
            ['name' => 'Gravilla 3/4" (m³)',                       'type' => $mat,  'condition' => 'new',  'qty' => 8,   'price' => 95000,  'date' => '-12 days'],
            ['name' => 'Varilla corrugada 3/8" (6m)',              'type' => $mat,  'condition' => 'new',  'qty' => 200, 'price' => 15000,  'date' => '-9 days'],
            ['name' => 'Tubería PVC presión 1/2" (tira 6m)',       'type' => $mat,  'condition' => 'new',  'qty' => 40,  'price' => 18000,  'date' => '-7 days'],
            ['name' => 'Tubería PVC sanitaria 4" (tira 3m)',       'type' => $mat,  'condition' => 'new',  'qty' => 20,  'price' => 32000,  'date' => '-7 days'],
            ['name' => 'Cable THW 12 AWG (rollo 100m)',            'type' => $mat,  'condition' => 'new',  'qty' => 6,   'price' => 145000, 'date' => '-5 days'],
            ['name' => 'Baldosa cerámica 30x30 cm (m²)',           'type' => $mat,  'condition' => 'new',  'qty' => 120, 'price' => 38000,  'date' => '-3 days'],
            ['name' => 'Pintura vinilo tipo 1 blanca (galón)',     'type' => $mat,  'condition' => 'new',  'qty' => 24,  'price' => 62000,  'date' => '-2 days'],
            ['name' => 'Estuco plástico para muros (bulto 25kg)',  'type' => $mat,  'condition' => 'new',  'qty' => 15,  'price' => 28000,  'date' => '-2 days'],
            ['name' => 'Malla electrosoldada 10x10 (rollo)',       'type' => $mat,  'condition' => 'new',  'qty' => 10,  'price' => 185000, 'date' => '-6 days'],
            // Herramientas
            ['name' => 'Palustre de acero',                        'type' => $tool, 'condition' => 'used', 'qty' => 12,  'price' => 22000,  'date' => '-150 days'],
            ['name' => 'Nivel de burbuja 60cm',                    'type' => $tool, 'condition' => 'used', 'qty' => 4,   'price' => 45000,  'date' => '-120 days'],
            ['name' => 'Metro retráctil 8m',                       'type' => $tool, 'condition' => 'used', 'qty' => 6,   'price' => 28000,  'date' => '-100 days'],
            ['name' => 'Maceta de hule 1kg',                       'type' => $tool, 'condition' => 'used', 'qty' => 8,   'price' => 18000,  'date' => '-90 days'],
            ['name' => 'Llana de goma',                            'type' => $tool, 'condition' => 'used', 'qty' => 6,   'price' => 15000,  'date' => '-90 days'],
            ['name' => 'Plomada',                                   'type' => $tool, 'condition' => 'used', 'qty' => 3,   'price' => 12000,  'date' => '-80 days'],
            ['name' => 'Hilo de albañil (rollo)',                   'type' => $tool, 'condition' => 'new',  'qty' => 10,  'price' => 8000,   'date' => '-14 days'],
            // Dotación y EPP
            ['name' => 'Casco de seguridad tipo I',                'type' => $ppe,  'condition' => 'new',  'qty' => 20,  'price' => 18000,  'date' => '-90 days'],
            ['name' => 'Guantes de nitrilo (caja x100)',           'type' => $ppe,  'condition' => 'new',  'qty' => 8,   'price' => 35000,  'date' => '-8 days'],
            ['name' => 'Gafas de seguridad',                       'type' => $ppe,  'condition' => 'new',  'qty' => 15,  'price' => 12000,  'date' => '-60 days'],
            ['name' => 'Mascarilla desechable N95 (caja x25)',     'type' => $ppe,  'condition' => 'new',  'qty' => 6,   'price' => 48000,  'date' => '-30 days'],
            ['name' => 'Botas de seguridad punta de acero',        'type' => $ppe,  'condition' => 'new',  'qty' => 10,  'price' => 95000,  'date' => '-45 days'],
            // Consumibles
            ['name' => 'Disco de corte 4.5"',                      'type' => $con,  'condition' => 'new',  'qty' => 30,  'price' => 8500,   'date' => '-12 days'],
            ['name' => 'Disco de desbaste 4.5"',                   'type' => $con,  'condition' => 'new',  'qty' => 20,  'price' => 9500,   'date' => '-12 days'],
            ['name' => 'Broca para concreto 1/2"',                 'type' => $con,  'condition' => 'new',  'qty' => 12,  'price' => 14000,  'date' => '-18 days'],
            ['name' => 'Alambre negro para amarre (rollo)',        'type' => $con,  'condition' => 'new',  'qty' => 15,  'price' => 22000,  'date' => '-11 days'],
            ['name' => 'Cinta aislante eléctrica',                 'type' => $con,  'condition' => 'new',  'qty' => 24,  'price' => 4500,   'date' => '-5 days'],
            // Equipos
            ['name' => 'Mezcladora de concreto 1 saco',            'type' => $eq,   'condition' => 'used', 'qty' => 1,   'price' => 4800000,'date' => '-14 months'],
            ['name' => 'Cortadora eléctrica de cerámica',          'type' => $eq,   'condition' => 'new',  'qty' => 1,   'price' => 1250000,'date' => '-4 months'],
            ['name' => 'Taladro percutor industrial',              'type' => $eq,   'condition' => 'new',  'qty' => 2,   'price' => 950000, 'date' => '-6 months'],
            ['name' => 'Compresor de aire 50L',                    'type' => $eq,   'condition' => 'used', 'qty' => 1,   'price' => 2100000,'date' => '-8 months'],
        ];

        foreach ($items as $data) {
            $date = now()->modify($data['date'])->toDateString();

            $asset2 = Asset2::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => $data['name']],
                [
                    'asset2_type_id' => $data['type']->id,
                    'asset2_type'    => $data['type']->name,
                    'asset_condition'=> $data['condition'],
                    'quantity'       => $data['qty'],
                    'purchase_value' => $data['price'],
                    'purchase_date'  => $date,
                    'status'         => EntityStatus::Active->value,
                ]
            );

            // Novedad de ingreso si no tiene ninguna
            if ($asset2->novelties()->count() === 0) {
                Asset2Novelty::query()->create([
                    'asset2_id'             => $asset2->id,
                    'created_by'            => $creator?->id,
                    'asset_novelty_type_id' => $reposicion->id,
                    'name'                  => 'Ingreso inicial',
                    'cost'                  => $data['price'] * $data['qty'],
                    'description'           => 'Registro de ingreso al inventario.',
                    'asset_status'          => 'Disponible',
                    'novelty_date'          => $date,
                    'status'                => EntityStatus::Active->value,
                ]);
            }
        }
    }
}
