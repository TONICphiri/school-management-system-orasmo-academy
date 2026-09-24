@extends('layouts.app')
@section('title', 'Classes')
@section('content')
<div class="page-head"><div><h1>Classes</h1><div class="sub">{{ $year?->name ?? 'No current year' }} &middot; one class teacher or form master per class per year</div></div></div>
<div class="grid {{ $canManage ? 'grid-main' : '' }}">
    <div class="panel">
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Class</th><th>Section</th><th>Class teacher</th><th>Room</th><th class="num">Learners</th><th>Capacity</th></tr></thead>
            <tbody>
            @forelse ($classes as $c)
                @php $fill = $c->capacity ? $c->students_count / $c->capacity * 100 : 0; @endphp
                <tr>
                    <td><a class="strong" href="{{ route('school.classes.show', $c) }}">{{ $c->name() }}</a><div class="small muted">{{ $c->level->instruction_language }}{{ $c->level->national_exam ? ', '.$c->level->national_exam.' year' : '' }}</div></td>
                    <td>{{ $c->section?->name ?? label($c->level->phase) }}</td>
                    <td>{{ $c->classTeacher?->name ?? '' }}@unless($c->classTeacher)<span class="badge warn">Not assigned</span>@endunless</td>
                    <td>{{ $c->room }}</td>
                    <td class="num">{{ $c->students_count }}</td>
                    <td style="min-width:120px"><div class="progress {{ $fill > 95 ? 'red' : '' }}"><span style="width:{{ min(100, $fill) }}%"></span></div><div class="small muted">{{ $c->students_count }} of {{ $c->capacity }}</div></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty"><strong>No classes this year</strong>Add the first class to begin enrolment.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
    @if ($canManage)
    <div class="panel">
        <div class="panel-head"><h2>Add a class</h2></div>
        <form method="POST" action="{{ route('school.classes.store') }}">@csrf
            <div class="panel-body stack-sm">
                <div class="field"><label>Level</label><select name="level_id">@foreach ($levels as $l)<option value="{{ $l->id }}" @selected(old('level_id') == $l->id)>{{ $l->name }}</option>@endforeach</select></div>
                <div class="field"><label>Stream</label><input type="text" name="stream" value="{{ old('stream', 'A') }}" required><div class="help">For example A, B or East</div></div>
                <div class="field"><label>Capacity</label><input type="number" name="capacity" value="{{ old('capacity', 60) }}" min="1" max="200"></div>
                <div class="field"><label>Room</label><input type="text" name="room" value="{{ old('room') }}"></div>
                <div class="field"><label>Class teacher</label>
                    <select name="class_teacher_id"><option value="">Assign later</option>
                        @foreach ($teachers as $t)<option value="{{ $t->id }}">{{ $t->name }} ({{ $t->teacherProfile?->qualification ?? 'no qualification' }})</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="panel-foot"><span class="small muted">Core subjects are added automatically.</span><button class="btn" type="submit">Add class</button></div>
        </form>
    </div>
    @endif
</div>
@endsection
