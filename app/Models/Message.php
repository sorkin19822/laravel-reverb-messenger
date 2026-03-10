<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['body', 'is_read'];

    protected $casts = ['is_read' => 'boolean'];

    /**
     * The user who sent this message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * The user who received this message.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Fetch the full conversation between two users in chronological order.
     *
     * @return Collection<int, Message>
     */
    public static function conversation(int $userAId, int $userBId): Collection
    {
        return self::where(function ($q) use ($userAId, $userBId): void {
            $q->where('sender_id', $userAId)->where('receiver_id', $userBId);
        })->orWhere(function ($q) use ($userAId, $userBId): void {
            $q->where('sender_id', $userBId)->where('receiver_id', $userAId);
        })->with('sender')->orderBy('created_at')->get();
    }

    /**
     * Mark all unread messages from a given sender as read.
     */
    public static function markAsRead(int $senderId, int $receiverId): void
    {
        self::where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
