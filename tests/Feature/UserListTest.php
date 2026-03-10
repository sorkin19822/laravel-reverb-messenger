<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserListTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_user_list(): void
    {
        $currentUser = User::factory()->create();
        $otherUser   = User::factory()->create(['name' => 'Jane Doe']);

        $response = $this->actingAs($currentUser)->get('/');

        $response->assertStatus(200);
        $response->assertSee($otherUser->name);
    }

    public function test_current_user_not_in_list(): void
    {
        $currentUser = User::factory()->create([
            'name'  => 'CurrentUser',
            'email' => 'currentuser@uniquedomain.test',
        ]);
        User::factory()->create(['name' => 'Other Person']);

        $response = $this->actingAs($currentUser)->get('/');

        $response->assertStatus(200);

        // The navbar renders the authenticated user's name, but the user-list
        // section renders each user's email address in a sub-row. We assert
        // the current user's email is absent from the page to confirm their
        // row was excluded from the list.
        $response->assertDontSee('currentuser@uniquedomain.test');
    }

    public function test_unread_count_shown_for_user_with_messages(): void
    {
        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        Message::factory()->create([
            'sender_id'   => $sender->id,
            'receiver_id' => $receiver->id,
            'body'        => 'Unread message',
            'is_read'     => false,
        ]);

        $response = $this->actingAs($receiver)->get('/');

        $response->assertStatus(200);
        // Badge renders only when unread > 0 — assert the badge element appears
        $response->assertSee('background:#ef4444', false);
    }
}
