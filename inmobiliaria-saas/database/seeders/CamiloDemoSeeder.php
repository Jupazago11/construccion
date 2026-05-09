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

        $project = Project::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'cerroverde',
            ],
            [
                'project_type' => 'mixed',
                'description' => 'Proyecto demo para control financiero de obra.',
                'country' => 'Colombia',
                'state' => 'Antioquia',
                'city' => 'Medellín',
                'address' => 'Sector Cerroverde',
                'location_reference' => 'Zona residencial',
                'start_date' => today(),
                'status' => 'active',
            ]
        );

        $structure = [
            'Mano de Obra' => [
                'description' => 'Costos asociados al personal y contratistas de obra.',
                'subcategories' => [
                    'Cuadrilla de obra' => ['Oficiales', 'Ayudantes'],
                    'Instalaciones especializadas' => ['Electricistas', 'Plomeros'],
                    'Supervisión técnica' => ['Residente de obra'],
                ],
            ],
            'Documentos' => [
                'description' => 'Trámites, permisos y gestión documental del proyecto.',
                'subcategories' => [
                    'Licencias y permisos' => ['Curaduría', 'Planeación municipal'],
                    'Estudios técnicos' => ['Suelos', 'Topografía'],
                    'Contratos y pólizas' => [],
                ],
            ],
            'Materiales' => [
                'description' => 'Materiales principales y suministros para construcción.',
                'subcategories' => [
                    'Obra gris' => ['Cemento', 'Arena', 'Acero'],
                    'Acabados' => ['Pisos', 'Pintura'],
                    'Herramientas y consumibles' => [],
                ],
            ],
        ];

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
