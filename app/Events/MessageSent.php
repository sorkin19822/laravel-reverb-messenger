<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->receiver_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->message->id,
            'body'         => $this->message->body,
            'sender_id'    => $this->message->sender_id,
            'sender_name'  => $this->message->sender->name,
            'created_at'   => $this->message->created_at->format('H:i d.m.Y'),
        ];
    }
}
