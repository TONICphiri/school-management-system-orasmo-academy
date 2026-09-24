@extends('layouts.app')
@section('title', $assessment->title)
@section('crumbs')<a href="{{ route('school.marks.index') }}">Mark entry</a> / <a href="{{ route('school.marks.show', $lesson) }}">{{ $lesson->subject->name }}, {{ $lesson->schoolClass->name() }}</a> / @endsection
@section('content')
<div class="page-head">
    <div><h1>{{ $assessment->title }}</h1><div class="sub">{{ $assessment->kind === 'EXAM' ? 'End of term examination' : 'Continuous assessment' }} &middot; out of {{ $assessment->max_score }} &middot; {{ $assessment->language }}</div></div>
    <span class="badge {{ status_tone($status->status) }}">{{ label($status->status) }}</span>
</div>
@unless ($editable)<div class="alert info">These marks are read only. {{ in_array($status->status, ['DRAFT', 'RETURNED']) ? '' : 'They have been submitted for review.' }}</div>@endunless
<form method="POST" action="{{ route('school.marks.save', $assessment) }}">@csrf
    <div class="panel">
        <div class="table-wrap"><table class="table">
            <thead><tr><th>#</th><th>Learner</th><th>Admission</th><th class="num">Mark out of {{ $assessment->max_score }}</th><th>Absent</th><th>Last entered</th></tr></thead>
            <tbody>
            @foreach ($students as $i => $s)
                @php $m = $marks[$s->id] ?? null; @endphp
                <tr class="{{ $m?->absent ? 'row-absent' : '' }}">
                    <td class="muted">{{ $i + 1 }}</td>
                    <td class="strong">{{ $s->fullName() }}</td>
                    <td class="small muted">{{ $s->admission_number }}</td>
                    <td class="num"><input class="mark-input" type="number" step="0.5" min="0" max="{{ $assessment->max_score }}" name="score[{{ $s->id }}]" value="{{ old('score.'.$s->id, $m && $m->score !== null ? rtrim(rtrim(number_format($m->score, 2, '.', ''), '0'), '.') : '') }}" @disabled(! $editable)></td>
                    <td><label class="check"><input type="checkbox" name="absent[{{ $s->id }}]" value="1" @checked(old('absent.'.$s->id, $m?->absent)) @disabled(! $editable)></label></td>
                    <td class="small muted">{{ $m?->entered_at ? $m->entered_at->format('j M, H:i').', '.$m->enteredBy?->name : '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        @if ($editable)
            <div class="panel-foot"><span class="small muted">Press Enter to move to the next learner. Every change is recorded in the audit log.</span><button class="btn" type="submit">Save marks</button></div>
        @endif
    </div>
</form>
@endsection
