<?php

namespace Tests\Feature\Modules\Messaging;

use App\Models\User;
use App\Modules\Messaging\Models\Conversation;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class MessagingApiTest extends TenantTestCase
{
    // La messagerie est un service transversal, non gated par module.active.
    protected static array $modulesToActivate = [];

    protected User $userA;
    protected User $userB;
    protected User $stranger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create();
        $this->userB = User::factory()->create();
        $this->stranger = User::factory()->create();
    }

    protected function actingAsHeaders(User $user): self
    {
        Sanctum::actingAs($user);

        return $this->withHeader('X-Tenant', $this->tenant->slug);
    }

    public function test_it_creates_a_conversation_with_the_creator_as_owner(): void
    {
        $response = $this->actingAsHeaders($this->userA)->postJson('/api/messaging/conversations', [
            'participants' => [$this->userB->id],
            'subject' => 'Question réservation',
        ]);

        $response->assertCreated();

        $conversationId = $response->json('data.id');

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userA->id,
            'role' => 'owner',
        ], 'tenant');

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userB->id,
            'role' => 'member',
        ], 'tenant');
    }

    public function test_a_non_participant_cannot_view_the_conversation(): void
    {
        $conversation = $this->createConversation($this->userA, [$this->userB->id]);

        $response = $this->actingAsHeaders($this->stranger)->getJson("/api/messaging/conversations/{$conversation->id}");

        $response->assertStatus(403);
    }

    public function test_a_participant_can_send_and_read_a_message(): void
    {
        $conversation = $this->createConversation($this->userA, [$this->userB->id]);

        $response = $this->actingAsHeaders($this->userA)->postJson(
            "/api/messaging/conversations/{$conversation->id}/messages",
            ['content' => 'Bonjour !']
        );

        $response->assertCreated()->assertJsonPath('data.content', 'Bonjour !');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->userA->id,
            'content' => 'Bonjour !',
        ], 'tenant');
    }

    public function test_a_non_participant_cannot_send_a_message(): void
    {
        $conversation = $this->createConversation($this->userA, [$this->userB->id]);

        $response = $this->actingAsHeaders($this->stranger)->postJson(
            "/api/messaging/conversations/{$conversation->id}/messages",
            ['content' => 'Je m\'invite']
        );

        $response->assertStatus(403);

        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'content' => 'Je m\'invite',
        ], 'tenant');
    }

    public function test_sending_a_message_in_a_closed_conversation_is_rejected(): void
    {
        $conversation = $this->createConversation($this->userA, [$this->userB->id]);
        $conversation->close();

        $response = $this->actingAsHeaders($this->userA)->postJson(
            "/api/messaging/conversations/{$conversation->id}/messages",
            ['content' => 'Encore là ?']
        );

        $response->assertStatus(422);
    }

    public function test_only_owner_or_admin_can_close_the_conversation(): void
    {
        $conversation = $this->createConversation($this->userA, [$this->userB->id]);

        $response = $this->actingAsHeaders($this->userB)->postJson("/api/messaging/conversations/{$conversation->id}/close");

        $response->assertStatus(403);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'closed_at' => null,
        ], 'tenant');
    }

    public function test_owner_can_remove_a_participant_and_that_participant_loses_access(): void
    {
        $conversation = $this->createConversation($this->userA, [$this->userB->id]);

        $response = $this->actingAsHeaders($this->userA)
            ->deleteJson("/api/messaging/conversations/{$conversation->id}/participants/{$this->userB->id}");

        $response->assertOk();

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $this->userB->id,
        ], 'tenant');

        // Retiré : ne doit plus pouvoir accéder à la conversation.
        $access = $this->actingAsHeaders($this->userB)->getJson("/api/messaging/conversations/{$conversation->id}");
        $access->assertStatus(403);
    }

    public function test_a_removed_participant_can_be_reinvited_without_error(): void
    {
        $conversation = $this->createConversation($this->userA, [$this->userB->id]);

        $this->actingAsHeaders($this->userA)
            ->deleteJson("/api/messaging/conversations/{$conversation->id}/participants/{$this->userB->id}");

        $response = $this->actingAsHeaders($this->userA)->postJson(
            "/api/messaging/conversations/{$conversation->id}/participants",
            ['user_id' => $this->userB->id]
        );

        $response->assertCreated();

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $this->userB->id,
            'left_at' => null,
        ], 'tenant');
    }

    public function test_marking_a_conversation_as_read_updates_the_participant(): void
    {
        $conversation = $this->createConversation($this->userA, [$this->userB->id]);

        $response = $this->actingAsHeaders($this->userB)->postJson("/api/messaging/conversations/{$conversation->id}/mark-read");

        $response->assertOk();

        $this->assertDatabaseMissing('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $this->userB->id,
            'last_read_at' => null,
        ], 'tenant');
    }

    protected function createConversation(User $creator, array $participantIds): Conversation
    {
        $conversation = Conversation::create([
            'subject' => 'Conversation test',
            'created_by' => $creator->id,
        ]);

        $conversation->addParticipant($creator->id, 'owner');
        foreach ($participantIds as $id) {
            $conversation->addParticipant($id, 'member');
        }

        return $conversation;
    }
}
