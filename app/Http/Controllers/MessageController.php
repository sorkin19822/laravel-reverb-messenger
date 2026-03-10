<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index(User $user)
    {
        abort_if($user->id === Auth::id(), 403);

        $messages = DB::transaction(function () use ($user) {
            $messages = Message::where(function ($q) use ($user) {
                $q->where('sender_id', Auth::id())
                  ->where('receiver_id', $user->id);
            })->orWhere(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                  ->where('receiver_id', Auth::id());
            })->with('sender')
              ->orderBy('created_at')
              ->get();

            Message::where('sender_id', $user->id)
                ->where('receiver_id', Auth::id())
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return $messages;
        });

        return view('messages.chat', compact('user', 'messages'));
    }

    public function store(Request $request, User $user)
    {
        abort_if($user->id === Auth::id(), 403);

        $request->validate(['body' => 'required|string|max:5000']);

        $message = Message::make(['body' => $request->body]);
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
