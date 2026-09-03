<?php

namespace Tests\Feature\Modules\Hr;

use App\Models\User;
use App\Modules\Hr\Models\Employee;
use App\Modules\Hr\Models\EmployeeContract;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class EmployeeApiTest extends TenantTestCase
{
    protected static array $modulesToActivate = ['hr'];

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    protected function headers(): self
    {
        return $this->withHeader('X-Tenant', $this->tenant->slug);
    }

    // ---------------------------------------------------------------
    // Employee CRUD
    // ---------------------------------------------------------------

    public function test_it_creates_an_employee(): void
    {
        $response = $this->headers()->postJson('/api/hr/employees', [
            'first_name' => 'Awa',
            'last_name'  => 'Traoré',
            'email'      => 'awa.traore@example.com',
            'position'   => 'Réceptionniste',
            'department' => 'Accueil',
            'hire_date'  => '2026-01-15',
        ]);

        $response->assertCreated()
            ->assertJsonPath('full_name', 'Awa Traoré')
            ->assertJsonPath('position', 'Réceptionniste');

        $this->assertDatabaseHas('employees', ['email' => 'awa.traore@example.com'], 'tenant');
    }

    public function test_it_rejects_employee_without_required_names(): void
    {
        $response = $this->headers()->postJson('/api/hr/employees', [
            'email' => 'sans-nom@example.com',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    public function test_it_shows_an_employee_with_active_contract(): void
    {
        $employee = Employee::create(['first_name' => 'Koffi', 'last_name' => 'Adjei']);
        EmployeeContract::create([
            'employee_id' => $employee->id,
            'type'        => 'cdi',
            'start_date'  => '2026-01-01',
            'status'      => 'active',
        ]);

        $response = $this->headers()->getJson("/api/hr/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonPath('id', $employee->id)
            ->assertJsonPath('active_contract.type', 'cdi');
    }

    public function test_it_searches_employees_by_name(): void
    {
        Employee::create(['first_name' => 'Fatou', 'last_name' => 'Bamba']);
        Employee::create(['first_name' => 'Ibrahim', 'last_name' => 'Cissé']);

        $response = $this->headers()->getJson('/api/hr/employees?search=Fatou');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_it_filters_employees_by_department(): void
    {
        Employee::create(['first_name' => 'A', 'last_name' => 'B', 'department' => 'Cuisine']);
        Employee::create(['first_name' => 'C', 'last_name' => 'D', 'department' => 'Accueil']);

        $response = $this->headers()->getJson('/api/hr/employees?department=Cuisine');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_it_updates_an_employee(): void
    {
        $employee = Employee::create(['first_name' => 'Yao', 'last_name' => 'Kouassi', 'position' => 'Serveur']);

        $response = $this->headers()->putJson("/api/hr/employees/{$employee->id}", [
            'first_name' => 'Yao',
            'last_name'  => 'Kouassi',
            'position'   => 'Chef de rang',
        ]);

        $response->assertOk()->assertJsonPath('position', 'Chef de rang');
    }

    public function test_it_deletes_an_employee(): void
    {
        $employee = Employee::create(['first_name' => 'Temp', 'last_name' => 'Oraire']);

        $response = $this->headers()->deleteJson("/api/hr/employees/{$employee->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('employees', ['id' => $employee->id], 'tenant');
    }

    // ---------------------------------------------------------------
    // EmployeeContract
    // ---------------------------------------------------------------

    public function test_it_creates_a_contract_for_an_employee(): void
    {
        $employee = Employee::create(['first_name' => 'Aïssata', 'last_name' => 'Diallo']);

        $response = $this->headers()->postJson("/api/hr/employees/{$employee->id}/contracts", [
            'type'       => 'cdd',
            'start_date' => '2026-03-01',
            'end_date'   => '2026-08-31',
            'salary'     => 250000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('type', 'cdd')
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('salary', 250000);
    }

    public function test_creating_a_new_active_contract_ends_the_previous_one(): void
    {
        $employee = Employee::create(['first_name' => 'Salif', 'last_name' => 'Keita']);

        $oldContract = EmployeeContract::create([
            'employee_id' => $employee->id,
            'type'        => 'cdd',
            'start_date'  => '2025-01-01',
            'status'      => 'active',
        ]);

        $this->headers()->postJson("/api/hr/employees/{$employee->id}/contracts", [
            'type'       => 'cdi',
            'start_date' => '2026-01-01',
        ]);

        $this->assertDatabaseHas('employee_contracts', [
            'id'     => $oldContract->id,
            'status' => 'ended',
        ], 'tenant');
    }

    public function test_it_rejects_contract_with_end_date_before_start_date(): void
    {
        $employee = Employee::create(['first_name' => 'Test', 'last_name' => 'Contrat']);

        $response = $this->headers()->postJson("/api/hr/employees/{$employee->id}/contracts", [
            'type'       => 'cdd',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-01-01',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['end_date']);
    }

    public function test_it_terminates_a_contract(): void
    {
        $employee = Employee::create(['first_name' => 'Marc', 'last_name' => 'Dupont']);
        $contract = EmployeeContract::create([
            'employee_id' => $employee->id,
            'type'        => 'cdi',
            'start_date'  => '2026-01-01',
            'status'      => 'active',
        ]);

        $response = $this->headers()->postJson(
            "/api/hr/employees/{$employee->id}/contracts/{$contract->id}/terminate"
        );

        $response->assertOk()->assertJsonPath('status', 'terminated');
    }

    public function test_it_lists_contracts_for_an_employee_ordered_by_start_date(): void
    {
        $employee = Employee::create(['first_name' => 'Historique', 'last_name' => 'Contrats']);

        EmployeeContract::create([
            'employee_id' => $employee->id, 'type' => 'stage',
            'start_date'  => '2024-01-01', 'status' => 'ended',
        ]);
        EmployeeContract::create([
            'employee_id' => $employee->id, 'type' => 'cdi',
            'start_date'  => '2026-01-01', 'status' => 'active',
        ]);

        $response = $this->headers()->getJson("/api/hr/employees/{$employee->id}/contracts");

        $response->assertOk();
        $this->assertSame('cdi', $response->json('data.0.type'));
    }
}
