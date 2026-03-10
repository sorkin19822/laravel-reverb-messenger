<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display the list of all registered users except the authenticated user,
     * along with the unread message count from each user (for badge display).
     *
     * Unread counts are fetched in a single aggregated query to avoid N+1.
     */
    public function index(): View
    {
        $users = User::where('id', '!=', Auth::id())->orderBy('name')->get();

        /** @var Collection<int, int> $unreadCounts keyed by sender_id */
        $unreadCounts = Message::where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->selectRaw('sender_id, count(*) as unread')
            ->groupBy('sender_id')
            ->pluck('unread', 'sender_id');

        return view('users.index', compact('users', 'unreadCounts'));
    }
}
