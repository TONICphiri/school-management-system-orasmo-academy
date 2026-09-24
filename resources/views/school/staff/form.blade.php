@extends('layouts.app')
@section('title', $user->exists ? 'Edit staff member' : 'Add staff member')
@section('crumbs')<a href="{{ route('school.staff.index') }}">Staff</a> / @endsection
@section('content')
<div class="page-head"><div><h1>{{ $user->exists ? $user->name : 'Add staff member' }}</h1><div class="sub">{{ $user->exists ? $user->roleLabel() : 'An activation code is sent when the account is created' }}</div></div></div>
<form method="POST" action="{{ $user->exists ? route('school.staff.update', $user) : route('school.staff.store') }}">
    @csrf @if($user->exists) @method('PUT') @endif
    <div class="panel">
        <div class="panel-body">
            <div class="form-grid">
                <div class="form-section">Personal details</div>
                <div class="field"><label>Full name</label><input type="text" name="name" value="{{ old('name', $user->name) }}" required></div>
                <div class="field"><label>National ID number</label><input type="text" name="national_id" value="{{ old('national_id', $user->national_id) }}" required></div>
                <div class="field"><label>Gender</label><select name="gender">@foreach (['Female', 'Male'] as $g)<option @selected(old('gender', $user->gender) === $g)>{{ $g }}</option>@endforeach</select></div>
                <div class="field"><label>Mobile number</label><input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+265 99 123 4567"></div>
                <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}"></div>
                <div class="field"><label>Preferred channel</label><select name="channel"><option value="SMS" @selected(old('channel', $user->preferred_channel) === 'SMS')>SMS</option><option value="EMAIL" @selected(old('channel', $user->preferred_channel) === 'EMAIL')>Email</option></select></div>

                <div class="form-section">Appointment</div>
                <div class="field"><label>Role</label><select name="role">@foreach ($roles as $k => $v)<option value="{{ $k }}" @selected(old('role', $user->role) === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label>Qualification</label><select name="qualification">@foreach (\App\Models\TeacherProfile::QUALIFICATIONS as $k => $v)<option value="{{ $k }}" @selected(old('qualification', $user->teacherProfile?->qualification ?? 'T2') === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label>TSC employment number</label><input type="text" name="employment_number" value="{{ old('employment_number', $user->teacherProfile?->employment_number) }}"></div>
                <div class="field"><label>Subject specialisation</label><input type="text" name="specialisation" value="{{ old('specialisation', $user->teacherProfile?->specialisation) }}" placeholder="For example Mathematics and Physics"></div>

                @if ($committees->count())
                <div class="form-section">Committees</div>
                <div class="field full"><div class="checks">
                    @php $mine = old('committees', $user->exists ? $user->committees->pluck('id')->all() : []); @endphp
                    @foreach ($committees as $c)<label class="check"><input type="checkbox" name="committees[]" value="{{ $c->id }}" @checked(in_array($c->id, $mine))> {{ $c->name }}</label>@endforeach
                </div></div>
                @endif

                @unless ($user->exists)
                <div class="form-section">Activation</div>
                <div class="field full">
                    <label class="check"><input type="radio" name="credential" value="OTP" checked> Send a one time code, valid for 15 minutes</label>
                    <label class="check"><input type="radio" name="credential" value="TEMP_PASSWORD"> Send a temporary password, valid for 72 hours</label>
                </div>
                @endunless
            </div>
        </div>
        <div class="panel-foot"><span class="small muted">Role changes are recorded in the audit log and the staff member is notified.</span><div class="actions"><a class="btn secondary" href="{{ route('school.staff.index') }}">Cancel</a><button class="btn" type="submit">{{ $user->exists ? 'Save changes' : 'Create account' }}</button></div></div>
    </div>
</form>
@endsection
