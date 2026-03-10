<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_chat(): void
    {
        $user = User::factory()->create();

        $this->get("/chat/{$user->id}")->assertRedirect('/login');
    }

    public function test_user_can_view_chat(): void
    {
        $currentUser = User::factory()->create();
        $otherUser   = User::factory()->create();

        $this->actingAs($currentUser)->get("/chat/{$otherUser->id}")->assertStatus(200);
    }

    public function test_user_cannot_chat_with_themselves(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get("/chat/{$user->id}")->assertForbidden();
        $this->actingAs($user)
            ->postJson("/chat/{$user->id}/messages", ['body' => 'hi'])
            ->assertForbidden();
    }

    public function test_chat_shows_conversation_messages(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Message::factory()->create([
            'sender_id'   => $userA->id,
            'receiver_id' => $userB->id,
            'body'        => 'Hello from A to B',
        ]);

        Message::factory()->create([
            'sender_id'   => $userB->id,
            'receiver_id' => $userA->id,
            'body'        => 'Hello from B to A',
        ]);

        $response = $this->actingAs($userA)->get("/chat/{$userB->id}");

        $response->assertStatus(200);
        $response->assertSee('Hello from A to B');
        $response->assertSee('Hello from B to A');
    }

    public function test_chat_does_not_show_messages_from_other_conversations(): void
    {
        [$userA, $userB, $userC] = User::factory()->count(3)->create();

        Message::factory()->create([
            'sender_id'   => $userA->id,
            'receiver_id' => $userB->id,
            'body'        => 'Private message between A and B',
        ]);

        Message::factory()->create([
            'sender_id'   => $userB->id,
            'receiver_id' => $userC->id,
            'body'        => 'Private message between B and C',
        ]);

        $response = $this->actingAs($userA)->get("/chat/{$userC->id}");

        $response->assertStatus(200);
        $response->assertDontSee('Private message between A and B');
        $response->assertDontSee('Private message between B and C');
    }

    public function test_user_cannot_see_messages_from_unrelated_conversation(): void
    {
        [$userA, $userB, $userC] = User::factory()->count(3)->create();

        Message::factory()->create([
            'sender_id'   => $userA->id,
            'receiver_id' => $userB->id,
            'body'        => 'Secret A-B message',
        ]);

        $response = $this->actingAs($userC)->get("/chat/{$userA->id}");

        $response->assertStatus(200);
        $response->assertDontSee('Secret A-B message');
    }

    public function test_messages_marked_as_read_when_chat_opened(): void
    {
        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::factory()->create([
            'sender_id'   => $sender->id,
            'receiver_id' => $receiver->id,
            'body'        => 'Mark me as read',
            'is_read'     => false,
        ]);

        $this->actingAs($receiver)->get("/chat/{$sender->id}");

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'is_read' => true]);
    }

    public function test_user_can_send_message(): void
    {
        Event::fake();

        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        $this->actingAs($sender)
            ->postJson("/chat/{$receiver->id}/messages", ['body' => 'Hello there!'])
            ->assertStatus(200);

        $this->assertDatabaseHas('messages', [
            'sender_id'   => $sender->id,
            'receiver_id' => $receiver->id,
            'body'        => 'Hello there!',
        ]);
    }

    public function test_send_message_returns_correct_json_structure(): void
    {
        Event::fake();

        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        $this->actingAs($sender)
            ->postJson("/chat/{$receiver->id}/messages", ['body' => 'Checking JSON structure'])
            ->assertStatus(200)
            ->assertJsonStructure(['id', 'body', 'sender_id', 'sender_name', 'created_at'])
            ->assertJson([
                'body'        => 'Checking JSON structure',
                'sender_id'   => $sender->id,
                'sender_name' => $sender->name,
            ]);
    }

    public function test_send_message_fires_broadcast_event(): void
    {
        Event::fake();

        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        $this->actingAs($sender)
            ->postJson("/chat/{$receiver->id}/messages", ['body' => 'Broadcast this!']);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($sender, $receiver) {
            return $event->message->sender_id   === $sender->id
                && $event->message->receiver_id === $receiver->id
                && $event->message->body        === 'Broadcast this!';
        });
    }

    public function test_send_message_validates_body_required(): void
    {
        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        $this->actingAs($sender)
            ->postJson("/chat/{$receiver->id}/messages", ['body' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_send_message_validates_body_max_length(): void
    {
        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        $this->actingAs($sender)
            ->postJson("/chat/{$receiver->id}/messages", ['body' => str_repeat('a', 5001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }
}
