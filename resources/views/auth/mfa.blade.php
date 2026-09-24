@extends('layouts.guest')
@section('title', 'Verify sign in')
@section('content')
    <h2>Enter your sign in code</h2>
    <p class="lead">We sent a 6 digit code to your {{ auth()->user()->preferred_channel === 'SMS' ? 'phone' : 'email' }}. It expires in 15 minutes.</p>
    <form method="POST" action="{{ route('mfa.verify') }}">
        @csrf
        <div class="field">
            <label for="code">Sign in code</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required autofocus>
        </div>
        <button class="btn" type="submit">Verify</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" style="margin-top:1rem">@csrf<button class="btn secondary" type="submit">Cancel and sign out</button></form>
@endsection
