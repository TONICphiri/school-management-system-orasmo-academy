@extends('layouts.guest')
@section('title', 'Sign in')
@section('content')
    <h2>Sign in</h2>
    <p class="lead">Use the email address or phone number registered with your school or office.</p>
    @error('login')<div class="alert error">{{ $message }}</div>@enderror
    <form method="POST" action="{{ url('/login') }}">
        @csrf
        <div class="field">
            <label for="identifier">Email or phone number</label>
            <input id="identifier" name="identifier" type="text" value="{{ old('identifier') }}" autocomplete="username" required autofocus placeholder="name@school.edu.mw or +265...">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <div class="field"><label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in on this computer</label></div>
        <button class="btn" type="submit">Sign in</button>
    </form>
    <div class="alert info" style="margin-top:1.5rem">
        <strong>First time here?</strong> If your head teacher or the Ministry has just registered you, <a href="{{ route('activate') }}">activate your account</a> with the code you received by SMS or email.
    </div>
@endsection
