@extends('layouts.app')
@section('content')
<div class="card" style="padding:0;overflow:hidden;">

    {{-- Chat Header --}}
    <div style="background:#4f46e5;color:white;padding:16px 20px;display:flex;align-items:center;gap:12px;">
        <a href="{{ route('users.index') }}" style="color:white;text-decoration:none;font-size:20px;line-height:1;">&larr;</a>
        <div style="width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,0.25);display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:16px;flex-shrink:0;">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <strong style="font-size:16px;">{{ $user->name }}</strong>
            <div style="font-size:12px;opacity:0.8;">{{ $user->email }}</div>
        </div>
        <div id="connection-status" style="margin-left:auto;font-size:12px;opacity:0.8;">Connecting...</div>
    </div>

    {{-- Messages Container --}}
    <div
        id="messages"
        style="height:480px;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:10px;background:#f8fafc;"
    >
        @forelse($messages as $message)
        @php $isMine = $message->sender_id === Auth::id(); @endphp
        <div class="message-row" style="display:flex;justify-content:{{ $isMine ? 'flex-end' : 'flex-start' }};">
            <div style="max-width:65%;background:{{ $isMine ? '#4f46e5' : 'white' }};color:{{ $isMine ? 'white' : '#333' }};padding:10px 14px;border-radius:{{ $isMine ? '16px 16px 4px 16px' : '16px 16px 16px 4px' }};box-shadow:0 1px 2px rgba(0,0,0,0.08);">
                @if(!$isMine)
                    <div style="font-size:11px;font-weight:700;margin-bottom:4px;opacity:0.7;">{{ $message->sender->name }}</div>
                @endif
                <p style="margin-bottom:4px;font-size:14px;line-height:1.5;word-break:break-word;">{{ $message->body }}</p>
                <small style="opacity:0.65;font-size:11px;">{{ $message->created_at->format('H:i d.m.Y') }}</small>
            </div>
        </div>
        @empty
        <div id="empty-state" style="text-align:center;color:#9ca3af;font-size:14px;margin-top:40px;">
            No messages yet. Start the conversation!
        </div>
        @endforelse
    </div>

    {{-- Message Input --}}
    <div style="padding:16px 20px;border-top:1px solid #e5e7eb;display:flex;gap:10px;background:white;">
        <input
            id="messageInput"
            type="text"
            placeholder="Type a message..."
            maxlength="5000"
            style="flex:1;padding:12px 16px;border:1px solid #d1d5db;border-radius:24px;outline:none;font-size:14px;"
            onkeydown="if(event.key==='Enter' && !event.shiftKey){ event.preventDefault(); sendMessage(); }"
        >
        <button
            onclick="sendMessage()"
            id="sendBtn"
            style="background:#4f46e5;color:white;border:none;padding:12px 22px;border-radius:24px;cursor:pointer;font-size:14px;font-weight:600;white-space:nowrap;"
        >
            Send
        </button>
    </div>
</div>

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const currentUserId = {{ Auth::id() }};
    const receiverId    = {{ $user->id }};
    const csrfToken     = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const messagesBox   = document.getElementById('messages');
    const statusEl      = document.getElementById('connection-status');
    const sendBtn       = document.getElementById('sendBtn');

    // Scroll to bottom on page load
    messagesBox.scrollTop = messagesBox.scrollHeight;

    // -----------------------------------------------------------------------
    // Pusher / Reverb setup
    // -----------------------------------------------------------------------
    const pusher = new Pusher('{{ config("broadcasting.connections.reverb.key") }}', {
        wsHost:            '{{ env("REVERB_HOST", "localhost") }}',
        wsPort:            {{ env("REVERB_PORT", 8081) }},
        wssPort:           {{ env("REVERB_PORT", 8081) }},
        forceTLS:          {{ env("REVERB_SCHEME", "http") === "https" ? "true" : "false" }},
        enabledTransports: ['ws', 'wss'],
        cluster:           'mt1',
        authEndpoint:      '/broadcasting/auth',
        auth: {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }
    });

    pusher.connection.bind('connected', () => {
        statusEl.textContent = 'Connected';
        statusEl.style.color = '#86efac';
    });

    pusher.connection.bind('disconnected', () => {
        statusEl.textContent = 'Disconnected';
        statusEl.style.color = '#fca5a5';
    });

    pusher.connection.bind('error', (err) => {
        console.error('Pusher connection error:', err);
        statusEl.textContent = 'Connection error';
        statusEl.style.color = '#fca5a5';
    });

    // Subscribe to private channel for the current logged-in user
    const channel = pusher.subscribe('private-chat.' + currentUserId);

    channel.bind('App\\Events\\MessageSent', function (data) {
        // Only display message if it comes from the user we are currently chatting with
        if (parseInt(data.sender_id) === parseInt(receiverId)) {
            appendMessage(data, false);
        }
    });

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------
    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function appendMessage(data, isMine) {
        // Remove empty-state placeholder if present
        const empty = document.getElementById('empty-state');
        if (empty) empty.remove();

        const row    = document.createElement('div');
        row.className = 'message-row';
        row.style.cssText = 'display:flex;justify-content:' + (isMine ? 'flex-end' : 'flex-start') + ';';

        const bubble = document.createElement('div');
        bubble.style.cssText = [
            'max-width:65%;',
            'background:'   + (isMine ? '#4f46e5' : 'white') + ';',
            'color:'        + (isMine ? 'white'   : '#333')   + ';',
            'padding:10px 14px;',
            'border-radius:' + (isMine ? '16px 16px 4px 16px' : '16px 16px 16px 4px') + ';',
            'box-shadow:0 1px 2px rgba(0,0,0,0.08);',
        ].join('');

        let inner = '';
        if (!isMine) {
            inner += '<div style="font-size:11px;font-weight:700;margin-bottom:4px;opacity:0.7;">' + escapeHtml(data.sender_name) + '</div>';
        }
        inner += '<p style="margin-bottom:4px;font-size:14px;line-height:1.5;word-break:break-word;">' + escapeHtml(data.body) + '</p>';
        inner += '<small style="opacity:0.65;font-size:11px;">' + escapeHtml(data.created_at) + '</small>';

        bubble.innerHTML = inner;
        row.appendChild(bubble);
        messagesBox.appendChild(row);
        messagesBox.scrollTop = messagesBox.scrollHeight;
    }

    // -----------------------------------------------------------------------
    // Send message via fetch
    // -----------------------------------------------------------------------
    function sendMessage() {
        const input = document.getElementById('messageInput');
        const body  = input.value.trim();
        if (!body) return;

        sendBtn.disabled = true;
        sendBtn.textContent = '...';

        fetch('/chat/' + receiverId + '/messages', {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  csrfToken,
                'Accept':        'application/json',
            },
            body: JSON.stringify({ body }),
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            appendMessage(data, true);
            input.value = '';
        })
        .catch(err => {
            console.error('Send error:', err);
            alert('Failed to send message. Please try again.');
        })
        .finally(() => {
            sendBtn.disabled = false;
            sendBtn.textContent = 'Send';
            input.focus();
        });
    }
</script>
@endpush
@endsection
