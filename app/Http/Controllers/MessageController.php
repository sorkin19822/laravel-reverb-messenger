<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Display the chat conversation between the authenticated user and the given user.
     *
     * Fetches messages in both directions, then atomically marks all incoming
     * messages as read to prevent a race condition where new messages arrive
     * between the fetch and the update.
     */
    public function index(User $user): View
    {
        abort_if($user->id === Auth::id(), 403);

        $messages = DB::transaction(function () use ($user) {
            $messages = Message::conversation(Auth::id(), $user->id);
            Message::markAsRead($user->id, Auth::id());
            return $messages;
        });

        return view('messages.chat', compact('user', 'messages'));
    }

    /**
     * Store a new message and broadcast it to the recipient via WebSocket.
     *
     * Returns the persisted message as JSON so the sender can append it
     * to the DOM immediately without waiting for the WebSocket echo.
     * toOthers() prevents the sender from receiving their own broadcast.
     */
    public function store(Request $request, User $user): JsonResponse
    {
        abort_if($user->id === Auth::id(), 403);

        $request->validate(['body' => 'required|string|max:5000']);

        $message              = Message::make(['body' => $request->input('body')]);
        $message->sender_id   = Auth::id();
        $message->receiver_id = $user->id;
        $message->save();

        $message->load('sender');

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'id'          => $message->id,
            'body'        => $message->body,
            'sender_id'   => $message->sender_id,
            'sender_name' => $message->sender->name,
            'created_at'  => $message->created_at->format('H:i d.m.Y'),
        ]);
    }
}
