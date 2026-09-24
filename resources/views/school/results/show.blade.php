@extends('layouts.app')
@section('title', 'Results for '.$class->name())
@section('crumbs')<a href="{{ route('school.results.index') }}">Results approval</a> / @endsection
@section('content')
@php
    $phase = $class->level->phase;
    $steps = $isSecondary
        ? ['OPEN' => 'Subject teachers and heads of department', 'CLASS_REVIEWED' => 'Form master review', 'DEPUTY_APPROVED' => 'Deputy Head Academic', 'RELEASED' => 'Head teacher release']
        : ['OPEN' => 'Subject teachers submit', 'CLASS_REVIEWED' => 'Class teacher review', 'RELEASED' => 'Head teacher release'];
    $order = array_keys($steps);
    $at = array_search($status->stage, $order);
    $isHead = $user->role === 'FACILITY_ADMIN';
    $isDeputy = in_array($user->role, ['DEPUTY_HEAD_ACADEMIC', 'DEPUTY_HEAD', 'FACILITY_ADMIN']);
    $releaseReady = $status->stage === ($isSecondary ? 'DEPUTY_APPROVED' : 'CLASS_REVIEWED');
    $resultCtl = app(\App\Http\Controllers\School\ResultController::class);
@endphp
<div class="page-head">
    <div><h1>{{ $class->name() }} results</h1><div class="sub">{{ $term->label() }} &middot; {{ $results['class_size'] }} learners &middot; class average {{ num($results['class_average']) }}%</div></div>
    <span class="badge {{ status_tone($status->stage) }} text-[.85rem] p-[.35rem_.7rem]">{{ $stages[$status->stage] }}</span>
</div>

<div class="pipeline mb-[1.25rem]" style="grid-template-columns:repeat({{ count($steps) }}, 1fr)">
    @foreach ($steps as $k => $v)
        @php $i = array_search($k, $order); @endphp
        <div class="step {{ $i < $at || $status->stage === 'RELEASED' ? 'done' : ($i === $at ? 'current' : '') }}">
            <div class="t">Step {{ $i + 1 }}</div><div class="strong">{{ $v }}</div>
            <div class="small muted">
                @if($k === 'CLASS_REVIEWED' && $status->class_reviewed_at) {{ $status->class_reviewed_at->format('j M, H:i') }} @endif
                @if($k === 'DEPUTY_APPROVED' && $status->deputy_approved_at) {{ $status->deputy_approved_at->format('j M, H:i') }} @endif
                @if($k === 'RELEASED' && $status->released_at) {{ $status->released_at->format('j M, H:i') }} @endif
            </div>
        </div>
    @endforeach
</div>

@if ($errors->has('review'))<div class="alert error">{{ $errors->first('review') }}</div>@endif

<div class="panel">
    <div class="panel-head"><h2>Subjects</h2>
        <div class="actions">
            @if ($canReview && $status->stage === 'OPEN')
                <form method="POST" action="{{ route('school.results.review', $class) }}" data-confirm="Confirm that you have reviewed all subject marks for {{ $class->name() }}?">@csrf<button class="btn" type="submit">{{ icon('check', 16) }} Mark class as reviewed</button></form>
            @endif
            @if ($isSecondary && $isDeputy && $status->stage === 'CLASS_REVIEWED')
                <form method="POST" action="{{ route('school.results.approve', $class) }}" data-confirm="Approve the results of {{ $class->name() }} and send them to the head teacher?">@csrf<button class="btn" type="submit">{{ icon('check-square', 16) }} Approve results</button></form>
            @endif
            @if ($isHead && $releaseReady)
                <form method="POST" action="{{ route('school.results.release', $class) }}" data-confirm="Release results to learners and parents? They will be notified by SMS and cannot be changed afterwards.">@csrf<button class="btn" type="submit">{{ icon('send', 16) }} Release to families</button></form>
            @endif
        </div>
    </div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Subject</th><th>Teacher</th><th>Status</th><th>Submitted</th>@if($isSecondary)<th>Validated</th>@endif<th></th></tr></thead>
        <tbody>
        @foreach ($class->classSubjects->sortBy('subject.name') as $cs)
            @php $st = $subjectStatuses[$cs->id]; $canVal = $resultCtl->canValidate($user, $cs); @endphp
            <tr>
                <td class="strong">@if($user->isSchoolLeader() || $cs->teacher_id === $user->id || $canVal || $class->class_teacher_id === $user->id)<a href="{{ route('school.marks.show', $cs) }}">{{ $cs->subject->name }}</a>@else{{ $cs->subject->name }}@endif</td>
                <td>{{ $cs->teacher?->name ?? 'Not assigned' }}</td>
                <td><span class="badge {{ status_tone($st->status) }}">{{ label($st->status) }}</span>@if($st->status === 'RETURNED' && $st->remark)<div class="small muted">{{ $st->remark }}</div>@endif</td>
                <td class="small">{{ $st->submitted_at?->format('j M, H:i') }}</td>
                @if($isSecondary)<td class="small">{{ $st->validated_at ? $st->validated_at->format('j M').', '.$st->validator?->name : '' }}</td>@endif
                <td class="right nowrap">
                    @if ($status->stage === 'OPEN')
                        @if ($isSecondary && $canVal && $st->status === 'SUBMITTED')
                            <form class="inline" method="POST" action="{{ route('school.results.validate', $st) }}">@csrf<button class="btn small" type="submit">Validate</button></form>
                        @endif
                        @if (($canVal || $isHead || (! $isSecondary && $canReview)) && in_array($st->status, ['SUBMITTED', 'VALIDATED']))
                            <details class="inline-block"><summary class="btn ghost small">Return</summary>
                                <form method="POST" action="{{ route('school.results.return', $st) }}" class="inline-form mt-[.4rem]">@csrf<input type="text" name="remark" placeholder="Reason for return" required><button class="btn danger small" type="submit">Return</button></form>
                            </details>
                        @endif
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</div>

@if ($showAll)
<form method="POST" action="{{ route('school.results.comments', $class) }}">@csrf
    @if($isHead)<input type="hidden" name="as" value="{{ $class->class_teacher_id === $user->id ? 'class' : 'head' }}">@endif
    <div class="panel">
        <div class="panel-head"><h2>Class results</h2><span class="hint">{{ $isSecondary ? 'MSCE grade 1 to 9. Best six uses the six strongest subjects.' : 'Grade 4 Excellent, 3 Good, 2 Average, 1 Needs Support' }}</span></div>
        <div class="table-wrap"><table class="table compact bordered">
            <thead><tr><th class="num">Pos</th><th>Learner</th>
                @foreach ($results['subjects'] as $cs)<th class="num" title="{{ $cs->subject->name }}">{{ $cs->subject->code }}</th>@endforeach
                <th class="num">Average</th>@if($isSecondary)<th class="num">Best six</th><th>Standing</th>@endif<th>{{ $isHead && $class->class_teacher_id !== $user->id ? 'Head teacher comment' : 'Class teacher comment' }}</th><th></th></tr></thead>
            <tbody>
            @foreach (collect($results['learners'])->sortBy(fn ($l) => $l['position'] ?? 999) as $sid => $l)
                @php $c = $comments[$sid] ?? null; @endphp
                <tr>
                    <td class="num strong">{{ $l['position'] ?? '' }}</td>
                    <td class="nowrap">{{ $l['student']->fullName() }}</td>
                    @foreach ($results['subjects'] as $cs)
                        @php $r = $l['rows'][$cs->id] ?? null; @endphp
                        <td class="num">@if($r && $r['final'] !== null)<span title="{{ num($r['final']) }}%" class="grade {{ grade_tone($phase, $r['grade']) }}">{{ $r['grade'] }}</span>@elseif(! $r)<span class="muted small">&middot;</span>@endif</td>
                    @endforeach
                    <td class="num strong">{{ num($l['average']) }}</td>
                    @if($isSecondary)
                        <td class="num">{{ $l['best_six'] ?? '' }}</td>
                        <td>@if($l['exam_eligible'] !== null)<span class="badge {{ $l['exam_eligible'] ? 'ok' : 'warn' }}" title="{{ $l['exam_note'] }}">{{ $l['exam_eligible'] ? 'On track' : 'At risk' }}</span>@else<span class="small muted">{{ $l['credits'] }} credits</span>@endif</td>
                    @endif
                    <td class="min-w-[220px]"><input type="text" name="comment[{{ $sid }}]" value="{{ $isHead && $class->class_teacher_id !== $user->id ? $c?->head_comment : $c?->class_teacher_comment }}" @disabled($status->stage === 'RELEASED')></td>
                    <td><a class="btn ghost small" href="{{ route('school.reports.card', $l['student']) }}">{{ icon('printer', 14) }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        @if($status->stage !== 'RELEASED')<div class="panel-foot"><span class="small muted">Comments appear on the printed report card.</span><button class="btn secondary" type="submit">Save comments</button></div>@endif
    </div>
</form>
@endif
@endsection
