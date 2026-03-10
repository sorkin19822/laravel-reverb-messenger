@extends('layouts.app')
@section('content')
<div class="card" style="max-width:400px;margin:40px auto;">
    <h2 style="margin-bottom:20px;">Login</h2>

    @if($errors->any())
        <div style="background:#fee2e2;border:1px solid #fca5a5;padding:10px 14px;border-radius:4px;margin-bottom:16px;">
            @foreach($errors->all() as $error)
                <p style="font-size:14px;color:#b91c1c;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div style="margin-bottom:16px;">
            <label style="display:block;margin-bottom:4px;font-size:14px;font-weight:600;">Email</label>
            <input
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autocomplete="email"
                style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:14px;outline:none;"
            >
        </div>
        <div style="margin-bottom:16px;">
            <label style="display:block;margin-bottom:4px;font-size:14px;font-weight:600;">Password</label>
            <input
                name="password"
                type="password"
                required
                autocomplete="current-password"
                style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:14px;outline:none;"
            >
        </div>
        <div style="margin-bottom:20px;display:flex;align-items:center;gap:8px;">
            <input type="checkbox" name="remember" id="remember" style="width:16px;height:16px;">
            <label for="remember" style="font-size:14px;cursor:pointer;">Remember me</label>
        </div>
        <button
            type="submit"
            style="width:100%;background:#4f46e5;color:white;padding:12px;border:none;border-radius:4px;cursor:pointer;font-size:16px;font-weight:600;"
        >
            Login
        </button>
    </form>
    <p style="margin-top:16px;text-align:center;font-size:14px;">
        Don't have an account? <a href="{{ route('register') }}" style="color:#4f46e5;font-weight:600;">Register</a>
    </p>
</div>
@endsection
