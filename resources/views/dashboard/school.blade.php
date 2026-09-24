@extends('layouts.app')
@section('title', 'School overview')
@section('content')
@php
    $me = auth()->user();
    $sec = $school->hasSecondary();
    $stages = ['OPEN' => 'Marks in progress', 'CLASS_REVIEWED' => $sec ? 'Reviewed by form master' : 'Reviewed by class teacher', 'DEPUTY_APPROVED' => 'Approved by deputy', 'RELEASED' => 'Released to families'];
    $periods = \App\Services\Timetable::PERIOD_TIMES;
@endphp
<div class="page-head">
    <div>
        <h1>{{ $school->name }}</h1>
        <div class="sub">{{ $school->typeLabel() }} &middot; {{ $school->categoryLabel() }} &middot; {{ $school->district->name }} &middot; {{ $term ? $term->label().', ends '.$term->ends_on->format('j M Y') : 'No current term set' }}</div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('school.marks.index') }}">{{ icon('edit', 16) }} Enter marks</a>
        @if ($me->role === 'FACILITY_ADMIN')
            <a class="btn" href="{{ route('school.staff.create') }}">{{ icon('plus', 16) }} Add staff</a>
        @endif
    </div>
</div>

<div class="stats">
    <div class="stat"><div class="label">Learners</div><div class="value">{{ $stats['enrolment'] }}</div><div class="foot">{{ $stats['girls'] }} girls, {{ $stats['boys'] }} boys</div></div>
    <div class="stat"><div class="label">Teachers</div><div class="value">{{ $stats['teachers'] }}</div><div class="foot">{{ $stats['qualified'] }} meet the qualification rule</div></div>
    <div class="stat {{ $stats['ptr'] > 60 ? 'red' : '' }}"><div class="label">Learners per teacher</div><div class="value">{{ num($stats['ptr']) }}</div><div class="foot">National target is 60 or fewer</div></div>
    <div class="stat {{ pct_tone($stats['attendance'], 90, 80) }}"><div class="label">Attendance this term</div><div class="value">{{ num($stats['attendance']) }}<small>%</small></div></div>
    <div class="stat blue"><div class="label">Marks submitted</div><div class="value">{{ num($stats['completion']) }}<small>%</small></div><div class="foot">Of class subjects</div></div>
</div>

<div class="grid grid-main">
    <div class="stack">
        @isset($pipeline)
        <div class="panel">
            <div class="panel-head"><h2>Results approval this term</h2><a class="small" href="{{ route('school.results.index') }}">Open results</a></div>
            <div class="panel-body">
                <div class="pipeline">
                    @php $counted = collect($pipeline)->sum(); @endphp
                    @foreach ($stages as $key => $name)
                        @php $n = $key === 'OPEN' ? $classCount - $counted + ($pipeline['OPEN'] ?? 0) : ($pipeline[$key] ?? 0); @endphp
                        <div class="step {{ $key === 'RELEASED' && $n ? 'done' : '' }}"><div class="n">{{ $n }}</div><div class="t">{{ $name }}</div></div>
                    @endforeach
                </div>
                <p class="small muted" style="margin:.75rem 0 0">
                    @if ($sec) Subject teacher submits, head of department validates, form master reviews, deputy head (academic) approves, head teacher releases.
                    @else Teacher submits, class teacher reviews, head teacher releases to parents. @endif
                </p>
            </div>
        </div>
        @endisset

        @if ($toValidate->isNotEmpty())
        <div class="panel">
            <div class="panel-head"><h2>Waiting for your validation</h2><span class="badge warn">{{ $toValidate->count() }}</span></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Class</th><th>Subject</th><th>Teacher</th><th>Submitted</th><th></th></tr></thead>
                <tbody>
                @foreach ($toValidate as $s)
                    <tr>
                        <td class="strong">{{ $s->classSubject->schoolClass->name() }}</td>
                        <td>{{ $s->classSubject->subject->name }}</td>
                        <td>{{ $s->classSubject->teacher?->name }}</td>
                        <td class="nowrap">{{ $s->submitted_at?->format('j M, H:i') }}</td>
                        <td class="right"><a class="btn small" href="{{ route('school.marks.show', $s->classSubject) }}">Review</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
        @endif

        <div class="panel">
            <div class="panel-head"><h2>My subjects</h2><span class="hint">{{ $myLessons->count() }} {{ Str::plural('class', $myLessons->count()) }}</span></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Class</th><th>Subject</th><th class="num">Periods</th><th>Marks this term</th><th></th></tr></thead>
                <tbody>
                @forelse ($myLessons as $row)
                    @php $cs = $row['lesson']; $st = $row['status']; @endphp
                    <tr>
                        <td class="strong">{{ $cs->schoolClass->name() }}</td>
                        <td>{{ $cs->subject->name }}</td>
                        <td class="num">{{ $cs->periods_per_week }}</td>
                        <td><span class="badge {{ status_tone($st?->status ?? 'DRAFT') }}">{{ label($st?->status ?? 'DRAFT') }}</span></td>
                        <td class="right"><a class="btn secondary small" href="{{ route('school.marks.show', $cs) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">You have not been assigned any subjects.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>

        @if ($me->isSchoolLeader() && isset($noClassTeacher) && ($noClassTeacher->isNotEmpty() || $noTeacher))
        <div class="panel">
            <div class="panel-head"><h2>Staffing gaps</h2></div>
            <ul class="list">
                @foreach ($noClassTeacher as $c)
                    <li><span>{{ $c->name() }} has no {{ $c->isSecondary() ? 'form master' : 'class teacher' }}</span><a class="btn secondary small" href="{{ route('school.classes.show', $c) }}">Assign</a></li>
                @endforeach
                @if ($noTeacher)
                    <li><span>{{ $noTeacher }} class {{ Str::plural('subject', $noTeacher) }} without a teacher</span><a class="btn secondary small" href="{{ route('school.classes.index') }}">Review classes</a></li>
                @endif
            </ul>
        </div>
        @endif
    </div>

    <div class="stack">
        <div class="panel">
            <div class="panel-head"><h2>Today</h2><span class="hint">{{ now()->format('l j F') }}</span></div>
            @if ($today->isEmpty())
                <div class="empty">{{ now()->isWeekend() ? 'No lessons at the weekend.' : 'You have no lessons on the timetable today.' }}</div>
            @else
                <ul class="timeline">
                    @foreach ($today as $slot)
                        <li><time>{{ $periods[$slot->period] ?? '' }}</time><span><strong>{{ $slot->classSubject->subject->name }}</strong><div class="small muted">{{ $slot->classSubject->schoolClass->name() }} &middot; Period {{ $slot->period }}</div></span></li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($myClass)
        <div class="panel">
            <div class="panel-head"><h2>My class</h2><span class="hint">{{ $myClass->isSecondary() ? 'Form master' : 'Class teacher' }}</span></div>
            <div class="panel-body">
                <div style="font-size:1.3rem;font-weight:600">{{ $myClass->name() }}</div>
                <div class="muted small">{{ $myClass->students_count }} learners &middot; {{ $myClass->room }}</div>
                <div class="actions" style="margin-top:.8rem">
                    <a class="btn small" href="{{ route('school.attendance.edit', $myClass) }}">{{ icon('check', 14) }} Take attendance</a>
                    <a class="btn secondary small" href="{{ route('school.results.show', $myClass) }}">Class results</a>
                </div>
            </div>
        </div>
        @endif

        @isset($recent)
        <div class="panel">
            <div class="panel-head"><h2>Recent activity</h2>@if($me->role === 'FACILITY_ADMIN')<a class="small" href="{{ route('school.audit') }}">Audit log</a>@endif</div>
            @if ($feedbackOpen)
                <div class="alert warn" style="margin:.75rem">{{ $feedbackOpen }} open {{ Str::plural('concern', $feedbackOpen) }} from the SMC, PTA or Board. <a href="{{ route('school.feedback.index') }}">Respond</a></div>
            @endif
            <ul class="timeline">
                @forelse ($recent as $log)
                    <li><time>{{ $log->created_at->format('j M, H:i') }}</time><span>{{ $log->description }}</span></li>
                @empty
                    <li class="muted">No activity yet.</li>
                @endforelse
            </ul>
        </div>
        @endisset
    </div>
</div>
@endsection
