@extends('layouts.guest')
@section('title', 'Complete activation')
@section('content')
    <div class="steps"><span class="done">1. Verify code</span><span class="on">2. Set password</span><span class="on">3. Accept policies</span></div>
    <h2>Welcome, {{ $user->name }}</h2>
    <p class="lead">You are joining as <strong>{{ $user->roleLabel() }}</strong>{{ $user->school ? ' at '.$user->school->name : '' }}. Choose a new password to finish setting up.</p>
    <form method="POST" action="{{ route('onboarding.store') }}">
        @csrf
        <div class="field">
            <label for="password">New password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>
            <div class="help">At least 10 characters with upper and lower case letters and a number.</div>
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        </div>

        <span class="label-text">Data protection</span>
        <div class="policy-box">
            Learner and staff records in this system are personal data held by the Ministry of Education. Use them only for the work of your school or office. Do not copy, photograph or share records of learners outside the system. Keep your password private and sign out on shared computers. Report any suspected leak to your head teacher or the Ministry ICT unit within 24 hours.
        </div>
        <div class="field"><label class="check"><input type="checkbox" name="accept_policy" value="1" @checked(old('accept_policy'))> I have read and accept the MoEST data protection policy.</label></div>

        <span class="label-text">Child safeguarding</span>
        <div class="policy-box">
            Every adult in a school has a duty to protect learners from abuse, neglect and exploitation. Information about a learner's home, health or conduct must be handled with care and only by those who need it. Concerns about the safety of a learner must be reported to the head teacher and the District Social Welfare Office without delay.
        </div>
        <div class="field"><label class="check"><input type="checkbox" name="accept_safeguarding" value="1" @checked(old('accept_safeguarding'))> I accept the MoEST child safeguarding policy.</label></div>

        <div class="field"><label class="check"><input type="checkbox" name="mfa" value="1" @checked(old('mfa'))> Send me a code by {{ $user->preferred_channel === 'SMS' ? 'SMS' : 'email' }} each time I sign in (recommended)</label></div>
        <button class="btn" type="submit">Activate my account</button>
    </form>
@endsection
