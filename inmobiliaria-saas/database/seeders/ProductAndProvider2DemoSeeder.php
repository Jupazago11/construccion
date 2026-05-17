<?php

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductSubgroup;
use App\Models\Provider2;
use App\Models\Provider2Type;
use Illuminate\Database\Seeder;

class ProductAndProvider2DemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('nit', 'CAMILO-DEMO')->firstOrFail();

        $this->seedProviders($company);
        $this->seedProducts($company);
    }

    private function seedProviders(Company $company): void
    {
        $typeFerreteria = Provider2Type::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Ferretería'],
            ['status' => EntityStatus::Active->value]
        );

        $typePersonaNatural = Provider2Type::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Persona natural - Servicios'],
            ['status' => EntityStatus::Active->value]
        );

        $ferreterias = [
            [
                'name' => 'Ferretería El Maestro',
                'location' => 'Medellín, Antioquia',
                'document_number' => '900100200-1',
                'phone' => '6044321100',
                'email' => 'ventas@ferreteriaelmaestro.com',
            ],
            [
                'name' => 'Ferretería La Herramienta',
                'location' => 'San Luis, Antioquia',
                'document_number' => '900200300-2',
                'phone' => '3152001100',
                'email' => 'ferreteria.laherramienta@gmail.com',
            ],
            [
                'name' => 'Construmax S.A.S.',
                'location' => 'Rionegro, Antioquia',
                'document_number' => '900300400-3',
                'phone' => '6045551234',
                'email' => 'pedidos@construmax.com.co',
            ],
            [
                'name' => 'Depósito El Constructor',
                'location' => 'Medellín, Antioquia',
                'document_number' => '900400500-4',
                'phone' => '6044008877',
                'email' => 'deposito.constructor@hotmail.com',
            ],
            [
                'name' => 'Ferretería Central Oriente',
                'location' => 'Marinilla, Antioquia',
                'document_number' => '900500600-5',
                'phone' => '3104567890',
                'email' => 'centraloriente@ferreterias.co',
            ],
        ];

        foreach ($ferreterias as $data) {
            Provider2::query()->updateOrCreate(
                ['company_id' => $company->id, 'document_number' => $data['document_number']],
                array_merge($data, [
                    'company_id' => $company->id,
                    'provider2_type_id' => $typeFerreteria->id,
                    'status' => EntityStatus::Active->value,
                ])
            );
        }

        $personasNaturales = [
            [
                'name' => 'Juan García Restrepo',
                'location' => 'San Luis, Antioquia',
                'document_number' => '71234567',
                'phone' => '3004561234',
                'email' => 'juangarcia.plomero@gmail.com',
            ],
            [
                'name' => 'Carlos Restrepo Arango',
                'location' => 'Medellín, Antioquia',
                'document_number' => '71345678',
                'phone' => '3115671234',
                'email' => 'carloseléctrico@gmail.com',
            ],
            [
                'name' => 'Pedro Arango Zapata',
                'location' => 'San Luis, Antioquia',
                'document_number' => '71456789',
                'phone' => '3006781234',
                'email' => 'pedro.maestro.obra@gmail.com',
            ],
            [
                'name' => 'Luis Zapata Bedoya',
                'location' => 'Rionegro, Antioquia',
                'document_number' => '71567890',
                'phone' => '3209871234',
                'email' => 'luiszapata.pintor@gmail.com',
            ],
            [
                'name' => 'Andrés Mejía Cano',
                'location' => 'San Luis, Antioquia',
                'document_number' => '71678901',
                'phone' => '3013451234',
                'email' => 'andres.cerrajero@yahoo.com',
            ],
            [
                'name' => 'Hernán Bedoya Osorio',
                'location' => 'Marinilla, Antioquia',
                'document_number' => '71789012',
                'phone' => '3124561234',
                'email' => 'hernan.soldador@gmail.com',
            ],
            [
                'name' => 'Mario López Giraldo',
                'location' => 'San Luis, Antioquia',
                'document_number' => '71890123',
                'phone' => '3005671234',
                'email' => 'mario.carpintero@gmail.com',
            ],
            [
                'name' => 'Fabio Osorio Vargas',
                'location' => 'San Luis, Antioquia',
                'document_number' => '71901234',
                'phone' => '3186781234',
                'email' => 'fabio.oficial@hotmail.com',
            ],
            [
                'name' => 'Jorge Cano Montoya',
                'location' => 'San Luis, Antioquia',
                'document_number' => '72012345',
                'phone' => '3017891234',
                'email' => 'jorge.sisternero@gmail.com',
            ],
            [
                'name' => 'Ramón Montoya Ríos',
                'location' => 'San Luis, Antioquia',
                'document_number' => '72123456',
                'phone' => '3128901234',
                'email' => 'ramon.estucador@gmail.com',
            ],
        ];

        foreach ($personasNaturales as $data) {
            Provider2::query()->updateOrCreate(
                ['company_id' => $company->id, 'document_number' => $data['document_number']],
                array_merge($data, [
                    'company_id' => $company->id,
                    'provider2_type_id' => $typePersonaNatural->id,
                    'status' => EntityStatus::Active->value,
                ])
            );
        }
    }

    private function seedProducts(Company $company): void
    {
        $catalog = [
            'Materiales de Construcción' => [
                'Cementantes y Pegantes' => [
                    'Cemento Portland tipo I (bulto 50kg)',
                    'Cemento blanco (bulto 25kg)',
                    'Cal hidratada (bulto 40kg)',
                    'Mortero seco de pega (bulto 25kg)',
                    'Pegacor para cerámica',
                    'Pegacor flexible para porcelana',
                    'Adhesivo para enchape de muro',
                ],
                'Arena y Agregados' => [
                    'Arena de río lavada (m³)',
                    'Arena de peña (m³)',
                    'Gravilla 3/4" (m³)',
                    'Triturado 1/2" (m³)',
                    'Recebo para compactación (m³)',
                    'Arena gruesa para mortero (m³)',
                ],
                'Acero y Metales' => [
                    'Varilla corrugada 3/8" (6m)',
                    'Varilla corrugada 1/2" (6m)',
                    'Varilla corrugada 5/8" (6m)',
                    'Malla electrosoldada 10x10 (rollo)',
                    'Alambre negro para amarre (rollo)',
                    'Ángulo metálico 2x2" (6m)',
                    'Platina de acero 1/4" (6m)',
                ],
                'Bloque y Ladrillo' => [
                    'Ladrillo de obra (und)',
                    'Bloque de concreto 15x20x40 (und)',
                    'Bloque de arcilla liviano (und)',
                    'Ladrillo a la vista cara lisa (und)',
                ],
            ],
            'Acabados' => [
                'Pisos y Enchapes' => [
                    'Baldosa cerámica 30x30 cm (m²)',
                    'Porcelana rectificada 60x60 cm (m²)',
                    'Granito microvibrocomprimido (m²)',
                    'Baldosín de grano 20x20 cm (m²)',
                    'Tableta de barro artesanal (m²)',
                    'Piso vinilo SPC 4mm (m²)',
                ],
                'Pintura y Recubrimientos' => [
                    'Pintura vinilo tipo 1 blanca (galón)',
                    'Pintura esmalte sintético (galón)',
                    'Pintura epóxica para pisos (galón)',
                    'Impermeabilizante acrílico (galón)',
                    'Estuco plástico para muros (bulto 25kg)',
                    'Sellador de poros acrílico (galón)',
                    'Pintura base anticorrosivo (galón)',
                ],
                'Carpintería y Madera' => [
                    'Tablero aglomerado 15mm (lámina)',
                    'Enchape de madera pino (m²)',
                    'Puerta de madera sólida 80cm',
                    'Marco metálico para puerta',
                    'Cielo raso en drywall (m²)',
                    'Perfil de aluminio para cielo raso (ml)',
                ],
            ],
            'Eléctrico e Iluminación' => [
                'Cables y Conductores' => [
                    'Cable THW 12 AWG (rollo 100m)',
                    'Cable THW 10 AWG (rollo 100m)',
                    'Cable THW 8 AWG (rollo 100m)',
                    'Cable encauchetado 2x12 (rollo 50m)',
                    'Cinta aislante eléctrica (und)',
                    'Conduit EMT 3/4" (barra 3m)',
                ],
                'Tableros y Protecciones' => [
                    'Breaker monopolar 15A',
                    'Breaker monopolar 20A',
                    'Breaker bifásico 30A',
                    'Tablero de distribución 8 circuitos',
                    'Puesta a tierra (kit)',
                ],
                'Iluminación y Tomas' => [
                    'Bombillo LED 9W E27',
                    'Panel LED 18W embutido',
                    'Toma doble con tierra',
                    'Interruptor sencillo',
                    'Interruptor doble',
                    'Roseta de porcelana',
                ],
            ],
            'Fontanería y Sanitarios' => [
                'Tubería y Accesorios PVC' => [
                    'Tubería PVC presión 1/2" (tira 6m)',
                    'Tubería PVC presión 3/4" (tira 6m)',
                    'Tubería PVC presión 1" (tira 6m)',
                    'Tubería PVC sanitaria 3" (tira 3m)',
                    'Tubería PVC sanitaria 4" (tira 3m)',
                    'Codo PVC 90° 1/2"',
                    'Te PVC 1/2"',
                    'Reducción PVC 1" a 1/2"',
                    'Unión PVC 1/2"',
                ],
                'Aparatos Sanitarios' => [
                    'Inodoro blanco tanque bajo',
                    'Lavamanos semipedestal blanco',
                    'Ducha eléctrica 110V',
                    'Llave de paso esférica 1/2"',
                    'Sifón para lavaplatos',
                    'Válvula check 1/2"',
                    'Mezcladora monocontrol ducha',
                ],
            ],
            'Herramientas y Protección' => [
                'Herramientas Manuales' => [
                    'Palustre de acero',
                    'Llana de goma',
                    'Nivel de burbuja 60cm',
                    'Metro retráctil 8m',
                    'Plomada',
                    'Maceta de hule 1kg',
                    'Hilo de albañil (rollo)',
                ],
                'Consumibles y EPP' => [
                    'Guantes de nitrilo (caja x100)',
                    'Casco de seguridad tipo I',
                    'Gafas de seguridad',
                    'Mascarilla desechable N95 (caja x25)',
                    'Disco de corte 4.5"',
                    'Disco de desbaste 4.5"',
                    'Broca para concreto 1/4"',
                    'Broca para concreto 1/2"',
                    'Botas de seguridad punta de acero',
                ],
            ],
            'Mano de Obra' => [
                'Obras Civiles' => [
                    'Excavación manual (m³)',
                    'Relleno y compactación (m³)',
                    'Fundida de columna (und)',
                    'Mampostería ladrillo (m²)',
                    'Pañete de muro interior (m²)',
                    'Pañete de muro exterior (m²)',
                    'Fundida de placa entrepiso (m²)',
                ],
                'Instalaciones Especializadas' => [
                    'Punto eléctrico de luz (und)',
                    'Punto de tomacorriente (und)',
                    'Instalación tubería sanitaria (ml)',
                    'Instalación tubería de presión (ml)',
                    'Sanitario instalado (und)',
                    'Lavamanos instalado (und)',
                ],
                'Acabados de Obra' => [
                    'Instalación pisos cerámica (m²)',
                    'Instalación pisos porcelana (m²)',
                    'Pintura de interiores (m²)',
                    'Pintura de exteriores (m²)',
                    'Estucada y pintura (m²)',
                    'Instalación cielo raso drywall (m²)',
                    'Instalación puerta (und)',
                ],
            ],
        ];

        foreach ($catalog as $groupName => $subgroups) {
            $group = ProductGroup::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => $groupName],
                ['status' => EntityStatus::Active->value]
            );

            foreach ($subgroups as $subgroupName => $productNames) {
                $subgroup = ProductSubgroup::query()->updateOrCreate(
                    ['company_id' => $company->id, 'product_group_id' => $group->id, 'name' => $subgroupName],
                    ['status' => EntityStatus::Active->value]
                );

                foreach ($productNames as $productName) {
                    Product::query()->updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'product_subgroup_id' => $subgroup->id,
                            'name' => $productName,
                        ],
                        [
                            'product_group_id' => $group->id,
                            'status' => EntityStatus::Active->value,
                        ]
                    );
                }
            }
        }
    }
}
