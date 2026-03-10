<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('id', '!=', Auth::id())->orderBy('name')->get();

        $unreadCounts = Message::where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->selectRaw('sender_id, count(*) as unread')
            ->groupBy('sender_id')
            ->pluck('unread', 'sender_id');

        return view('users.index', compact('users', 'unreadCounts'));
    }
}
