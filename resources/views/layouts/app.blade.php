<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Messenger') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; color: #333; }
        .navbar {
            background: #4f46e5;
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 18px;
        }
        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }
        .navbar form button {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.4);
            color: white;
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .navbar form button:hover {
            background: rgba(255,255,255,0.3);
        }
        .container { max-width: 960px; margin: 20px auto; padding: 0 16px; }
        .card {
            background: white;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="{{ route('users.index') }}">Messenger</a>
    @auth
    <div class="user-info">
        <span>{{ Auth::user()->name }}</span>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </div>
    @endauth
</nav>
<div class="container">
    @yield('content')
</div>
@stack('scripts')
</body>
</html>
