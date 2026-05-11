<?php

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Enums\SystemRole;
use App\Models\Auxiliary;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Module;
use App\Models\Project;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CamiloDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->updateOrCreate(
            ['nit' => 'CAMILO-DEMO'],
            [
                'name' => 'Empresa de Camilo',
                'legal_name' => 'Empresa de Camilo',
                'email' => 'camilo@example.com',
                'phone' => '3000000000',
                'primary_color' => '#1c1917',
                'status' => EntityStatus::Active->value,
            ]
        );

        Module::query()
            ->where('key', 'construction_finance')
            ->get()
            ->each(function (Module $module) use ($company): void {
                CompanyModule::query()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'module_id' => $module->id,
                    ],
                    [
                        'status' => EntityStatus::Active->value,
                        'enabled_at' => now(),
                        'disabled_at' => null,
                    ]
                );
            });

        $user = User::query()->updateOrCreate(
            ['username' => 'camilomorales'],
            [
                'company_id' => $company->id,
                'name' => 'Camilo Morales',
                'email' => 'camilo.morales@example.com',
                'password' => Hash::make('123456'),
                'status' => EntityStatus::Active->value,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles([SystemRole::CompanyAdmin->value]);

        $projects = [
            [
                'name' => 'cerroverde',
                'project_type' => 'mixed',
                'description' => 'Proyecto residencial de uso mixto para control financiero de obra.',
                'country' => 'Colombia',
                'state' => 'Antioquia',
                'city' => 'San Luis',
                'address' => 'Vereda La Josefina',
                'location_reference' => 'Corredor vial hacia la autopista Medellín-Bogotá',
                'start_date' => today()->subDays(90),
                'status' => EntityStatus::Active->value,
            ],
            [
                'name' => 'altosdelrio',
                'project_type' => 'apartments',
                'description' => 'Proyecto multifamiliar con enfoque en acabados y urbanismo.',
                'country' => 'Colombia',
                'state' => 'Antioquia',
                'city' => 'San Luis',
                'address' => 'Sector El Prodigio',
                'location_reference' => 'Zona urbana con acceso al parque principal',
                'start_date' => today()->subDays(60),
                'status' => EntityStatus::Active->value,
            ],
            [
                'name' => 'miradordelnorte',
                'project_type' => 'houses',
                'description' => 'Proyecto de viviendas en etapa avanzada con múltiples frentes de trabajo.',
                'country' => 'Colombia',
                'state' => 'Antioquia',
                'city' => 'San Luis',
                'address' => 'Vereda Buenos Aires',
                'location_reference' => 'Ingreso por la vía San Luis - Granada',
                'start_date' => today()->subDays(30),
                'status' => EntityStatus::Active->value,
            ],
        ];

        $structure = [
            'Mano de Obra' => [
                'description' => 'Costos asociados al personal y contratistas de obra.',
                'subcategories' => [
                    'Cuadrilla de obra' => ['Oficiales', 'Ayudantes', 'Maestro general'],
                    'Instalaciones especializadas' => ['Electricistas', 'Plomeros', 'Red contra incendios'],
                    'Supervisión técnica' => ['Residente de obra', 'Inspector SST'],
                ],
            ],
            'Documentos' => [
                'description' => 'Trámites, permisos y gestión documental del proyecto.',
                'subcategories' => [
                    'Licencias y permisos' => ['Curaduría', 'Planeación municipal', 'Bomberos'],
                    'Estudios técnicos' => ['Suelos', 'Topografía', 'Interventoría'],
                    'Contratos y pólizas' => ['Póliza de cumplimiento', 'Póliza todo riesgo'],
                ],
            ],
            'Materiales' => [
                'description' => 'Materiales principales y suministros para construcción.',
                'subcategories' => [
                    'Obra gris' => ['Cemento', 'Arena', 'Acero'],
                    'Acabados' => ['Pisos', 'Pintura', 'Carpintería metálica'],
                    'Herramientas y consumibles' => ['Brocas', 'Discos de corte', 'Elementos de protección'],
                ],
            ],
            'Equipos y Alquileres' => [
                'description' => 'Costos de alquiler de maquinaria, equipos temporales y apoyo logístico.',
                'subcategories' => [
                    'Maquinaria liviana' => ['Cortadora', 'Mezcladora'],
                    'Equipos de elevación' => ['Andamios', 'Malacate'],
                    'Servicios temporales' => ['Baños portátiles', 'Cerramiento provisional'],
                ],
            ],
        ];

        foreach ($projects as $projectData) {
            $project = Project::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $projectData['name'],
                ],
                $projectData + ['company_id' => $company->id]
            );

            $this->seedProjectStructure($project, $structure);
        }
    }

    protected function seedProjectStructure(Project $project, array $structure): void
    {
        $categoryOrder = 1;

        foreach ($structure as $categoryName => $categoryData) {
            $category = Category::query()->updateOrCreate(
                [
                    'project_id' => $project->id,
                    'name' => $categoryName,
                ],
                [
                    'description' => $categoryData['description'],
                    'sort_order' => $categoryOrder++,
                    'status' => EntityStatus::Active->value,
                ]
            );

            $subcategoryOrder = 1;

            foreach ($categoryData['subcategories'] as $subcategoryName => $auxiliaries) {
                $subcategory = Subcategory::query()->updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $subcategoryName,
                    ],
                    [
                        'description' => "Detalle de {$subcategoryName}.",
                        'sort_order' => $subcategoryOrder++,
                        'status' => EntityStatus::Active->value,
                    ]
                );

                $auxiliaryOrder = 1;

                foreach ($auxiliaries as $auxiliaryName) {
                    Auxiliary::query()->updateOrCreate(
                        [
                            'subcategory_id' => $subcategory->id,
                            'name' => $auxiliaryName,
                        ],
                        [
                            'description' => "Auxiliar para {$subcategoryName}.",
                            'sort_order' => $auxiliaryOrder++,
                            'status' => EntityStatus::Active->value,
                        ]
                    );
                }
            }
        }
    }
}
