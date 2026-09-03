<?php

namespace Tests\Feature\Modules\Hr;

use App\Models\User;
use App\Modules\Hr\Models\Employee;
use App\Modules\Hr\Models\LeaveRequest;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class LeaveRequestApiTest extends TenantTestCase
{
    protected static array $modulesToActivate = ['hr'];

    protected User $user;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->employee = Employee::create(['first_name' => 'Nadia', 'last_name' => 'Bakayoko']);
    }

    protected function headers(): self
    {
        return $this->withHeader('X-Tenant', $this->tenant->slug);
    }

    public function test_it_creates_a_leave_request_and_computes_days_count(): void
    {
        $response = $this->headers()->postJson("/api/hr/employees/{$this->employee->id}/leave-requests", [
            'type'       => 'annual',
            'start_date' => '2026-09-01',
            'end_date'   => '2026-09-05', // 5 jours inclus
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('days_count', 5)
            ->assertJsonPath('employee.id', $this->employee->id);
    }

    public function test_it_rejects_end_date_before_start_date(): void
    {
        $response = $this->headers()->postJson("/api/hr/employees/{$this->employee->id}/leave-requests", [
            'type'       => 'annual',
            'start_date' => '2026-09-10',
            'end_date'   => '2026-09-05',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['end_date']);
    }

    public function test_it_rejects_invalid_leave_type(): void
    {
        $response = $this->headers()->postJson("/api/hr/employees/{$this->employee->id}/leave-requests", [
            'type'       => 'sabbatical', // hors enum autorisé
            'start_date' => '2026-09-01',
            'end_date'   => '2026-09-05',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['type']);
    }

    public function test_it_approves_a_leave_request(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'type'        => 'sick',
            'start_date'  => '2026-09-01',
            'end_date'    => '2026-09-02',
            'days_count'  => 2,
            'status'      => 'pending',
        ]);

        $response = $this->headers()->postJson("/api/hr/leave-requests/{$leave->id}/approve");

        $response->assertOk()
            ->assertJsonPath('status', 'approved')
            ->assertJsonPath('approved_by.id', $this->user->id);

        $this->assertNotNull($response->json('approved_at'));
    }

    public function test_it_rejects_a_leave_request(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'type'        => 'unpaid',
            'start_date'  => '2026-09-01',
            'end_date'    => '2026-09-10',
            'days_count'  => 10,
            'status'      => 'pending',
        ]);

        $response = $this->headers()->postJson("/api/hr/leave-requests/{$leave->id}/reject");

        $response->assertOk()->assertJsonPath('status', 'rejected');
    }

    public function test_it_cancels_a_leave_request(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'type'        => 'annual',
            'start_date'  => '2026-09-01',
            'end_date'    => '2026-09-03',
            'days_count'  => 3,
            'status'      => 'pending',
        ]);

        $response = $this->headers()->postJson("/api/hr/leave-requests/{$leave->id}/cancel");

        $response->assertOk()->assertJsonPath('status', 'cancelled');
    }

    public function test_it_lists_leave_requests_filtered_by_status(): void
    {
        LeaveRequest::create([
            'employee_id' => $this->employee->id, 'type' => 'annual',
            'start_date'  => '2026-09-01', 'end_date' => '2026-09-02',
            'days_count'  => 2, 'status' => 'pending',
        ]);
        LeaveRequest::create([
            'employee_id' => $this->employee->id, 'type' => 'sick',
            'start_date'  => '2026-08-01', 'end_date' => '2026-08-02',
            'days_count'  => 2, 'status' => 'approved',
        ]);

        $response = $this->headers()->getJson('/api/hr/leave-requests?status=approved');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_it_filters_leave_requests_by_employee(): void
    {
        $otherEmployee = Employee::create(['first_name' => 'Autre', 'last_name' => 'Personne']);

        LeaveRequest::create([
            'employee_id' => $this->employee->id, 'type' => 'annual',
            'start_date'  => '2026-09-01', 'end_date' => '2026-09-02',
            'days_count'  => 2, 'status' => 'pending',
        ]);
        LeaveRequest::create([
            'employee_id' => $otherEmployee->id, 'type' => 'annual',
            'start_date'  => '2026-09-01', 'end_date' => '2026-09-02',
            'days_count'  => 2, 'status' => 'pending',
        ]);

        $response = $this->headers()->getJson("/api/hr/leave-requests?employee_id={$this->employee->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
