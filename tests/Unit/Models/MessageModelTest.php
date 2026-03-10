<?php

namespace Tests\Unit\Models;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_belongs_to_sender(): void
    {
        $message = Message::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $message->sender());
        $this->assertInstanceOf(User::class, $message->sender);
    }

    public function test_message_belongs_to_receiver(): void
    {
        $message = Message::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $message->receiver());
        $this->assertInstanceOf(User::class, $message->receiver);
    }

    public function test_is_read_cast_to_boolean(): void
    {
        $message = Message::factory()->create(['is_read' => false]);

        $this->assertIsBool($message->is_read);
        $this->assertFalse($message->is_read);

        $message->update(['is_read' => true]);
        $this->assertTrue($message->fresh()->is_read);
    }

    public function test_fillable_fields(): void
    {
        $fillable = (new Message())->getFillable();

        $this->assertContains('body', $fillable);
        $this->assertContains('is_read', $fillable);
        $this->assertNotContains('sender_id', $fillable);
        $this->assertNotContains('receiver_id', $fillable);
    }
}
