<?php

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Provider;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

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
                'location' => 'Rionegro, Antioquia',
                'document_number' => '900100001',
                'phone' => '3001112233',
                'email' => 'ventas@ferreconstructor.test',
            ],
            [
                'name' => 'Transportes Cerro Verde',
                'location' => 'San Luis, Antioquia',
                'document_number' => '900100002',
                'phone' => '3002223344',
                'email' => 'operaciones@transportescerroverde.test',
            ],
            [
                'name' => 'Ferretería La 80',
                'location' => 'Rionegro, Antioquia',
                'document_number' => '900100003',
                'phone' => '3003334455',
                'email' => 'cotizaciones@ferreteriala80.test',
            ],
            [
                'name' => 'Acabados Urbanos SAS',
                'location' => 'San Luis, Antioquia',
                'document_number' => '900100004',
                'phone' => '3004445566',
                'email' => 'compras@acabadosurbanos.test',
            ],
            [
                'name' => 'Ingeniería y Equipos del Norte',
                'location' => 'Rionegro, Antioquia',
                'document_number' => '900100005',
                'phone' => '3005556677',
                'email' => 'servicio@ingenierianorte.test',
            ],
        ];

        $providerModels = collect();

        foreach ($providers as $provider) {
            $providerModels->push(Provider::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $provider['name'],
                ],
                [
                    ...$provider,
                    'status' => EntityStatus::Active->value,
                ]
            ));
        }

        $projects = Project::query()
            ->with(['categories.subcategories.auxiliaries'])
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->get();

        foreach ($projects as $projectIndex => $project) {
            $this->seedExpensesForProject($company->id, $project, $providerModels, $projectIndex);
        }
    }

    protected function seedExpensesForProject(int $companyId, Project $project, Collection $providers, int $projectIndex): void
    {
        $structures = collect();

        foreach ($project->categories as $category) {
            foreach ($category->subcategories as $subcategory) {
                foreach ($subcategory->auxiliaries as $auxiliary) {
                    $structures->push([
                        'category' => $category,
                        'subcategory' => $subcategory,
                        'auxiliary' => $auxiliary,
                    ]);
                }
            }
        }

        if ($structures->isEmpty()) {
            return;
        }

        $paymentMethods = ['cash', 'bank_transfer', 'credit_card', 'debit_card', 'other'];
        $user = $project->company?->users()->where('username', 'camilomorales')->first();

        for ($i = 1; $i <= 15; $i++) {
            $structure = $structures[($i - 1) % $structures->count()];
            $provider = $providers[($i - 1) % $providers->count()];
            $subtotal = 150000 + ($projectIndex * 50000) + ($i * 37500);
            $tax = (int) round($subtotal * 0.19);
            $discount = $i % 4 === 0 ? 10000 : 0;

            Expense::query()->updateOrCreate(
                [
                    'project_id' => $project->id,
                    'expense_number' => sprintf('%s-%03d', strtoupper(substr($project->name, 0, 3)), $i),
                ],
                [
                    'company_id' => $companyId,
                    'category_id' => $structure['category']->id,
                    'subcategory_id' => $structure['subcategory']->id,
                    'auxiliary_id' => $structure['auxiliary']->id,
                    'provider_id' => $provider->id,
                    'created_by' => $user?->id,
                    'expense_date' => $project->start_date?->copy()->addDays($i * 2) ?? now()->subDays(30 - $i),
                    'payment_method' => $paymentMethods[($i - 1) % count($paymentMethods)],
                    'description' => sprintf(
                        'Gasto demo %02d de %s para %s / %s / %s.',
                        $i,
                        $project->name,
                        $structure['category']->name,
                        $structure['subcategory']->name,
                        $structure['auxiliary']->name
                    ),
                    'subtotal_amount' => $subtotal,
                    'tax_amount' => $tax,
                    'discount_amount' => $discount,
                    'total_amount' => $subtotal + $tax - $discount,
                    'status' => EntityStatus::Active->value,
                ]
            );
        }
    }
}
