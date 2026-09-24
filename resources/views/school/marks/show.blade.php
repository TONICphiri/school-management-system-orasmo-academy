@extends('layouts.app')
@section('title', $lesson->subject->name.', '.$lesson->schoolClass->name())
@section('crumbs')<a href="{{ route('school.marks.index') }}">Mark entry</a> / @endsection
@section('content')
@php
    $phase = $lesson->schoolClass->level->phase;
    $open = in_array($status->status, ['DRAFT', 'RETURNED']);
    $isTeacher = $lesson->teacher_id === auth()->id() || auth()->user()->role === 'FACILITY_ADMIN';
@endphp
<div class="page-head">
    <div><h1>{{ $lesson->subject->name }}</h1><div class="sub">{{ $lesson->schoolClass->name() }} &middot; {{ $term->label() }} &middot; {{ $rosterCount }} learners &middot; CA {{ $lesson->subject->ca_weight }}%, exam {{ $lesson->subject->exam_weight }}%</div></div>
    <span class="badge {{ status_tone($status->status) }} text-[.85rem] p-[.35rem_.7rem]">{{ label($status->status) }}</span>
</div>
@if ($status->status === 'RETURNED' && $status->remark)
    <div class="alert warn"><strong>Returned for correction.</strong> {{ $status->remark }}</div>
@endif
<div class="grid grid-main">
    <div class="stack">
        <div class="panel">
            <div class="panel-head"><h2>Assessments</h2></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Assessment</th><th>Type</th><th>Language</th><th>Date</th><th class="num">Out of</th><th>Entered</th><th></th></tr></thead>
                <tbody>
                @forelse ($assessments as $a)
                    <tr>
                        <td class="strong">{{ $a->title }}</td>
                        <td><span class="badge {{ $a->kind === 'EXAM' ? 'info' : 'neutral' }}">{{ $a->kind === 'EXAM' ? 'Examination' : 'Continuous' }}</span></td>
                        <td>{{ $a->language }}</td>
                        <td>{{ $a->held_on?->format('j M') }}</td>
                        <td class="num">{{ $a->max_score }}</td>
                        <td class="min-w-[110px]"><div class="progress {{ $a->marks_count < $rosterCount ? 'amber' : '' }}"><span style="width:{{ $rosterCount ? min(100, $a->marks_count / $rosterCount * 100) : 0 }}%"></span></div><div class="small muted">{{ $a->marks_count }} of {{ $rosterCount }}</div></td>
                        <td class="right"><a class="btn secondary small" href="{{ route('school.marks.sheet', $a) }}">{{ $open ? 'Enter' : 'View' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No assessments yet for this term.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
        <div class="panel">
            <div class="panel-head"><h2>Current standing</h2><span class="hint">Calculated from the marks entered so far</span></div>
            <div class="table-wrap"><table class="table compact">
                <thead><tr><th>Learner</th><th class="num">CA %</th><th class="num">Exam %</th><th class="num">Final</th><th>Grade</th></tr></thead>
                <tbody>
                @foreach ($scores as $r)
                    <tr><td>{{ $r['student']->fullName() }}</td><td class="num">{{ num($r['ca']) }}</td><td class="num">{{ num($r['exam']) }}</td><td class="num strong">{{ num($r['final']) }}</td><td>@if($r['grade'] !== null)<span class="grade {{ grade_tone($phase, $r['grade']) }}">{{ $r['grade'] }}</span> <span class="small muted">{{ $r['label'] }}</span>@endif</td></tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="stack">
        @if ($open && ($canCa || $canExam))
        <div class="panel">
            <div class="panel-head"><h2>Add an assessment</h2></div>
            <form method="POST" action="{{ route('school.marks.assessments', $lesson) }}">@csrf
                <div class="panel-body stack-sm">
                    <div class="field"><label>Type</label><select name="kind">@if($canCa)<option value="CA">Continuous assessment</option>@endif @if($canExam)<option value="EXAM">End of term examination</option>@endif</select></div>
                    <div class="field"><label>Title</label><input type="text" name="title" placeholder="Test 2, Project, Mid-term test" required></div>
                    <div class="field"><label>Out of</label><input type="number" name="max_score" value="50" min="1" max="500"></div>
                    <div class="field"><label>Date</label><input type="date" name="held_on" value="{{ now()->toDateString() }}"></div>
                    <div class="field"><label>Language of assessment</label><select name="language">@foreach (['English', 'Chichewa', 'Local language'] as $lang)<option @selected($lang === $lesson->schoolClass->level->instruction_language)>{{ $lang }}</option>@endforeach</select></div>
                </div>
                <div class="panel-foot"><span></span><button class="btn secondary" type="submit">Add</button></div>
            </form>
        </div>
        @endif
        @if ($open && $isTeacher)
        <div class="panel">
            <div class="panel-head"><h2>Submit for review</h2></div>
            <form method="POST" action="{{ route('school.marks.submit', $lesson) }}" data-confirm="Submit these marks? They will be locked until reviewed.">@csrf
                <div class="panel-body stack-sm">
                    <p class="small m-0">{{ $phase === 'PRIMARY' ? 'Marks go to the class teacher for review, then to the head teacher for release.' : 'Marks go to the head of department for validation, then the form master, the Deputy Head Academic and the head teacher.' }}</p>
                    <label class="check"><input type="checkbox" name="confirm_missing" value="1"> Submit even if some marks are blank</label>
                </div>
                <div class="panel-foot"><span></span><button class="btn" type="submit">{{ icon('send', 16) }} Submit marks</button></div>
            </form>
        </div>
        @endif
        <div class="panel">
            <div class="panel-head"><h2>History</h2></div>
            <div class="panel-body"><dl class="kv">
                <dt>Submitted</dt><dd>{{ $status->submitted_at ? $status->submitted_at->format('j M Y, H:i').' by '.$status->submitter?->name : 'Not yet' }}</dd>
                <dt>Validated</dt><dd>{{ $status->validated_at ? $status->validated_at->format('j M Y, H:i').' by '.$status->validator?->name : 'Not yet' }}</dd>
            </dl></div>
        </div>
    </div>
</div>
@endsection
