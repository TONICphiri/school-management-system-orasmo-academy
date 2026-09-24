@extends('layouts.app')
@section('title', $user->role === 'PARENT' ? 'My children' : 'My school work')
@section('content')
<div class="page-head">
    <div><h1>{{ $user->role === 'PARENT' ? 'My children' : 'My school work' }}</h1><div class="sub">{{ $user->school?->name }} &middot; {{ $term?->label() ?? 'No current term' }}</div></div>
</div>
@forelse ($cards as $c)
    @php $s = $c['student']; $phase = $s->schoolClass?->level->phase; @endphp
    <div class="panel">
        <div class="panel-head">
            <div><h2>{{ $s->fullName() }}</h2><span class="hint">{{ $s->schoolClass?->name() ?? 'No class' }} &middot; Admission {{ $s->admission_number }}</span></div>
            @if ($c['released'])
                <a class="btn small" href="{{ route('school.reports.card', $s) }}">{{ icon('printer', 14) }} Report card</a>
            @endif
        </div>
        <div class="panel-body">
            <div class="stats mb-[1rem]">
                <div class="stat {{ pct_tone($c['attendance'], 90, 80) }}"><div class="label">Attendance this term</div><div class="value">{{ $c['attendance'] ?? 'n/a' }}<small>{{ $c['attendance'] !== null ? '%' : '' }}</small></div><div class="foot">{{ $c['absences'] }} days absent</div></div>
                @if ($c['report'])
                    <div class="stat blue"><div class="label">Average</div><div class="value">{{ num($c['report']['average']) }}<small>%</small></div><div class="foot">Class average {{ num($c['report']['class_average']) }}%</div></div>
                    <div class="stat"><div class="label">Position</div><div class="value">{{ $c['report']['position'] ?? 'n/a' }}<small> of {{ $c['report']['class_size'] }}</small></div></div>
                @endif
            </div>
            @if ($c['report'])
                <table class="table">
                    <thead><tr><th>Subject</th><th class="num">Assessment (40%)</th><th class="num">Examination (60%)</th><th class="num">Final</th><th>Grade</th></tr></thead>
                    <tbody>
                    @foreach ($c['report']['rows'] as $r)
                        <tr><td>{{ $r['subject']->name }}</td><td class="num">{{ num($r['ca']) }}</td><td class="num">{{ num($r['exam']) }}</td><td class="num strong">{{ num($r['final']) }}</td><td><span class="grade {{ grade_tone($phase, $r['grade']) }}">{{ $r['grade'] ?? '' }}</span> <span class="small muted">{{ $r['label'] }}</span></td></tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert info m-0">Results for {{ $term?->label() ?? 'this term' }} have not been released yet. You will receive a notification when the head teacher releases them.</div>
            @endif
        </div>
    </div>
@empty
    <div class="panel"><div class="empty"><strong>No learner linked to your account</strong>Ask the school office to link your account to your child's record.</div></div>
@endforelse
@endsection
