@extends('layouts.app')
@section('title', $student->fullName())
@section('crumbs')<a href="{{ route('school.students.index') }}">Learners</a> / @endsection
@section('content')
@php $leader = $canEdit; $present = ($attendance['PRESENT'] ?? 0) + ($attendance['LATE'] ?? 0); $days = $attendance->sum(); @endphp
<div class="page-head">
    <div><h1>{{ $student->fullName() }} <span class="badge {{ status_tone($student->status) }} align-middle">{{ label($student->status) }}</span></h1>
    <div class="sub">Learner ID <strong>{{ $student->learner_uid }}</strong> &middot; {{ $student->admission_number }} &middot; {{ $student->schoolClass?->name() }} &middot; {{ $student->gender }}{{ $student->date_of_birth ? ', '.$student->date_of_birth->age.' years' : '' }}</div></div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('school.students.card', $student) }}">{{ icon('printer', 16) }} Learner card</a>
        <a class="btn secondary" href="{{ route('school.reports.card', $student) }}">{{ icon('printer', 16) }} Report card</a>
        @if($leader)<a class="btn" href="{{ route('school.students.edit', $student) }}">{{ icon('edit', 16) }} Edit</a>@endif
    </div>
</div>
<div class="stats">
    <div class="stat {{ pct_tone($days ? $present / $days * 100 : null, 90, 80) }}"><div class="label">Attendance {{ $term?->label() }}</div><div class="value">{{ $days ? round($present / $days * 100) : 'n/a' }}<small>{{ $days ? '%' : '' }}</small></div><div class="foot">{{ $present }} of {{ $days }} days</div></div>
    <div class="stat red"><div class="label">Absent</div><div class="value">{{ $attendance['ABSENT'] ?? 0 }}</div><div class="foot">{{ $attendance['EXCUSED'] ?? 0 }} excused</div></div>
    <div class="stat amber"><div class="label">Late</div><div class="value">{{ $attendance['LATE'] ?? 0 }}</div></div>
    @if($student->maneb_exam_number)<div class="stat blue"><div class="label">MANEB exam number</div><div class="value text-[1.2rem]">{{ $student->maneb_exam_number }}</div></div>@endif
</div>
<div class="grid grid-2">
    <div class="panel">
        <div class="panel-head"><h2>Learner details</h2><span class="badge warn">Restricted</span></div>
        <div class="panel-body"><dl class="kv">
            <dt>Date of birth</dt><dd>{{ $student->date_of_birth?->format('j F Y') ?? 'Not recorded' }}</dd>
            <dt>Admitted</dt><dd>{{ $student->admitted_on?->format('j F Y') ?? 'Not recorded' }}</dd>
            <dt>Village</dt><dd>{{ $student->home_village ?: 'Not recorded' }}</dd>
            <dt>Traditional Authority</dt><dd>{{ $student->traditional_authority ?: 'Not recorded' }}</dd>
            <dt>Home district</dt><dd>{{ $student->home_district ?: 'Not recorded' }}</dd>
            <dt>Physical address</dt><dd>{{ $student->physical_address ?: 'Not recorded' }}</dd>
            <dt>Emergency contact</dt><dd>{{ $student->emergency_contact_name ? $student->emergency_contact_name.' ('.($student->emergency_contact_relationship ?: 'contact').'), '.$student->emergency_contact_phone : 'Not recorded' }}</dd>
            <dt>Registered by</dt><dd>{{ $student->registeredBy?->name ?? 'Not recorded' }}</dd>
            <dt>Class teacher</dt><dd>{{ $student->schoolClass?->classTeacher?->name ?? 'Not assigned' }}</dd>
            <dt>Learner account</dt><dd>{{ $student->user ? label($student->user->status) : 'None' }}</dd>
        </dl></div>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Parents and guardians</h2></div>
        <ul class="list">
            @forelse ($student->guardians as $g)
                <li><span><strong>{{ $g->name }}</strong><div class="small muted">{{ $g->pivot->relationship }} &middot; {{ $g->phone }}</div></span><span class="badge {{ status_tone($g->status) }}">{{ label($g->status) }}</span></li>
            @empty
                <li class="muted">No guardian linked.</li>
            @endforelse
        </ul>
    </div>
</div>
<div class="grid grid-2">
    <div class="panel">
        <div class="panel-head"><h2>Learner ID and QR code</h2></div>
        <div class="panel-body qr-block">
            <div class="qr">{!! $student->qrSvg(150) !!}</div>
            <div><div class="small muted">National Learner ID</div><div class="learner-uid">{{ $student->learner_uid }}</div>
            <p class="small muted">The same ID follows the learner to every school, from Standard 1 to Form 4. The QR code holds only the ID, never personal details.</p></div>
        </div>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>School history</h2></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>School</th><th>Last class</th><th>Year</th><th>Reason</th></tr></thead>
            <tbody>
                <tr><td><strong>{{ current_school()->name }}</strong><div class="small muted">Current</div></td><td>{{ $student->schoolClass?->name() }}</td><td>{{ $student->admitted_on?->year }}</td><td><span class="badge {{ status_tone($student->status) }}">{{ label($student->status) }}</span></td></tr>
                @foreach ($passport as $p)
                    <tr><td>{{ $p->school->name }}<div class="small muted">{{ $p->school->code }} &middot; linked record</div></td><td>{{ $p->schoolClass?->name() }}</td><td>{{ $p->admitted_on?->year }}</td><td><span class="badge {{ status_tone($p->status) }}">{{ label($p->status) }}</span></td></tr>
                @endforeach
                @foreach ($student->histories as $h)
                    <tr><td>{{ $h->school_name }}@if($h->school_code)<div class="small muted">{{ $h->school_code }}</div>@endif</td><td>{{ $h->last_class ?: 'Not recorded' }}</td><td>{{ $h->year_left }}</td><td>{{ $h->reason }}</td></tr>
                @endforeach
            </tbody>
        </table></div>
        @if($canEdit)
        <form method="POST" action="{{ route('school.students.history', $student) }}" class="panel-foot inline-form">@csrf
            <input type="text" name="school_name" placeholder="Previous school" required>
            <input class="max-w-[130px]" type="text" name="last_class" placeholder="Last class">
            <input class="max-w-[100px]" type="number" name="year_left" placeholder="Year" min="1990" max="{{ now()->year }}">
            <input type="text" name="reason" placeholder="Reason">
            <button class="btn secondary" type="submit">Add</button>
        </form>
        @endif
    </div>
</div>
@if ($electiveOptions->count())
<div class="panel">
    <div class="panel-head"><h2>Elective subjects</h2><span class="hint">Core subjects are taken by every learner in the form</span></div>
    <form method="POST" action="{{ route('school.students.electives', $student) }}">@csrf
        <div class="panel-body"><div class="checks">
            @foreach ($electiveOptions as $cs)
                <label class="check"><input type="checkbox" name="electives[]" value="{{ $cs->id }}" @checked($student->electives->contains('id', $cs->id)) @disabled(! $leader)> {{ $cs->subject->name }}</label>
            @endforeach
        </div></div>
        <div class="panel-foot"><span class="small muted">MSCE candidates normally sit at least six subjects.</span>@if($leader)<button class="btn secondary" type="submit">Save electives</button>@endif</div>
    </form>
</div>
@endif
@endsection
