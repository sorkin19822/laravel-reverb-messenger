@extends('layouts.app')
@section('content')
<div class="card" style="max-width:400px;margin:40px auto;">
    <h2 style="margin-bottom:20px;">Register</h2>

    @if($errors->any())
        <div style="background:#fee2e2;border:1px solid #fca5a5;padding:10px 14px;border-radius:4px;margin-bottom:16px;">
            @foreach($errors->all() as $error)
                <p style="font-size:14px;color:#b91c1c;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div style="margin-bottom:16px;">
            <label style="display:block;margin-bottom:4px;font-size:14px;font-weight:600;">Name</label>
            <input
                name="name"
                value="{{ old('name') }}"
                required
                autocomplete="name"
                style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:14px;outline:none;"
            >
        </div>
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
                autocomplete="new-password"
                style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:14px;outline:none;"
            >
        </div>
        <div style="margin-bottom:20px;">
            <label style="display:block;margin-bottom:4px;font-size:14px;font-weight:600;">Confirm Password</label>
            <input
                name="password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:14px;outline:none;"
            >
        </div>
        <button
            type="submit"
            style="width:100%;background:#4f46e5;color:white;padding:12px;border:none;border-radius:4px;cursor:pointer;font-size:16px;font-weight:600;"
        >
            Register
        </button>
    </form>
    <p style="margin-top:16px;text-align:center;font-size:14px;">
        Already have an account? <a href="{{ route('login') }}" style="color:#4f46e5;font-weight:600;">Login</a>
    </p>
</div>
@endsection
