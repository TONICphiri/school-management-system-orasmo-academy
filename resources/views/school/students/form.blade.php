@extends('layouts.app')
@section('title', $student->exists ? 'Edit learner' : 'Register a learner')
@section('crumbs')<a href="{{ route('school.students.index') }}">Learners</a> / @endsection
@section('content')
@php
    $source = $source ?? null;
    $g = $sourceGuardian ?? null;
@endphp
<div class="page-head"><div><h1>{{ $student->exists ? $student->fullName() : 'Register a learner' }}</h1><div class="sub">{{ $student->exists ? 'Learner ID '.$student->learner_uid : 'A Learner ID and QR code are created when you save. No national ID is needed.' }}</div></div></div>

@unless($student->exists)
<div class="panel">
    <div class="panel-head"><h2>Learner coming from another school</h2></div>
    <div class="panel-body">
        <form method="GET" action="{{ route('school.students.create') }}" class="inline-form">
            <div class="field"><label>Learner ID</label><input type="text" name="learner_id" value="{{ $lookup }}" placeholder="MW2600000125" maxlength="16"></div>
            <button class="btn secondary" type="submit">Find learner</button>
        </form>
        <p class="small muted">If the learner was registered at any school before, enter the Learner ID from their card or report. Their details carry over and the old school is told the learner has moved.</p>
        @if($lookupError)<div class="alert error">{{ $lookupError }}</div>@endif
        @if($source)
            <div class="alert success">Found {{ $source->fullName() }}, last at {{ $source->school->name }} ({{ label($source->status) }}). Check the details below, choose the new class and save.</div>
        @endif
    </div>
</div>
@endunless

<form method="POST" action="{{ $student->exists ? route('school.students.update', $student) : route('school.students.store') }}">
    @csrf @if($student->exists) @method('PUT') @endif
    @if($source)<input type="hidden" name="transfer_from" value="{{ $source->id }}">@endif
    <div class="panel">
        <div class="panel-body"><div class="form-grid">
            <div class="form-section">Personal details</div>
            <div class="field"><label>First name</label><input type="text" name="first_name" value="{{ old('first_name', $student->first_name) }}" required></div>
            <div class="field"><label>Surname</label><input type="text" name="last_name" value="{{ old('last_name', $student->last_name) }}" required></div>
            <div class="field"><label>Gender</label><select name="gender">@foreach (['Female', 'Male'] as $gd)<option @selected(old('gender', $student->gender) === $gd)>{{ $gd }}</option>@endforeach</select></div>
            <div class="field"><label>Date of birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $student->date_of_birth?->toDateString()) }}" required></div>

            <div class="form-section">Class placement</div>
            <div class="field"><label>Class</label><select name="school_class_id">@foreach ($classes as $c)<option value="{{ $c->id }}" @selected(old('school_class_id', $student->school_class_id ?? ($defaultClass ?? null)) == $c->id)>{{ $c->name() }}</option>@endforeach</select>
                @unless(auth()->user()->isSchoolLeader())<div class="help">You can register into the class you own or the classes you teach.</div>@endunless</div>
            <div class="field"><label>Admission number</label><input type="text" name="admission_number" value="{{ old('admission_number', $student->admission_number) }}" required><div class="help">Suggested by the system. You can change it.</div></div>
            <div class="field"><label>Date admitted</label><input type="date" name="admitted_on" value="{{ old('admitted_on', $student->admitted_on?->toDateString()) }}"></div>
            @if($student->exists)
                <div class="field"><label>Status</label><select name="status">@foreach (['ENROLLED', 'TRANSFERRED', 'WITHDRAWN', 'COMPLETED'] as $st)<option value="{{ $st }}" @selected(old('status', $student->status) === $st)>{{ label($st) }}</option>@endforeach</select></div>
            @endif

            <div class="form-section">Address</div>
            <div class="field full"><label>Physical address</label><input type="text" name="physical_address" value="{{ old('physical_address', $student->physical_address) }}" placeholder="House 14, Chilomoni Ward, near Chilomoni Health Centre, Blantyre" required></div>
            <div class="field"><label>Home village</label><input type="text" name="home_village" value="{{ old('home_village', $student->home_village) }}"></div>
            <div class="field"><label>Traditional Authority</label><input type="text" name="traditional_authority" value="{{ old('traditional_authority', $student->traditional_authority) }}"></div>
            <div class="field"><label>Home district</label><input type="text" name="home_district" value="{{ old('home_district', $student->home_district) }}"></div>

            <div class="form-section">Emergency contact</div>
            <div class="field"><label>Name</label><input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $student->emergency_contact_name) }}"></div>
            <div class="field"><label>Relationship</label><input type="text" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship', $student->emergency_contact_relationship) }}" placeholder="Uncle"></div>
            <div class="field"><label>Mobile number</label><input type="tel" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $student->emergency_contact_phone) }}" placeholder="+265 99 765 4321"></div>

            @unless($student->exists)
                <div class="form-section">Parent or guardian</div>
                <div class="field"><label>Name</label><input type="text" name="guardian_name" value="{{ old('guardian_name', $g?->name) }}" required></div>
                <div class="field"><label>Relationship</label><input type="text" name="guardian_relationship" value="{{ old('guardian_relationship', $g?->relationship ?? 'Mother') }}"></div>
                <div class="field"><label>Mobile number</label><input type="tel" name="guardian_phone" value="{{ old('guardian_phone', $g?->phone) }}" placeholder="+265 88 123 4567" required><div class="help">An existing parent account with this number is linked automatically.</div></div>
                <div class="field"><label>Email</label><input type="email" name="guardian_email" value="{{ old('guardian_email', $g?->email) }}"></div>

                @unless($source)
                <div class="form-section">Previous school</div>
                <div class="field"><label>School name</label><input type="text" name="previous_school_name" value="{{ old('previous_school_name') }}" placeholder="Leave empty for a new Standard 1 learner"></div>
                <div class="field"><label>EMIS code</label><input type="text" name="previous_school_code" value="{{ old('previous_school_code') }}"></div>
                <div class="field"><label>Last class</label><input type="text" name="previous_last_class" value="{{ old('previous_last_class') }}" placeholder="Standard 4"></div>
                <div class="field"><label>Year left</label><input type="number" name="previous_year_left" value="{{ old('previous_year_left') }}" min="1990" max="{{ now()->year }}"></div>
                <div class="field full"><label>Reason for leaving</label><input type="text" name="previous_reason" value="{{ old('previous_reason') }}" placeholder="Family moved to Blantyre"></div>
                @endunless

                <div class="form-section">Learner account</div>
                <div class="field full"><label class="check"><input type="checkbox" name="create_learner_account" value="1" @checked(old('create_learner_account'))> Create a sign in for the learner to view released results</label></div>
                <div class="field" data-show-when="create_learner_account=1"><label>Learner email</label><input type="email" name="learner_email" value="{{ old('learner_email') }}"></div>
            @endunless
        </div></div>
        <div class="panel-foot"><span></span><div class="actions"><a class="btn secondary" href="{{ route('school.students.index') }}">Cancel</a><button class="btn" type="submit">{{ $student->exists ? 'Save changes' : ($source ? 'Admit transferring learner' : 'Register learner') }}</button></div></div>
    </div>
</form>
@endsection
