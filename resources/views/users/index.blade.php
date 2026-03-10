@extends('layouts.app')
@section('content')
<div class="card">
    <h2 style="margin-bottom:20px;font-size:20px;">Users</h2>

    @if($users->isEmpty())
        <p style="color:#6b7280;font-size:14px;">No other users registered yet.</p>
    @else
        <ul style="list-style:none;">
            @foreach($users as $user)
            @php $unread = $unreadCounts[$user->id] ?? 0; @endphp
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
                    data-user-id="{{ $user->id }}"
                    style="background:#4f46e5;color:white;padding:8px 16px;border-radius:20px;text-decoration:none;font-size:14px;display:flex;align-items:center;gap:6px;white-space:nowrap;"
                >
                    Message
                    <span
                        id="badge-{{ $user->id }}"
                        style="background:#ef4444;border-radius:50%;width:20px;height:20px;display:{{ $unread > 0 ? 'inline-flex' : 'none' }};align-items:center;justify-content:center;font-size:11px;font-weight:bold;"
                    >{{ $unread > 99 ? '99+' : ($unread ?: '') }}</span>
                </a>
            </li>
            @endforeach
        </ul>
    @endif
</div>
@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"
        integrity="sha384-gA0TPBlnosOv77mNKhqDqUd7BMOqU7f5VlaEGFdyCus4A5l7JHELZ4K5dQMBSL1j"
        crossorigin="anonymous"></script>
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const pusher = new Pusher('{{ config("reverb.client.key") }}', {
        wsHost:            '{{ config("reverb.client.host") }}',
        wsPort:            {{ config("reverb.client.port") }},
        wssPort:           {{ config("reverb.client.port") }},
        forceTLS:          {{ config("reverb.client.scheme") === "https" ? "true" : "false" }},
        enabledTransports: ['ws', 'wss'],
        cluster:           'mt1',
        authEndpoint:      '/broadcasting/auth',
        auth: { headers: { 'X-CSRF-TOKEN': csrfToken } }
    });

    const channel = pusher.subscribe('private-chat.{{ Auth::id() }}');

    channel.bind('App\\Events\\MessageSent', function (data) {
        const badge = document.getElementById('badge-' + data.sender_id);
        if (!badge) return;

        const current = parseInt(badge.textContent) || 0;
        const next    = current + 1;
        badge.textContent    = next > 99 ? '99+' : next;
        badge.style.display  = 'inline-flex';
    });
</script>
@endpush
@endsection
