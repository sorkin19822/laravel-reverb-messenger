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
        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::create([
            'sender_id'   => $sender->id,
            'receiver_id' => $receiver->id,
            'body'        => 'Test message',
        ]);

        $this->assertInstanceOf(BelongsTo::class, $message->sender());
        $this->assertInstanceOf(User::class, $message->sender);
        $this->assertEquals($sender->id, $message->sender->id);
    }

    public function test_message_belongs_to_receiver(): void
    {
        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::create([
            'sender_id'   => $sender->id,
            'receiver_id' => $receiver->id,
            'body'        => 'Test message',
        ]);

        $this->assertInstanceOf(BelongsTo::class, $message->receiver());
        $this->assertInstanceOf(User::class, $message->receiver);
        $this->assertEquals($receiver->id, $message->receiver->id);
    }

    public function test_is_read_cast_to_boolean(): void
    {
        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::create([
            'sender_id'   => $sender->id,
            'receiver_id' => $receiver->id,
            'body'        => 'Test message',
            'is_read'     => false,
        ]);

        $this->assertIsBool($message->is_read);
        $this->assertFalse($message->is_read);

        $message->update(['is_read' => true]);
        $message->refresh();

        $this->assertIsBool($message->is_read);
        $this->assertTrue($message->is_read);
    }

    public function test_fillable_fields(): void
    {
        $message  = new Message();
        $fillable = $message->getFillable();

        $this->assertContains('sender_id', $fillable);
        $this->assertContains('receiver_id', $fillable);
        $this->assertContains('body', $fillable);
        $this->assertContains('is_read', $fillable);
    }
}
