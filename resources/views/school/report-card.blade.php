@extends('layouts.app')
@section('title', 'Report card, '.$student->fullName())
@section('content')
@php
    $school = $student->school; $class = $student->schoolClass; $phase = $class->level->phase;
    $present = ($attendance['PRESENT'] ?? 0) + ($attendance['LATE'] ?? 0); $days = $attendance->sum();
@endphp
<div class="page-head no-print">
    <div><h1>Report card</h1><div class="sub">{{ $student->fullName() }} &middot; {{ $term->label() }}</div></div>
    <div class="actions">
        @if(! $status || $status->stage !== 'RELEASED')<span class="badge warn">Draft, not yet released</span>@endif
        <button class="btn" type="button" onclick="window.print()">{{ icon('printer', 16) }} Print</button>
    </div>
</div>
<div class="report">
    <div class="report-head">
        <div>{{ icon('award', 48) }}</div>
        <div>
            <p>Republic of Malawi</p>
            <p class="strong">Ministry of Education</p>
            <h2>{{ $school->name }}</h2>
            <p>{{ $school->postal_address }}{{ $school->phone ? ', Tel '.$school->phone : '' }}</p>
            <p class="strong" style="margin-top:.4rem">End of Term Report, {{ $term->label() }}</p>
        </div>
        <div>{{ icon('school', 48) }}</div>
    </div>
    <dl class="kv">
        <dt>Name</dt><dd class="strong">{{ $student->fullName() }}</dd>
        <dt>Admission No.</dt><dd>{{ $student->admission_number }}</dd>
        <dt>Class</dt><dd>{{ $class->name() }}</dd>
        <dt>Position</dt><dd>{{ $report['position'] ?? 'n/a' }} out of {{ $report['class_size'] ?? 'n/a' }}</dd>
        <dt>Attendance</dt><dd>{{ $present }} of {{ $days }} days</dd>
        <dt>Average</dt><dd>{{ num($report['average'] ?? null) }}% (class {{ num($report['class_average'] ?? null) }}%)</dd>
        @if($student->maneb_exam_number)<dt>MANEB No.</dt><dd>{{ $student->maneb_exam_number }}</dd>@endif
    </dl>
    @if ($report)
    <table class="table bordered compact">
        <thead><tr><th>Subject</th><th class="num">Continuous assessment</th><th class="num">Examination</th><th class="num">Final %</th><th class="num">Grade</th><th>Remark</th></tr></thead>
        <tbody>
        @foreach ($report['rows'] as $r)
            <tr><td>{{ $r['subject']->name }}</td><td class="num">{{ num($r['ca']) }}</td><td class="num">{{ num($r['exam']) }}</td><td class="num strong">{{ num($r['final']) }}</td><td class="num"><span class="grade {{ grade_tone($phase, $r['grade']) }}">{{ $r['grade'] }}</span></td><td>{{ $r['label'] }}</td></tr>
        @endforeach
        </tbody>
    </table>
    @if($phase === 'SECONDARY' && isset($report['credits']))
        <p class="small" style="margin-top:.6rem"><strong>{{ $report['credits'] }}</strong> credits, <strong>{{ $report['passes'] }}</strong> passes{{ $report['best_six'] ? ', best six aggregate '.$report['best_six'] : '' }}. {{ $report['exam_note'] }}</p>
    @endif
    @else
        <p class="muted">No marks recorded for this term.</p>
    @endif
    <p class="small muted" style="margin-top:.8rem">Grading key: {{ $bands->map(fn ($b) => $b->grade.' '.$b->label.' ('.(int) $b->min_score.' to '.(int) $b->max_score.')')->implode(', ') }}. Final mark is 40% continuous assessment and 60% examination.</p>
    <table class="table bordered" style="margin-top:1rem">
        <tr><th style="width:190px">Class teacher's remarks</th><td>{{ $comment?->class_teacher_comment }}</td></tr>
        <tr><th>Head teacher's remarks</th><td>{{ $comment?->head_comment }}</td></tr>
    </table>
    <p class="small" style="margin-top:.8rem">@if($nextTerm)Next term opens on <strong>{{ $nextTerm->starts_on->format('l j F Y') }}</strong>.@endif</p>
    <div class="sign">
        <div>{{ $class->classTeacher?->name }}<br><span class="small muted">Class teacher</span></div>
        <div>{{ $school->headTeacher?->name }}<br><span class="small muted">Head teacher, signature and school stamp</span></div>
    </div>
</div>
@endsection
