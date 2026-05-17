<?php

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Models\CatalogActivity;
use App\Models\CatalogActivityGroup;
use App\Models\CatalogActivitySubgroup;
use App\Models\Company;
use Illuminate\Database\Seeder;

class ActivityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->orderBy('id')
            ->get();

        foreach ($companies as $company) {
            $this->seedCatalogForCompany($company->id);
        }
    }

    private function seedCatalogForCompany(int $companyId): void
    {
        $catalog = [
            'Obra gris' => [
                'Cimentacion y estructura' => [
                    'Excavacion manual',
                    'Replanteo y nivelacion',
                    'Armado de acero',
                    'Formaleta de columnas',
                    'Fundida de zapatas',
                    'Fundida de vigas',
                    'Fundida de columnas',
                    'Fundida de placa',
                    'Desencofrado',
                ],
                'Mamposteria estructural' => [
                    'Mamposteria en bloque',
                    'Mamposteria en ladrillo',
                    'Levantamiento de muros divisorios',
                    'Dintel y refuerzo de vanos',
                    'Pega de sardinel',
                ],
                'Concretos y mezclas' => [
                    'Mezcla y vaciado de concreto',
                    'Vibrado de concreto',
                    'Curado de concreto',
                    'Mortero para nivelacion',
                    'Relleno y compactacion',
                ],
            ],
            'Instalaciones tecnicas' => [
                'Electricas' => [
                    'Punto electrico',
                    'Punto de iluminacion',
                    'Punto de tomacorriente',
                    'Tendido de tuberia conduit',
                    'Cableado y conexionado',
                    'Instalacion de tablero electrico',
                    'Instalacion de luminarias',
                    'Puesta a tierra',
                ],
                'Hidrosanitarias' => [
                    'Punto hidraulico',
                    'Punto sanitario',
                    'Instalacion de tuberia PVC',
                    'Instalacion de tuberia presion',
                    'Instalacion de cajas y registros',
                    'Prueba de redes hidraulicas',
                    'Prueba de redes sanitarias',
                ],
                'Gas y especiales' => [
                    'Punto de gas',
                    'Instalacion de red de gas',
                    'Prueba de hermeticidad',
                    'Instalacion de red contra incendios',
                    'Canalizacion para telecomunicaciones',
                ],
            ],
            'Cubiertas y exteriores' => [
                'Cubiertas' => [
                    'Estructura metalica de cubierta',
                    'Instalacion de cubierta termoacustica',
                    'Instalacion de teja',
                    'Canales y bajantes',
                    'Impermeabilizacion de cubierta',
                    'Sellos y remates',
                ],
                'Fachadas y exteriores' => [
                    'Panuete exterior',
                    'Pintura de fachada',
                    'Revestimiento de fachada',
                    'Andenes y senderos',
                    'Cerramiento perimetral',
                    'Jardineria y limpieza exterior',
                ],
            ],
            'Acabados' => [
                'Pisos y enchapes' => [
                    'Instalacion de ceramica',
                    'Instalacion de porcelanato',
                    'Instalacion de tableta',
                    'Boquillado',
                    'Nivelacion de piso',
                    'Pulida y sellado',
                ],
                'Paredes y cielos' => [
                    'Panuete interior',
                    'Estuco',
                    'Pintura de interiores',
                    'Drywall de muros',
                    'Cielo raso en drywall',
                    'Cielo raso en PVC',
                    'Pintura de cielo raso',
                ],
                'Carpinteria y metalisteria' => [
                    'Instalacion de puerta',
                    'Instalacion de marco',
                    'Instalacion de ventana',
                    'Carpinteria en madera',
                    'Soldadura y metalisteria',
                    'Barandas y pasamanos',
                    'Muebles fijos',
                ],
            ],
            'Equipamiento y montaje' => [
                'Aparatos y accesorios' => [
                    'Instalacion de sanitario',
                    'Instalacion de lavamanos',
                    'Instalacion de griferia',
                    'Instalacion de ducha',
                    'Instalacion de lavaplatos',
                    'Instalacion de accesorios de bano',
                ],
                'Montajes especiales' => [
                    'Montaje de estructura liviana',
                    'Montaje de paneleria',
                    'Montaje de vidrio',
                    'Montaje de mobiliario',
                    'Instalacion de equipos menores',
                ],
            ],
            'Servicios de apoyo' => [
                'Logistica y apoyo de obra' => [
                    'Cargue y descargue',
                    'Acarreo interno de materiales',
                    'Limpieza de obra',
                    'Retiro de escombros',
                    'Senalizacion temporal',
                    'Vigilancia de obra',
                ],
                'Maquinaria y equipos' => [
                    'Operacion de mezcladora',
                    'Operacion de vibrocompactador',
                    'Operacion de cortadora',
                    'Operacion de equipo de soldadura',
                    'Alquiler con operario',
                ],
            ],
        ];

        foreach ($catalog as $groupName => $subgroups) {
            $group = CatalogActivityGroup::query()->updateOrCreate(
                ['company_id' => $companyId, 'name' => $groupName],
                ['status' => EntityStatus::Active->value]
            );

            foreach ($subgroups as $subgroupName => $activities) {
                $subgroup = CatalogActivitySubgroup::query()->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'activity_group_id' => $group->id,
                        'name' => $subgroupName,
                    ],
                    ['status' => EntityStatus::Active->value]
                );

                foreach ($activities as $activityName) {
                    CatalogActivity::query()->updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'activity_subgroup_id' => $subgroup->id,
                            'name' => $activityName,
                        ],
                        [
                            'activity_group_id' => $group->id,
                            'status' => EntityStatus::Active->value,
                        ]
                    );
                }
            }
        }
    }
}
