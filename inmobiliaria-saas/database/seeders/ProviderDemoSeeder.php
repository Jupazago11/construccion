<?php

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Models\Company;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()
            ->where('nit', 'CAMILO-DEMO')
            ->first();

        if (! $company) {
            return;
        }

        $providers = [
            [
                'name' => 'Ferretería El Constructor',
                'document_number' => '900100001',
                'phone' => '3001112233',
                'email' => 'ventas@ferreconstructor.test',
            ],
            [
                'name' => 'Transportes Cerro Verde',
                'document_number' => '900100002',
                'phone' => '3002223344',
                'email' => 'operaciones@transportescerroverde.test',
            ],
            [
                'name' => 'Ferretería La 80',
                'document_number' => '900100003',
                'phone' => '3003334455',
                'email' => 'cotizaciones@ferreteriala80.test',
            ],
        ];

        foreach ($providers as $provider) {
            Provider::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $provider['name'],
                ],
                [
                    ...$provider,
                    'status' => EntityStatus::Active->value,
                ]
            );
        }
    }
}
