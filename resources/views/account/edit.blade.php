@extends('layouts.app')
@section('title', 'My account')
@section('content')
<div class="page-head">
    <div><h1>My account</h1><div class="sub">{{ $user->roleLabel() }} &middot; {{ $user->scopeLabel() }}</div></div>
</div>
<div class="grid grid-2">
    <div class="panel">
        <div class="panel-head"><h2>Profile</h2></div>
        <div class="panel-body">
            <dl class="kv">
                <dt>Name</dt><dd>{{ $user->name }}</dd>
                <dt>Role</dt><dd>{{ $user->roleLabel() }}</dd>
                <dt>Email</dt><dd>{{ $user->email ?: 'Not recorded' }}</dd>
                <dt>Phone</dt><dd>{{ $user->phone ?: 'Not recorded' }}</dd>
                <dt>Account status</dt><dd><span class="badge {{ status_tone($user->status) }}">{{ label($user->status) }}</span></dd>
                <dt>Activated</dt><dd>{{ $user->activated_at?->format('j M Y, H:i') ?? 'Not yet' }}</dd>
                <dt>Policies accepted</dt><dd>{{ $user->policy_accepted_at?->format('j M Y') ?? 'Not yet' }}</dd>
                <dt>Last sign in</dt><dd>{{ $user->last_login_at?->format('j M Y, H:i') ?? 'Not recorded' }}</dd>
            </dl>
            <p class="small muted mt-[1rem]">To correct your name, email or phone number, ask your head teacher or the Ministry administrator.</p>
        </div>
    </div>
    <div class="stack">
        <div class="panel">
            <div class="panel-head"><h2>Sign in and notifications</h2></div>
            <form method="POST" action="{{ route('account.security') }}">
                @csrf
                <div class="panel-body">
                    <div class="field mb-[1rem]">
                        <label for="preferred_channel">Send codes and alerts by</label>
                        <select id="preferred_channel" name="preferred_channel">
                            <option value="SMS" @selected($user->preferred_channel === 'SMS') @disabled(! $user->phone)>SMS to {{ $user->phone ?: 'no phone on record' }}</option>
                            <option value="EMAIL" @selected($user->preferred_channel === 'EMAIL') @disabled(! $user->email)>Email to {{ $user->email ?: 'no email on record' }}</option>
                        </select>
                    </div>
                    <label class="check"><input type="checkbox" name="mfa" value="1" @checked($user->mfa_enabled)> Ask for a code every time I sign in</label>
                </div>
                <div class="panel-foot"><span class="small muted">Changes are recorded in the audit log.</span><button class="btn" type="submit">Save settings</button></div>
            </form>
        </div>
        <div class="panel">
            <div class="panel-head"><h2>Change password</h2></div>
            <form method="POST" action="{{ route('account.password') }}">
                @csrf
                <div class="panel-body">
                    <div class="form-grid">
                        <div class="field full"><label>Current password</label><input type="password" name="current_password" required autocomplete="current-password"></div>
                        <div class="field"><label>New password</label><input type="password" name="password" required autocomplete="new-password"></div>
                        <div class="field"><label>Confirm new password</label><input type="password" name="password_confirmation" required autocomplete="new-password"></div>
                    </div>
                    <div class="help small muted mt-[.5rem]">At least 10 characters with upper and lower case letters and a number.</div>
                </div>
                <div class="panel-foot"><span></span><button class="btn" type="submit">Change password</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
