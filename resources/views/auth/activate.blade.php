@extends('layouts.guest')
@section('title', 'Activate account')
@section('content')
    <div class="steps"><span class="on">1. Verify code</span><span>2. Set password</span><span>3. Accept policies</span></div>
    <h2>Activate your account</h2>
    <p class="lead">Enter the one time code or temporary password sent to your phone or email. Codes expire after 15 minutes and temporary passwords after 72 hours.</p>
    <form method="POST" action="{{ url('/activate') }}">
        @csrf
        <div class="field">
            <label for="identifier">Email or phone number</label>
            <input id="identifier" name="identifier" type="text" value="{{ old('identifier', $identifier) }}" required>
        </div>
        <div class="field">
            <label for="code">Activation code</label>
            <input id="code" name="code" type="text" inputmode="text" autocomplete="one-time-code" required autofocus class="@error('code') is-invalid @enderror">
            <div class="help">After 5 wrong attempts the code is cancelled and the Ministry is alerted.</div>
        </div>
        <button class="btn" type="submit">Verify and continue</button>
    </form>
    <form class="mt-[1rem]" method="POST" action="{{ route('activate.resend') }}">
        @csrf
        <input type="hidden" name="identifier" value="{{ old('identifier', $identifier) }}">
        <button class="btn secondary" type="submit" @if(! old('identifier', $identifier)) disabled title="Enter your email or phone first and try the code once" @endif>Send me a new code</button>
    </form>
    <p class="small mt-[1rem]"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
