<?php

namespace Tests\Feature;

use App\Enums\EntityStatus;
use App\Enums\SystemRole;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_company_status_endpoint_rejects_deleted_transitions(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $company = $this->createCompany();

        $response = $this->actingAs($superAdmin)->patchJson(route('companies.status', $company), [
            'status' => EntityStatus::Deleted->value,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_company_can_be_archived_when_only_deleted_dependencies_exist(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $company = $this->createCompany();

        User::factory()->create([
            'company_id' => $company->id,
            'status' => EntityStatus::Deleted->value,
        ])->syncRoles([SystemRole::Viewer->value]);

        $response = $this->actingAs($superAdmin)->delete(route('companies.destroy', $company));

        $response->assertRedirect(route('companies.index'));
        $this->assertSame(EntityStatus::Deleted->value, $company->fresh()->status);
    }

    public function test_user_status_endpoint_rejects_deleted_transitions(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $company = $this->createCompany();
        $managedUser = User::factory()->create([
            'company_id' => $company->id,
            'status' => EntityStatus::Active->value,
        ]);
        $managedUser->syncRoles([SystemRole::Operator->value]);

        $response = $this->actingAs($superAdmin)->patchJson(route('users.status', $managedUser), [
            'status' => EntityStatus::Deleted->value,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_weak_password_is_rejected_when_creating_users(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $company = $this->createCompany();

        $response = $this->actingAs($superAdmin)->from(route('users.create'))->post(route('users.store'), [
            'company_id' => $company->id,
            'username' => 'operador.seguro',
            'name' => 'Operador Seguro',
            'email' => 'operador@example.com',
            'password' => '123456',
            'password_confirmation' => '123456',
            'status' => EntityStatus::Active->value,
            'role' => SystemRole::Operator->value,
        ]);

        $response->assertRedirect(route('users.create'));
        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('users', ['username' => 'operador.seguro']);
    }

    protected function createSuperAdmin(): User
    {
        $user = User::factory()->create([
            'company_id' => null,
            'status' => EntityStatus::Active->value,
        ]);

        $user->syncRoles([SystemRole::SuperAdmin->value]);

        return $user;
    }

    protected function createCompany(): Company
    {
        return Company::query()->create([
            'name' => 'Empresa QA',
            'legal_name' => 'Empresa QA SAS',
            'nit' => fake()->unique()->numerify('NIT-#####'),
            'email' => 'qa@example.com',
            'phone' => '3000000000',
            'primary_color' => '#111827',
            'status' => EntityStatus::Active->value,
        ]);
    }
}
