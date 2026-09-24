@extends('layouts.app')
@section('title', $class->name())
@section('crumbs')<a href="{{ route('school.classes.index') }}">Classes</a> / @endsection
@section('content')
@php
    $secondary = $class->isSecondary(); $u = auth()->user(); $ctl = app(\App\Http\Controllers\School\ResultController::class);
    $canResults = $u->isSchoolLeader() || $class->class_teacher_id === $u->id || $class->classSubjects->contains(fn ($cs) => $cs->teacher_id === $u->id || $ctl->canValidate($u, $cs));
@endphp
<div class="page-head">
    <div><h1>{{ $class->name() }}</h1><div class="sub">{{ $class->classTeacher ? ($secondary ? 'Form master' : 'Class teacher').': '.$class->classTeacher->name : 'No class teacher assigned' }} &middot; {{ $students->count() }} learners &middot; taught in {{ $class->level->instruction_language }}</div></div>
    <div class="actions">
        @if($canSeePii)<a class="btn secondary" href="{{ route('school.attendance.edit', $class) }}">{{ icon('check-square', 16) }} Take register</a>@endif
        @if($canResults)<a class="btn secondary" href="{{ route('school.results.show', $class) }}">{{ icon('chart', 16) }} Results</a>@endif
        <a class="btn ghost" href="{{ route('school.timetable.index', ['view' => 'class', 'class_id' => $class->id]) }}">{{ icon('grid', 16) }} Timetable</a>
    </div>
</div>
@if ($canManage)
<div class="panel">
    <form method="POST" action="{{ route('school.classes.update', $class) }}" class="panel-body filters">@csrf @method('PUT')
        <div class="field"><label>{{ $secondary ? 'Form master' : 'Class teacher' }}</label>
            <select name="class_teacher_id"><option value="">Not assigned</option>
                @foreach ($teachers as $t)<option value="{{ $t->id }}" @selected($class->class_teacher_id === $t->id)>{{ $t->name }} ({{ \App\Models\TeacherProfile::QUALIFICATIONS[$t->teacherProfile?->qualification] ?? 'no qualification' }})</option>@endforeach
            </select>
        </div>
        <div class="field"><label>Capacity</label><input type="number" name="capacity" value="{{ $class->capacity }}" min="1" max="200"></div>
        <div class="field"><label>Room</label><input type="text" name="room" value="{{ $class->room }}"></div>
        <div class="actions"><button class="btn" type="submit">Save</button></div>
    </form>
</div>
@endif
<div class="grid grid-main">
    <div class="panel">
        <div class="panel-head"><h2>Subjects and teachers</h2><span class="hint">{{ $lessons->sum('periods_per_week') }} periods a week</span></div>
        <form method="POST" action="{{ route('school.classes.assign', $class) }}">@csrf
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Subject</th><th>Teacher</th><th class="num">Periods</th>@if($secondary)<th class="num">Taking</th>@endif</tr></thead>
                <tbody>
                @foreach ($lessons as $cs)
                    <tr>
                        <td><span class="strong">{{ $cs->subject->name }}</span> @if(! $cs->subject->is_core)<span class="badge neutral">Elective</span>@endif<div class="small muted">{{ $cs->subject->department?->name }}</div></td>
                        <td>@if($canManage)<select name="lessons[{{ $cs->id }}][teacher_id]"><option value="">Not assigned</option>@foreach ($teachers as $t)<option value="{{ $t->id }}" @selected($cs->teacher_id === $t->id)>{{ $t->name }}</option>@endforeach</select>@else{{ $cs->teacher?->name ?? 'Not assigned' }}@endif</td>
                        <td class="num">@if($canManage)<input type="number" name="lessons[{{ $cs->id }}][periods_per_week]" value="{{ $cs->periods_per_week }}" min="0" max="12" style="width:70px">@else{{ $cs->periods_per_week }}@endif</td>
                        @if($secondary)<td class="num">{{ $cs->subject->is_core ? $students->count() : ($electiveCounts[$cs->id] ?? 0) }}</td>@endif
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            @if($canManage)<div class="panel-foot"><span class="small muted">Teachers are notified of new assignments.</span><button class="btn" type="submit">Save assignments</button></div>@endif
        </form>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Learners</h2><span class="hint">{{ $students->where('gender', 'Female')->count() }} girls, {{ $students->where('gender', 'Male')->count() }} boys</span></div>
        <ul class="list">
            @forelse ($students as $s)
                <li>
                    <span>@if($canSeePii)<a href="{{ route('school.students.show', $s) }}">{{ $s->fullName() }}</a>@else{{ $s->fullName() }}@endif
                        @if($secondary && $s->electives->count())<div class="small muted">{{ $s->electives->count() }} electives</div>@endif</span>
                    <span class="small muted">{{ $s->admission_number }}</span>
                </li>
            @empty
                <li class="muted">No learners enrolled.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
