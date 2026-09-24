@extends('layouts.app')
@section('title', 'Subjects')
@section('content')
@php $admin = auth()->user()->isSchoolAdmin(); @endphp
<div class="page-head"><div><h1>Subjects</h1><div class="sub">Final marks combine continuous assessment and the end of term examination. The national default is 40% and 60%.</div></div></div>
<div class="grid {{ $admin ? 'grid-main' : '' }}">
    <div class="stack">
    @forelse ($subjects as $phase => $list)
        <div class="panel">
            <div class="panel-head"><h2>{{ label($phase) }} subjects</h2><span class="hint">{{ $list->count() }}</span></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Subject</th><th>Code</th><th>Type</th>@if($phase === 'SECONDARY')<th>Department</th>@endif<th class="num">CA</th><th class="num">Exam</th><th class="num">Classes</th></tr></thead>
                <tbody>
                @foreach ($list as $s)
                    <tr>
                        <td class="strong">{{ $s->name }}</td><td>{{ $s->code }}</td>
                        <td>@if($s->is_core)<span class="badge ok">Core</span>@else<span class="badge neutral">{{ $phase === 'SECONDARY' ? 'Elective' : 'Optional' }}</span>@endif</td>
                        @if($phase === 'SECONDARY')<td>{{ $s->department?->name }}</td>@endif
                        <td class="num">{{ $s->ca_weight }}%</td><td class="num">{{ $s->exam_weight }}%</td><td class="num">{{ $s->class_subjects_count }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
    @empty
        <div class="panel"><div class="empty">No subjects yet.</div></div>
    @endforelse
    </div>
    @if ($admin)
    <div class="panel">
        <div class="panel-head"><h2>Add or update a subject</h2></div>
        <form method="POST" action="{{ route('school.subjects.store') }}">@csrf
            <div class="panel-body stack-sm">
                <div class="field"><label>Existing subject</label><select name="id"><option value="">New subject</option>@foreach ($subjects->flatten() as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ label($s->phase) }})</option>@endforeach</select><div class="help">Choose a subject to change its weights.</div></div>
                <div class="field"><label>Name</label><input type="text" name="name" value="{{ old('name') }}" required></div>
                <div class="field"><label>Code</label><input type="text" name="code" value="{{ old('code') }}" required></div>
                <div class="field"><label>Phase</label><select name="phase">@if($school->type !== 'SECONDARY')<option value="PRIMARY">Primary</option>@endif @if($school->type !== 'PRIMARY')<option value="SECONDARY">Secondary</option>@endif</select></div>
                <div class="field" data-show-when="phase=SECONDARY"><label>Department</label><select name="department_id"><option value="">None</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
                <label class="check"><input type="checkbox" name="is_core" value="1" checked> Core subject taken by every learner</label>
                <div class="field"><label>Continuous assessment weight (%)</label><input type="number" name="ca_weight" value="{{ old('ca_weight', 40) }}" min="0" max="100"><div class="help">The examination weight is the remainder.</div></div>
            </div>
            <div class="panel-foot"><span></span><button class="btn" type="submit">Save subject</button></div>
        </form>
    </div>
    @endif
</div>
@endsection
