<?php

namespace Tests\Feature\Modules\Hotel;

use App\Models\User;
use App\Modules\Hotel\Models\HousekeepingTask;
use App\Modules\Hotel\Models\Room;
use App\Modules\Hotel\Models\RoomType;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class HousekeepingTaskApiTest extends TenantTestCase
{
    protected User $user;
    protected RoomType $roomType;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->roomType = RoomType::factory()->create();
        $this->room = Room::factory()->create([
            'room_type_id' => $this->roomType->id,
            'status'       => 'cleaning',
        ]);
    }

    protected function headers(): self
    {
        return $this->withHeader('X-Tenant', $this->tenant->slug);
    }

    public function test_it_creates_a_housekeeping_task(): void
    {
        $response = $this->headers()->postJson('/api/hotel/housekeeping-tasks', [
            'room_id' => $this->room->id,
            'type'    => 'checkout_cleaning',
        ]);

        $response->assertCreated()
            ->assertJsonPath('type', 'checkout_cleaning')
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('room.id', $this->room->id);

        $this->assertDatabaseHas('housekeeping_tasks', [
            'room_id' => $this->room->id,
            'type'    => 'checkout_cleaning',
        ], 'tenant');
    }

    public function test_it_rejects_invalid_task_type(): void
    {
        $response = $this->headers()->postJson('/api/hotel/housekeeping-tasks', [
            'room_id' => $this->room->id,
            'type'    => 'deep_clean_spa', // hors enum autorisé
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['type']);
    }

    public function test_it_assigns_a_task_to_a_user(): void
    {
        $task = HousekeepingTask::create([
            'room_id' => $this->room->id,
            'type'    => 'daily_cleaning',
            'status'  => 'pending',
        ]);

        $assignee = User::factory()->create();

        $response = $this->headers()->postJson(
            "/api/hotel/housekeeping-tasks/{$task->id}/assign",
            ['assigned_to' => $assignee->id]
        );

        $response->assertOk()->assertJsonPath('assigned_to.id', $assignee->id);
    }

    public function test_it_rejects_assign_to_nonexistent_user(): void
    {
        $task = HousekeepingTask::create(['room_id' => $this->room->id, 'type' => 'daily_cleaning']);

        $response = $this->headers()->postJson(
            "/api/hotel/housekeeping-tasks/{$task->id}/assign",
            ['assigned_to' => (string) \Illuminate\Support\Str::uuid()]
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['assigned_to']);
    }

    public function test_it_starts_a_task(): void
    {
        $task = HousekeepingTask::create([
            'room_id' => $this->room->id,
            'type'    => 'daily_cleaning',
            'status'  => 'pending',
        ]);

        $response = $this->headers()->postJson("/api/hotel/housekeeping-tasks/{$task->id}/start");

        $response->assertOk()
            ->assertJsonPath('status', 'in_progress');

        $this->assertNotNull($response->json('started_at'));
    }

    public function test_completing_a_checkout_cleaning_task_frees_the_room(): void
    {
        $task = HousekeepingTask::create([
            'room_id' => $this->room->id,
            'type'    => 'checkout_cleaning',
            'status'  => 'in_progress',
        ]);

        $response = $this->headers()->postJson("/api/hotel/housekeeping-tasks/{$task->id}/complete");

        $response->assertOk()->assertJsonPath('status', 'completed');

        $this->assertDatabaseHas('rooms', [
            'id'     => $this->room->id,
            'status' => 'available',
        ], 'tenant');
    }

    public function test_completing_an_inspection_task_does_not_change_room_status(): void
    {
        $task = HousekeepingTask::create([
            'room_id' => $this->room->id,
            'type'    => 'inspection',
            'status'  => 'in_progress',
        ]);

        $this->headers()->postJson("/api/hotel/housekeeping-tasks/{$task->id}/complete");

        // Le statut de la chambre ('cleaning', fixé dans setUp) ne doit pas bouger
        // pour un type de tâche autre que checkout_cleaning/daily_cleaning.
        $this->assertDatabaseHas('rooms', [
            'id'     => $this->room->id,
            'status' => 'cleaning',
        ], 'tenant');
    }

    public function test_it_lists_tasks_filtered_by_status(): void
    {
        HousekeepingTask::create(['room_id' => $this->room->id, 'type' => 'daily_cleaning', 'status' => 'pending']);
        HousekeepingTask::create(['room_id' => $this->room->id, 'type' => 'daily_cleaning', 'status' => 'completed']);

        $response = $this->headers()->getJson('/api/hotel/housekeeping-tasks?status=completed');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
