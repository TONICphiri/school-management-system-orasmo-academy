@extends('layouts.app')
@section('title', $student->exists ? 'Edit learner' : 'Enrol a learner')
@section('crumbs')<a href="{{ route('school.students.index') }}">Learners</a> / @endsection
@section('content')
<div class="page-head"><div><h1>{{ $student->exists ? $student->fullName() : 'Enrol a learner' }}</h1><div class="sub">Learner information is restricted and every change is recorded</div></div></div>
<form method="POST" action="{{ $student->exists ? route('school.students.update', $student) : route('school.students.store') }}">
    @csrf @if($student->exists) @method('PUT') @endif
    <div class="panel">
        <div class="panel-body"><div class="form-grid">
            <div class="form-section">Learner</div>
            <div class="field"><label>First name</label><input type="text" name="first_name" value="{{ old('first_name', $student->first_name) }}" required></div>
            <div class="field"><label>Surname</label><input type="text" name="last_name" value="{{ old('last_name', $student->last_name) }}" required></div>
            <div class="field"><label>Gender</label><select name="gender">@foreach (['Female', 'Male'] as $g)<option @selected(old('gender', $student->gender) === $g)>{{ $g }}</option>@endforeach</select></div>
            <div class="field"><label>Date of birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $student->date_of_birth?->toDateString()) }}"></div>
            <div class="field"><label>Admission number</label><input type="text" name="admission_number" value="{{ old('admission_number', $student->admission_number) }}" required></div>
            <div class="field"><label>Class</label><select name="school_class_id">@foreach ($classes as $c)<option value="{{ $c->id }}" @selected(old('school_class_id', $student->school_class_id) == $c->id)>{{ $c->name() }}</option>@endforeach</select></div>
            <div class="field"><label>Date admitted</label><input type="date" name="admitted_on" value="{{ old('admitted_on', $student->admitted_on?->toDateString()) }}"></div>
            @if($student->exists)
                <div class="field"><label>Status</label><select name="status">@foreach (['ENROLLED', 'TRANSFERRED', 'WITHDRAWN', 'COMPLETED'] as $st)<option value="{{ $st }}" @selected(old('status', $student->status) === $st)>{{ label($st) }}</option>@endforeach</select></div>
            @endif
            <div class="form-section">Home</div>
            <div class="field"><label>Village</label><input type="text" name="home_village" value="{{ old('home_village', $student->home_village) }}"></div>
            <div class="field"><label>Traditional Authority</label><input type="text" name="traditional_authority" value="{{ old('traditional_authority', $student->traditional_authority) }}"></div>
            <div class="field"><label>Home district</label><input type="text" name="home_district" value="{{ old('home_district', $student->home_district) }}"></div>
            @unless($student->exists)
                <div class="form-section">Parent or guardian</div>
                <div class="field"><label>Name</label><input type="text" name="guardian_name" value="{{ old('guardian_name') }}"></div>
                <div class="field"><label>Relationship</label><input type="text" name="guardian_relationship" value="{{ old('guardian_relationship', 'Mother') }}"></div>
                <div class="field"><label>Mobile number</label><input type="tel" name="guardian_phone" value="{{ old('guardian_phone') }}" placeholder="+265 88 123 4567"><div class="help">An existing parent account with this number is linked automatically.</div></div>
                <div class="field"><label>Email</label><input type="email" name="guardian_email" value="{{ old('guardian_email') }}"></div>
                <div class="form-section">Learner account</div>
                <div class="field full"><label class="check"><input type="checkbox" name="create_learner_account" value="1" @checked(old('create_learner_account'))> Create a sign in for the learner to view released results</label></div>
                <div class="field" data-show-when="create_learner_account=1"><label>Learner email</label><input type="email" name="learner_email" value="{{ old('learner_email') }}"></div>
            @endunless
        </div></div>
        <div class="panel-foot"><span></span><div class="actions"><a class="btn secondary" href="{{ route('school.students.index') }}">Cancel</a><button class="btn" type="submit">{{ $student->exists ? 'Save changes' : 'Enrol learner' }}</button></div></div>
    </div>
</form>
@endsection
