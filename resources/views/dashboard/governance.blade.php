@extends('layouts.app')
@section('title', 'Governance overview')
@section('content')
<div class="page-head">
    <div><h1>{{ $school->name }}</h1><div class="sub">{{ $membership ? \App\Models\GovernanceMembership::BODIES[$membership->body].', '.$membership->position : 'Governance member' }} &middot; summary view, learner names are not shown</div></div>
    <div class="actions"><a class="btn" href="{{ route('school.feedback.index') }}">{{ icon('message', 16) }} Raise a concern</a></div>
</div>
<div class="stats">
    <div class="stat"><div class="label">Learners</div><div class="value">{{ $stats['enrolment'] }}</div><div class="foot">{{ $stats['girls'] }} girls, {{ $stats['boys'] }} boys</div></div>
    <div class="stat"><div class="label">Teachers</div><div class="value">{{ $stats['teachers'] }}</div><div class="foot">{{ num($stats['ptr']) }} learners per teacher</div></div>
    <div class="stat {{ pct_tone($stats['attendance'], 90, 80) }}"><div class="label">Attendance</div><div class="value">{{ num($stats['attendance']) }}<small>%</small></div></div>
    <div class="stat blue"><div class="label">Marks submitted</div><div class="value">{{ num($stats['completion']) }}<small>%</small></div></div>
    <div class="stat"><div class="label">Pass rate</div><div class="value">{{ num($stats['pass_rate']) }}<small>%</small></div></div>
</div>
<div class="grid grid-2">
    <div class="panel">
        <div class="panel-head"><h2>My concerns</h2><a class="small" href="{{ route('school.feedback.index') }}">All</a></div>
        <ul class="list">
            @forelse ($feedback as $f)
                <li><span>{{ $f->subject }}<div class="small muted">{{ $f->created_at->format('j M Y') }}</div></span><span class="badge {{ status_tone($f->status) }}">{{ label($f->status) }}</span></li>
            @empty
                <li class="muted">You have not raised any concerns.</li>
            @endforelse
        </ul>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Recent inspection visits</h2></div>
        <ul class="list">
            @forelse ($inspections as $r)
                <li><span>{{ $r->visit_date->format('j M Y') }}<div class="small muted">{{ $r->directorate }}</div></span><span class="badge {{ status_tone($r->overall_rating) }}">{{ label($r->overall_rating) }}</span></li>
            @empty
                <li class="muted">No inspection visits recorded.</li>
            @endforelse
        </ul>
    </div>
</div>
<p class="small muted">A term summary is sent to all SMC, PTA and Board members by SMS at the end of each term.</p>
@endsection
