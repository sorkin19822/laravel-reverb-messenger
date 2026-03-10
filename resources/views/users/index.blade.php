@extends('layouts.app')
@section('content')
<div class="card">
    <h2 style="margin-bottom:20px;font-size:20px;">Users</h2>

    @if($users->isEmpty())
        <p style="color:#6b7280;font-size:14px;">No other users registered yet.</p>
    @else
        <ul style="list-style:none;">
            @foreach($users as $user)
            @php
                $unread = \App\Models\Message::where('sender_id', $user->id)
                    ->where('receiver_id', Auth::id())
                    ->where('is_read', false)
                    ->count();
            @endphp
            <li style="padding:14px 0;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:#4f46e5;color:white;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:18px;flex-shrink:0;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <strong style="font-size:15px;">{{ $user->name }}</strong>
                        <div style="font-size:12px;color:#6b7280;margin-top:2px;">{{ $user->email }}</div>
                    </div>
                </div>
                <a
                    href="{{ route('messages.index', $user) }}"
                    style="background:#4f46e5;color:white;padding:8px 16px;border-radius:20px;text-decoration:none;font-size:14px;display:flex;align-items:center;gap:6px;white-space:nowrap;"
                >
                    Message
                    @if($unread > 0)
                        <span style="background:#ef4444;border-radius:50%;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:bold;">
                            {{ $unread > 99 ? '99+' : $unread }}
                        </span>
                    @endif
                </a>
            </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
